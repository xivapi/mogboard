<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ORM\Table(name="users_alerts")
 * @ORM\Entity(repositoryClass="App\Repository\UserAlertRepository")
 */
class UserAlert
{
    const TRIGGERS = [
        // price per unit
        100 => 'Price Per Unit > [X]',
        110 => 'Price Per Unit < [X]',
        120 => 'Price Per Unit = [X]',
        # 130 => '(SOON) Price Per Unit Avg > [X]',
        # 140 => '(SOON) Price Per Unit Avg < [X]',

        200 => 'Price Total > [X]',
        210 => 'Price Total < [X]',
        220 => 'Price Total = [X]',
        # 230 => '(SOON) Price Total Avg > [X]',
        # 240 => '(SOON) Price Total Avg < [X]',

        300 => 'Single Stock Quantity > [X]',
        310 => 'Single Stock Quantity < [X]',
        320 => 'Single Stock Quantity = [X]',

        400 => 'Total Stock Quantity > [X]',
        410 => 'Total Stock Quantity < [X]',
        420 => 'Total Stock Quantity = [X]',

        # 500 => 'Total Stock Quantity > [X]',
        # 510 => 'Total Stock Quantity < [X]',
        # 520 => 'Total Stock Quantity = [X]',
        
        600 => 'Retainer Name = [X]',
        700 => 'Buyer Name = [X]',
        800 => 'Craft Name = [X]'
    ];
    
    // the maximum number of times a trigger will send in 1 day
    const LIMIT_DEFAULT      = 20;
    // the delay between sending triggers
    const DELAY_DEFAULT      = 300;
    // how old data can be before it's requested to be manually updated
    const PATRON_UPDATE_TIME = 300;

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
    private $itemId;
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
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $server;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerDataCenter = false;
    /**
     * @var int
     * @ORM\Column(type="integer", length=3)
     */
    private $triggerOption;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $triggerValue;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggerLimit = self::LIMIT_DEFAULT;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggerDelay = self::DELAY_DEFAULT;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggerLastSent = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggersSent = 0;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerHq = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerNq = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $triggerActive = true;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $notifiedViaEmail = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $notifiedViaDiscord = false;
    /**
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user")
     */
    private $events;
    
    public function __construct()
    {
        $this->id     = Uuid::uuid4();
        $this->added  = time();
        $this->events = new ArrayCollection();
    }

    /**
     * Build a new alert from a json payload request.
     */
    public static function buildFromRequest(Request $request, ?UserAlert $alert = null): UserAlert
    {
        $obj = \GuzzleHttp\json_decode($request->getContent());

        $alert = $alert ?: new UserAlert();

        return $alert
            ->setItemId($obj->itemId ?: $alert->getItemId())
            ->setName($obj->name ?: $alert->getName())
            ->setTriggerDataCenter($obj->dc ?: $alert->isTriggerDataCenter())
            ->setTriggerOption($obj->option ?: $alert->getTriggerOption())
            ->setTriggerValue($obj->value ?: $alert->getTriggerValue())
            ->setTriggerHq($obj->hq ?: $alert->isTriggerHq())
            ->setTriggerNq($obj->nq ?: $alert->isTriggerNq())
            ->setNotifiedViaDiscord($obj->discord ?: $alert->isNotifiedViaDiscord())
            ->setNotifiedViaEmail($obj->email ?: $alert->isNotifiedViaEmail());
    }

    public function getTrigger()
    {
        return self::TRIGGERS[$this->getTriggerOption()];
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
    
    public function getItemId(): int
    {
        return $this->itemId;
    }
    
    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;
        
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

    public function getServer(): string
    {
        return $this->server;
    }

    public function setServer(string $server)
    {
        $this->server = $server;

        return $this;
    }

    public function isTriggerDataCenter(): bool
    {
        return $this->triggerDataCenter;
    }

    public function setTriggerDataCenter(bool $triggerDataCenter)
    {
        $this->triggerDataCenter = $triggerDataCenter;

        return $this;
    }

    public function getTriggerOption(): int
    {
        return $this->triggerOption;
    }
    
    public function getTriggerOptionFormula(): string
    {
        return str_ireplace(
            '[X]', $this->triggerValue, self::TRIGGERS[$this->triggerOption]
        );
    }
    
    public function setTriggerOption(int $triggerOption)
    {
        $this->triggerOption = $triggerOption;
        
        return $this;
    }
    
    public function getTriggerValue(): string
    {
        return $this->triggerValue;
    }
    
    public function setTriggerValue(string $triggerValue)
    {
        $this->triggerValue = $triggerValue;
        
        return $this;
    }
    
    public function getTriggerLimit(): int
    {
        return $this->triggerLimit;
    }
    
    public function setTriggerLimit(int $triggerLimit)
    {
        $this->triggerLimit = $triggerLimit;
        
        return $this;
    }
    
    public function getTriggerDelay(): int
    {
        return $this->triggerDelay;
    }
    
    public function setTriggerDelay(int $triggerDelay)
    {
        $this->triggerDelay = $triggerDelay;
        
        return $this;
    }
    
    public function getTriggerLastSent(): int
    {
        return $this->triggerLastSent;
    }
    
    public function setTriggerLastSent(int $triggerLastSent)
    {
        $this->triggerLastSent = $triggerLastSent;
        
        return $this;
    }
    
    public function getTriggersSent(): int
    {
        return $this->triggersSent;
    }
    
    public function setTriggersSent(int $triggersSent)
    {
        $this->triggersSent = $triggersSent;
        
        return $this;
    }
    
    public function incrementTriggersSent()
    {
        $this->triggersSent++;
        
        return $this;
    }
    
    public function isTriggerHq(): bool
    {
        return $this->triggerHq;
    }
    
    public function setTriggerHq(bool $triggerHq)
    {
        $this->triggerHq = $triggerHq;
        
        return $this;
    }
    
    public function isTriggerNq(): bool
    {
        return $this->triggerNq;
    }
    
    public function setTriggerNq(bool $triggerNq)
    {
        $this->triggerNq = $triggerNq;
        
        return $this;
    }
    
    public function isTriggerActive(): bool
    {
        return $this->triggerActive;
    }
    
    public function setTriggerActive(bool $triggerActive)
    {
        $this->triggerActive = $triggerActive;
        
        return $this;
    }
    
    public function isNotifiedViaEmail(): bool
    {
        return $this->notifiedViaEmail;
    }
    
    public function setNotifiedViaEmail(bool $notifiedViaEmail)
    {
        $this->notifiedViaEmail = $notifiedViaEmail;
        
        return $this;
    }
    
    public function isNotifiedViaDiscord(): bool
    {
        return $this->notifiedViaDiscord;
    }
    
    public function setNotifiedViaDiscord(bool $notifiedViaDiscord)
    {
        $this->notifiedViaDiscord = $notifiedViaDiscord;
        
        return $this;
    }
    
    public function getEvents()
    {
        return $this->events;
    }
    
    public function setEvents($events)
    {
        $this->events = $events;
        
        return $this;
    }
    
    public function addEvent(UserAlertEvents $userAlertEvents)
    {
        $this->events[] = $userAlertEvents;
        return $this;
    }
}
