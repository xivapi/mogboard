<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use App\Repository\UserAlertRepository;
use App\Service\Companion\Companion;
use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserAlerts
{
    /** @var EntityManagerInterface */
    protected $em;
    /** @var Users */
    protected $users;
    /** @var Companion */
    protected $companion;
    /** @var GameDataSource */
    protected $gamedata;
    /** @var UserAlertRepository */
    protected $repository;
    /** @var ConsoleOutput */
    protected $console;

    public function __construct(EntityManagerInterface $em, Users $users, Companion $companion, GameDataSource $gamedata)
    {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
        $this->gamedata     = $gamedata;
        $this->repository   = $em->getRepository(UserAlert::class);
        $this->console      = new ConsoleOutput();
    }

    /**
     * Get one alert
     */
    public function get(string $id)
    {
        return $this->repository->find($id);
    }

    /**
     * Get all alerts for a given user
     */
    public function getAllForCurrentUser()
    {
        return $this->repository->findBy([
            'user' => $this->users->getUser(true),
        ]);
    }
    
    /**
     * Get all alerts for a given user
     */
    public function getAllForItemForCurrentUser($itemId)
    {
        return $this->repository->findBy([
            'user' => $this->users->getUser(true),
            'itemId' => $itemId,
        ]);
    }

    /**
     * Get all alerts
     */
    public function getAll(array $filters = [], array $order = [], int $limit = null, int $offset = null)
    {
        return $this->repository->findBy($filters, $order, $limit, $offset);
    }
    
    /**
     * Get all alerts by their patron status
     */
    public function getAllByPatronStatus(bool $patron = false)
    {
        return $this->repository->findPatrons($patron);
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserAlert $alert)
    {
        $user   = $this->users->getUser(true);
        $server = GameServers::getServer();

        // set user and trigger delay
        $alert->setServer($server)->setUser($user);

        $this->em->persist($alert);
        $this->em->flush();
        return true;
    }

    /**
     * Delete an existing alert if the owner owns it, if force
     * is set true then user check is not required.
     */
    public function delete(UserAlert $alert, bool $force = false)
    {
        if ($force || $alert->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedAlertOwnershipException();
        }

        $this->em->remove($alert);
        return true;
    }
}
