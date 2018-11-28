<?php

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AlertRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function findOneMinuteAlerts()
    {
        $filter = [ 'delay' => 1 ];
        $order  = [ 'added' => 'asc' ];

        return $this->findBy($filter, $order);
    }

    public function findOneHourAlerts()
    {
        $filter = [ 'delay' => 60 ];
        $order  = [ 'added' => 'asc' ];

        return $this->findBy($filter, $order);
    }

    public function findOneDayAlerts()
    {
        $filter = [ 'delay' => 1440 ];
        $order  = [ 'added' => 'asc' ];

        return $this->findBy($filter, $order);
    }
}
