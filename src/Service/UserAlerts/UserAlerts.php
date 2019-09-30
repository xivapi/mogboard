<?php

namespace App\Service\UserAlerts;

use App\Common\Entity\UserAlert;
use App\Common\Game\GameServers;
use App\Common\Repository\UserAlertRepository;
use App\Common\User\Users;
use App\Service\GameData\GameDataSource;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

class UserAlerts
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var GameDataSource */
    private $gamedata;
    /** @var UserAlertRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;
    /** @var XIVAPI */
    private $xivapi;
    /** @var UserAlertsDiscordNotification */
    private $discord;

    public function __construct(
        EntityManagerInterface $em,
        Users $users,
        GameDataSource $gamedata,
        UserAlertsDiscordNotification $discord
    ) {
        $this->em           = $em;
        $this->users        = $users;
        $this->gamedata     = $gamedata;
        $this->repository   = $em->getRepository(UserAlert::class);
        $this->console      = new ConsoleOutput();
        $this->xivapi       = new XIVAPI();
        $this->discord      = $discord;
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
            'user'   => $this->users->getUser(true),
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
     * Save a new or existing alert
     */
    public function save(UserAlert $alert)
    {
        $user = $this->users->getUser(true);
        
        $alert
            ->setServer(GameServers::getServer())
            ->setUser($user);

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
        if ($force === false) {
            $user = $this->users->getUser();
    
            if ($force || $alert->getUser() !== $user) {
                throw new UnauthorisedAlertOwnershipException();
            }
        }

        $this->em->remove($alert);
        $this->em->flush();

        return true;
    }
}
