<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use App\Repository\UserAlertRepository;
use App\Service\Companion\Companion;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserAlerts
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var UserAlertRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em, Users $users, Companion $companion)
    {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
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

    /**
     * Trigger alerts, intended to be called from commands
     */
    public function trigger(bool $patrons = false)
    {
        $this->console->writeln("Triggering Alerts");

        // grab all alerts
        $alerts = $this->getAllByPatronStatus($patrons);

        /** @var UserAlert $alert */
        foreach ($alerts as $alert) {
            
            $this->console->writeln("- Alert: <comment>{$alert->getName()}</comment> by <info>{$alert->getUser()->getUsername()}</info>");
            $this->console->writeln("--> Trigger Condition: <comment>{$alert->getTriggerOptionFormula()}</comment>");
            $this->console->writeln("--> Communication: <comment>". ($alert->isNotifiedViaDiscord() ? 'Discord' : 'None') ."</comment>");
            
            //
            // handle server
            //
            $dc         = GameServers::getDataCenter($alert->getServer());
            $dcServers  = GameServers::getDataCenterServers($alert->getServer());
            $servers    = $alert->isTriggerDataCenter() ? $dcServers : [ $alert->getServer() ];
            $this->console->writeln("--> Data Center: <info>{$dc}</info>: ". implode(', ', $servers));
            
            // grab market data
            # $market = $this->companion->getByServers($servers, $alert->getItemId());

            // todo check when prices last changed
            // - if this user is a patron user and the prices are older than a few minutes
            //   it will query companion directly.
        }
    }
}
