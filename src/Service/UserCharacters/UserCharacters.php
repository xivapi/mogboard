<?php

namespace App\Service\UserCharacters;

use App\Entity\UserCharacter;
use App\Exceptions\GeneralJsonException;
use App\Repository\UserCharacterRepository;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use XIVAPI\XIVAPI;

class UserCharacters
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var UserCharacterRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;
    /** @var XIVAPI */
    private $xivapi;

    public function __construct(EntityManagerInterface $em, Users $users)
    {
        $this->em         = $em;
        $this->users      = $users;
        $this->repository = $em->getRepository(UserCharacter::class);
        $this->console    = new ConsoleOutput();
        $this->xivapi     = new XIVAPI();
    }

    /**
     * Get a single character
     */
    public function get(int $id, bool $confirmed = false)
    {
        return $this->repository->findOneBy([
            'lodestoneId' => $id,
            'confirmed'   => $confirmed,
        ]);
    }

    /**
     * Add a new character to the site
     */
    public function add(Request $request)
    {
        $lodestoneId = $request->get('lodestone_id');

        if ($this->get($lodestoneId, true)) {
            throw new GeneralJsonException('Character already exists and is confirmed.');
        }
        
        $user = $this->users->getUser();
        
        /** @var UserCharacter $existing */
        $existing = $this->get($lodestoneId);
        if ($existing && $existing->getUser() === $user) {
            throw new GeneralJsonException('You have already added this character...');
        }

        $character = (new UserCharacter())
            ->setUser($user)
            ->setLodestoneId($lodestoneId);

        $this->save($character);
    }

    /**
     * Confirm a characters ownership
     */
    public function confirm(int $lodestoneId)
    {
        /** @var UserCharacter $character */
        $character = $this->get($lodestoneId);
        $user = $this->users->getUser();

        // run character verification
        $verification = $this->xivapi->character->verify($character->getLodestoneId());

        // test if our Users pass phrase was found
        if (stripos($verification->Bio, $user->getCharacterPassPhrase()) === false) {
            throw new GeneralJsonException('Character pass phrase could not be found on the characters profile bio.');
        }

        // confirm ownership and save
        $character
            ->setUser($user)
            ->setConfirmed(true)
            ->setMain(empty($user->getCharacters()));

        $this->save($character);
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserCharacter $obj)
    {
        $this->em->persist($obj);
        $this->em->flush();
        return true;
    }

    /**
     * Auto-update information on characters, eg: Name, Server and Avatar
     */
    public function autoupdate()
    {
        $characters = $this->repository->findLastUpdated(100);

        /** @var UserCharacter $character */
        foreach ($characters as $character) {
            $data = $this->xivapi->character->get($character->getLodestoneId());

            // ensure we don't get stuck on a character
            $character->setUpdated(time());
            $this->save($character);

            if (!$data) {
                continue;
            }

            $character
                ->setName($data->Character->Name)
                ->setServer(GameServers::getServerId($data->Character->Server))
                ->setAvatar($data->Character->Avatar);

            $this->save($character);
        }
    }
}
