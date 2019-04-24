<?php

namespace App\Repository;

use App\Entity\UserAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserAlertRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserAlert::class);
    }
    
    public function findPatrons(bool $patron, int $offset, int $limit)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->join('a.user', 'u')
            ->orderBy('a.lastChecked', 'asc')
            ->setFirstResult($limit * $offset)
            ->setMaxResults($limit);

        if ($patron) {
            $sql->where('u.patron > 0');
        } else {
            $sql->where('u.patron = 0');
        }
        
        return $sql->getQuery()->getResult();
    }
}
