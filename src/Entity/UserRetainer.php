<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_retainers")
 * @ORM\Entity(repositoryClass="App\Repository\UserRetainerRepository")
 */
class UserRetainer
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="lists")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $slug;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $server;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $avatar;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $confirmed = false;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $confirmItem;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $confirmPrice;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $updated;
    /**
     * - The ID of the retainer on XIVAPI
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $apiRetainerId;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();

        // confirmation will be a shard, crystal or cluster sold at a very high price
        $this->confirmItem  = mt_rand(2, 19);
        $this->confirmPrice = mt_rand(9999,999999);
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug)
    {
        $this->slug = $slug;

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

    public function getServer(): int
    {
        return $this->server;
    }

    public function setServer(int $server)
    {
        $this->server = $server;

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed)
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function getConfirmItem(): int
    {
        return $this->confirmItem;
    }

    public function setConfirmItem(int $confirmItem)
    {
        $this->confirmItem = $confirmItem;

        return $this;
    }

    public function getConfirmPrice(): int
    {
        return $this->confirmPrice;
    }

    public function setConfirmPrice(int $confirmPrice)
    {
        $this->confirmPrice = $confirmPrice;

        return $this;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function setUpdated(int $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    public function getApiRetainerId()
    {
        return $this->apiRetainerId;
    }

    public function setApiRetainerId($apiRetainerId)
    {
        $this->apiRetainerId = $apiRetainerId;

        return $this;
    }
}
