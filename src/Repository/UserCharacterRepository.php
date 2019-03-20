<?php

namespace App\Repository;

use App\Entity\UserCharacter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserCharacterRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserCharacter::class);
    }

    public function findLastUpdated(int $limit)
    {
        $sql = $this->createQueryBuilder('uc');
        $sql->orderBy('uc.updated', 'ASC')
            ->setMaxResults($limit);

        return $sql->getQuery()->getResult();
    }
}
