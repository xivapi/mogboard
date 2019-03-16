<?php

namespace App\Service\Alert;

use App\Entity\UserAlert;
use App\Exceptions\UnauthorisedAlertOwnershipException;
use App\Repository\AlertRepository;
use App\Service\Companion\Companion;
use App\Service\Content\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class Alerts
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var AlertRepository */
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
    public function getAllForItemForCurrentUser(int $itemId)
    {
        return $this->repository->findBy([
            'user' => $this->users->getUser(true),
            'item' => $itemId,
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

        // set user and trigger delay
        $alert
            ->setUser($user)
            ->setTriggerDelay($user->isPatron() ? UserAlert::DELAY_PATRON : UserAlert::DELAY_DEFAULT);

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
        $alerts = $this->getAll([
            'user.patron' => $patrons,
        ]);

        /** @var UserAlert $alert */
        foreach ($alerts as $alert) {
            // get servers to check
            $servers = $alert->isTriggerDataCenter()
                ? GameServers::getDataCenter($alert->getServer())
                : [ $alert->getServer() ];

            // grab market data
            $market = $this->companion->getByServers($servers, $alert->getItemId());

            // todo check
            // - if this user is a patron user and the prices are older than a few minutes
            //   it will query companion directly.
        }
    }
}
