<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="alerts_items")
 * @ORM\Entity(repositoryClass="App\Repository\AlertItemRepository")
 */
class AlertItem
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $itemId;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $server;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $scans = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $lastScanned = 0;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $data = '';
    /**
     * @ORM\OneToMany(targetEntity="Alert", mappedBy="alertItem")
     */
    private $alerts;

    public function __construct(?int $itemId = null, ?string $server = null)
    {
        $this->id = Uuid::uuid4();
        $this->alerts = new ArrayCollection();
        $this->added = time();
        
        $this->itemId = $itemId ?: $this->getItemId();
        $this->server = $server ?: $this->getServer();
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

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }
    
    public function getServer(): string
    {
        return $this->server;
    }
    
    public function setServer(string $server)
    {
        $this->server = $server;
        
        return $this;
    }
    
    public function getScans(): int
    {
        return $this->scans;
    }

    public function setScans(int $scans)
    {
        $this->scans = $scans;

        return $this;
    }

    public function getLastScanned(): int
    {
        return $this->lastScanned;
    }

    public function setLastScanned(int $lastScanned)
    {
        $this->lastScanned = $lastScanned;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data)
    {
        $this->data = $data;

        return $this;
    }

    public function getAlerts()
    {
        return $this->alerts;
    }

    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;

        return $this;
    }
}
