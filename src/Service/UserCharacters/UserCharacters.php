<?php

namespace App\Service\UserCharacters;

use App\Common\Entity\UserCharacter;
use App\Common\Repository\UserCharacterRepository;
use App\Common\User\Users;
use App\Exceptions\GeneralJsonException;
use App\Exceptions\UnauthorisedRetainerOwnershipException;
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
     * Confirm a characters ownership
     */
    public function confirm(int $lodestoneId)
    {
        $user = $this->users->getUser();

        // run character verification
        $verification = $this->xivapi->character->verify($lodestoneId);

        // test if our Users pass phrase was found
        if (stripos($verification->Bio, $user->getCharacterPassPhrase()) === false) {
            throw new GeneralJsonException('Character auth code could not be found on the characters profile bio.');
        }
        
        // grab character
        /** @var \stdClass $character */
        $json = $this->xivapi->character->get($lodestoneId);

        // confirm ownership and save
        $character = new UserCharacter();
        $character
            ->setLodestoneId($lodestoneId)
            ->setName($json->Character->Name)
            ->setServer($json->Character->Server)
            ->setAvatar($json->Character->Avatar)
            ->setUpdated(time())
            ->setUser($user)
            ->setConfirmed(true)
            ->setMain(!empty($user->getCharacters()));
        
        $this->save($character);
        return true;
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
     * Delete a character
     */
    public function delete(UserCharacter $userCharacter)
    {
        if ($userCharacter->getUser() !== $this->users->getUser()) {
            throw new UnauthorisedRetainerOwnershipException();
        }
        
        $this->em->remove($userCharacter);
        $this->em->flush();
        return true;
    }

    /**
     * Auto-update information on characters, eg: Name, Server and Avatar
     */
    public function autoupdate()
    {
        $a = microtime(true);
        $console = new ConsoleOutput();
        $console->writeln("Getting 500 characters to update");
        
        $characters = $this->repository->findLastUpdated(500);

        /** @var UserCharacter $character */
        foreach ($characters as $character) {
            $console->writeln("- {$character->getName()}");
            
            $data = $this->xivapi->character->get($character->getLodestoneId());

            // ensure we don't get stuck on a character
            $character->setUpdated(time());
            $this->save($character);

            if (!$data) {
                continue;
            }

            $character
                ->setName($data->Character->Name)
                ->setServer($data->Character->Server)
                ->setAvatar($data->Character->Avatar);

            $this->save($character);
        }
        
        $b = round(microtime(true) - $a, 3);
        $console->writeln("Done! {$b}");
    }
}
