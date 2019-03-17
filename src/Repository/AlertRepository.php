<?php

namespace App\Repository;

use App\Entity\UserAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AlertRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserAlert::class);
    }
    
    public function findPatrons(bool $patron)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->join('a.user', 'u')
            ->where('u.patron = :patron')
            ->setParameter('patron', $patron);
        
        return $sql->getQuery()->getResult();
    }
}
