<?php

namespace App\Service\UserLists;

use App\Entity\UserRetainer;
use App\Exceptions\GeneralJsonException;
use App\Repository\UserRetainerRepository;
use App\Service\Companion\Companion;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;

class UserRetainers
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var UserRetainerRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em, Users $users, Companion $companion)
    {
        $this->em         = $em;
        $this->users      = $users;
        $this->companion  = $companion;
        $this->repository = $em->getRepository(UserRetainer::class);
        $this->console    = new ConsoleOutput();
    }

    /**
     * Get a retainer
     */
    public function get(string $unique, bool $confirmed = false)
    {
        return $this->repository->findOneBy([
            'unique'    => $unique,
            'confirmed' => $confirmed,
        ]);
    }

    /**
     * Add a new character to the site
     */
    public function add(Request $request)
    {
        $name   = $request->get('name');
        $server = GameServers::getServerId($request->get('server'));
        $unique = UserRetainer::unique($name, $server);

        if ($this->get($unique, true)) {
            throw new GeneralJsonException('Retainer already exists and is confirmed.');
        }

        $retainer = (new UserRetainer())
            ->setUser($this->users->getUser())
            ->setName($name)
            ->setServer($server)
            ->setUnique($unique)
            ->setSlug($name);

        $this->save($retainer);
    }

    /**
     * Confirm a characters ownership
     */
    public function confirm(UserRetainer $retainer)
    {
        $user = $this->users->getUser();

        // confirmation variables
        $server    = GameServers::LIST[$retainer->getServer()];
        $itemId    = $retainer->getConfirmItem();
        $itemPrice = $retainer->getConfirmPrice();
        $name      = $retainer->getName();

        // todo - add real-time market query onto XIVAPI so we can get the current listed prices

        return;

        // confirm ownership and save
        $retainer->setConfirmed(true)->setUpdated(time());

        $this->save($retainer);
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserRetainer $obj)
    {
        $this->em->persist($obj);
        $this->em->flush();
        return true;
    }
}
