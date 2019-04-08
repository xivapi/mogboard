<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_alerts_queues")
 * @ORM\Entity(repositoryClass="App\Repository\UserAlertQueueRepository")
 */
class UserAlertQueue
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var integer
     * @ORM\Column(type="integer", length=8, unique=true)
     */
    private $number;
    /**
     * Not a relationship as it changes frequently and never needs to associate directly.
     *
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number)
    {
        $this->number = $number;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user)
    {
        $this->user = $user;

        return $this;
    }
    
    public function isActive(): bool
    {
        return !empty($this->user);
    }
}
