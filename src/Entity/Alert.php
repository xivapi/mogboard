<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="alerts")
 * @ORM\Entity(repositoryClass="App\Repository\AlertRepository")
 */
class Alert
{
    const CONDITION_MIN_PRICE = 1;
    const CONDITION_MAX_PRICE = 2;
    const CONDITION_AVG_PRICE = 3;
    const CONDITION_MIN_STOCK = 10;
    const CONDITION_MAX_STOCK = 11;
    const CONDITION_MIN_QTY = 20;
    const CONDITION_MAX_QTY = 21;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="alerts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;
    /**
     * @var int
     * @ORM\Column(type="integer", length=3)
     */
    private $condition;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $value;
    /**
     * In minutes
     *
     * @var int
     * @ORM\Column(type="integer", length=10)
     */
    private $delay;
    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $notifyViaDesktop;
    /**
     * @var User
     * @ORM\Column(type="string", length=64)
     */
    private $notifyViaEmail;
    /**
     * @var User
     * @ORM\Column(type="string", length=64)
     */
    private $notifyViaDiscord;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->added = time();
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getCondition(): int
    {
        return $this->condition;
    }

    public function setCondition(int $condition)
    {
        $this->condition = $condition;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay)
    {
        $this->delay = $delay;

        return $this;
    }

    public function isNotifyViaDesktop(): bool
    {
        return $this->notifyViaDesktop;
    }

    public function setNotifyViaDesktop(bool $notifyViaDesktop)
    {
        $this->notifyViaDesktop = $notifyViaDesktop;

        return $this;
    }

    public function getNotifyViaEmail(): User
    {
        return $this->notifyViaEmail;
    }

    public function setNotifyViaEmail(User $notifyViaEmail)
    {
        $this->notifyViaEmail = $notifyViaEmail;

        return $this;
    }

    public function getNotifyViaDiscord(): User
    {
        return $this->notifyViaDiscord;
    }

    public function setNotifyViaDiscord(User $notifyViaDiscord)
    {
        $this->notifyViaDiscord = $notifyViaDiscord;

        return $this;
    }
}
