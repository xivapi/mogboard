<?php

namespace App\Service\UserAlerts;

use App\Entity\User;
use App\Entity\UserAlert;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use App\Repository\UserAlertRepository;
use App\Service\Common\Mog;
use App\Service\Companion\Companion;
use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

class UserAlerts
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
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
        Companion $companion,
        GameDataSource $gamedata,
        UserAlertsDiscordNotification $discord
    ) {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
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
            'user' => $this->users->getUser(),
        ]);
    }
    
    /**
     * Get all alerts for a given user
     */
    public function getAllForItemForCurrentUser($itemId)
    {
        return $this->repository->findBy([
            'user'   => $this->users->getUser(),
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
    public function getAllByPatronStatus(bool $patronQueue = false, int $offset,  int $limit = 100)
    {
        return $this->repository->findPatrons($patronQueue, $offset, $limit);
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserAlert $alert, bool $sendDiscordMessage = true)
    {
        $user = $this->users->getUser();
        
        $alert
            ->setServer(GameServers::getServer())
            ->setUser($user);

        $this->em->persist($alert);
        $this->em->flush();
        
        if ($sendDiscordMessage && $alert->isNotifiedViaDiscord()) {
            $this->discord->sendSavedAlertNotification($alert);
        }
        
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

        if ($alert->isNotifiedViaDiscord()) {
            $this->discord->sendDeletedAlertNotification($alert);
        }
        
        return true;
    }
}
