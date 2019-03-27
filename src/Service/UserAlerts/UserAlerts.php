<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use App\Repository\UserAlertRepository;
use App\Service\Common\Mog;
use App\Service\Companion\Companion;
use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use App\Service\Redis\Redis;
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

    public function __construct(EntityManagerInterface $em, Users $users, Companion $companion, GameDataSource $gamedata)
    {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
        $this->gamedata     = $gamedata;
        $this->repository   = $em->getRepository(UserAlert::class);
        $this->console      = new ConsoleOutput();
        $this->xivapi       = new XIVAPI(XIVAPI::STAGING);
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
            'user' => $this->users->getUser(),
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
        $user   = $this->users->getUser();
        $server = GameServers::getServer();

        // set user and trigger delay
        $alert->setServer($server)->setUser($user);

        $this->em->persist($alert);
        $this->em->flush();
        
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");
        
        // confirm
        Mog::aymeric("Alert Confirmation: **{$alert->getName()}** `{$alert->getTriggerOptionFormula()}` - [{$item->ID}] {$item->Name_en}", $user->getSsoDiscordId());
        
        return true;
    }

    /**
     * Delete an existing alert if the owner owns it, if force
     * is set true then user check is not required.
     */
    public function delete(UserAlert $alert, bool $force = false)
    {
        $user = $this->users->getUser();
        
        if ($force || $alert->getUser() !== $user) {
            throw new UnauthorisedAlertOwnershipException();
        }

        $this->em->remove($alert);
        $this->em->flush();
    
        Mog::aymeric("Alert Deleted: **{$alert->getName()}** `{$alert->getTriggerOptionFormula()}`", $user->getSsoDiscordId());
    
        return true;
    }
}
