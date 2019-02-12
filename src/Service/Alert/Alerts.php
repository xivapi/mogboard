<?php

namespace App\Service\Alert;

use App\Entity\User;
use App\Entity\Alert;
use Doctrine\ORM\EntityManagerInterface;

class Alerts
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(User $user, Alert $alert)
    {
        $alert->setUser($user);
        $this->em->persist($alert);
        $this->em->flush();
    }

    public function update(Alert $alert)
    {
        $this->em->persist($alert);
        $this->em->flush();
    }
}
