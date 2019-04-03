<?php

namespace App\Entity;

use App\Utils\Random;
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
    const STRING_PREG = "/[^a-zA-Z0-9\+_\- ]/";
    
    const TRIGGER_FIELDS = [
        // Prices
        'Prices' => [
            'Prices_Added',
            'Prices_CreatorSignatureName',
            'Prices_IsCrafted',
            'Prices_IsHQ',
            'Prices_HasMateria',
            'Prices_PricePerUnit',
            'Prices_PriceTotal',
            'Prices_Quantity',
            'Prices_RetainerName',
            //'Prices_StainID',
            'Prices_TownID',
        ],

        // History
        'History' => [
            'History_Added',
            'History_CharacterName',
            'History_IsHQ',
            'History_PricePerUnit',
            'History_PriceTotal',
            'History_PurchaseDate',
            'History_Quantity',
        ],
    ];
    
    const TRIGGER_OPERATORS = [
        1 => '[ > ] Greater than',
        2 => '[ >= ] Greater than or equal to',
        3 => '[ < ] Less than',
        4 => '[ <= ] Less than or equal to',
        5 => '[ = ] Equal-to',
        6 => '[ != ] Not equal-to',
        7 => '[ % ] Is Divisible by',
    ];
    
    const TRIGGER_OPERATORS_SHORT = [
        1 => '>',
        2 => '>=',
        3 => '<',
        4 => '<=',
        5 => '=',
        6 => '!=',
        7 => '%',
    ];
    
    // what to do once the trigger is fired
    const TRIGGER_ACTION_CONTINUE = 'continue';
    const TRIGGER_ACTION_DELETE   = 'delete';
    const TRIGGER_ACTION_PAUSE    = 'pause';

    // the maximum number of times a trigger will send in 1 day
    const LIMIT_DEFAULT = 10;
    const LIMIT_PATREON = 50;

    // the delay between sending alerts
    const DELAY_DEFAULT = (60 * 60);
    const DELAY_PATREON = (60 * 10);

    // how old data can be before it's requested to be manually updated
    const PATRON_UPDATE_TIME = 120;

    #-------------------------------------------------------------------------------------------------------------------
    
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(type="string", length=8, unique=true)
     */
    private $uniq;
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
     * @var array
     * @ORM\Column(type="array")
     */
    private $triggerConditions = [];
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $triggerType;
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
     * @var string
     * @ORM\Column(type="string")
     */
    private $triggerAction = self::TRIGGER_ACTION_CONTINUE;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $triggerDataCenter = false;
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
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user", cascade={"remove"}, orphanRemoval=true)
     */
    private $events;

    public function __construct()
    {
        $this->id     = Uuid::uuid4();
        $this->added  = time();
        $this->events = new ArrayCollection();
        $this->uniq   = strtoupper(Random::randomHumanUniqueCode(8));
    }

    /**
     * Build a new alert from a json payload request.
     */
    public static function buildFromRequest(Request $request, ?UserAlert $alert = null): UserAlert
    {
        $json  = \GuzzleHttp\json_decode($request->getContent());
        $alert = $alert ?: new UserAlert();
 
        // preg_replace(self::STRING_PREG, null, $name);
        $alert
            ->setItemId($json->alert_item_id ?: $alert->getItemId())
            ->setName($json->alert_name ?: $alert->getName())
            ->setTriggerDataCenter($json->alert_dc ?: $alert->isTriggerDataCenter())
            ->setTriggerType($json->alert_type ?: $alert->getTriggerType())
            ->setTriggerHq($json->alert_hq ?: $alert->isTriggerHq())
            ->setTriggerNq($json->alert_nq ?: $alert->isTriggerNq())
            ->setNotifiedViaDiscord($json->alert_notify_discord ?: $alert->isNotifiedViaDiscord())
            ->setNotifiedViaEmail($json->alert_notify_email ?: $alert->isNotifiedViaEmail());
        
        // add triggers
        foreach($json->alert_triggers as $trigger) {
            $alert->addTriggerCondition(
                $trigger->alert_trigger_field,
                $trigger->alert_trigger_op,
                preg_replace(self::STRING_PREG, null, $trigger->alert_trigger_value)
            );
        }

        return $alert;
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
    
    public function getUniq(): ?string
    {
        return $this->uniq;
    }
    
    public function setUniq(string $uniq)
    {
        $this->uniq = $uniq;
        
        return $this;
    }

    public function getUser(): ?User
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
        $this->name = preg_replace(self::STRING_PREG, null, $name);

        return $this;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function setServer(string $server)
    {
        $this->server = preg_replace(self::STRING_PREG, null, $server);

        return $this;
    }

    public function getTriggerConditions(): array
    {
        return $this->triggerConditions;
    }

    /**
     * Formats conditions.
     */
    public function getTriggerConditionsFormatted(): array
    {
        $conditions = [];
        foreach ($this->triggerConditions as $triggerCondition) {
            [$field, $operator, $value] = explode(',', $triggerCondition);
            
            $operator = self::TRIGGER_OPERATORS_SHORT[$operator];
            $conditions[] = [$field, $operator, $value];
        }

        return $conditions;
    }

    public function setTriggerConditions(array $triggerConditions)
    {
        $this->triggerConditions = $triggerConditions;

        return $this;
    }
    
    public function getTriggerType(): string
    {
        return $this->triggerType;
    }
    
    public function setTriggerType(string $triggerType)
    {
        $this->triggerType = $triggerType;
        
        return $this;
    }
    
    public function addTriggerCondition($field, $op, $value)
    {
        $this->triggerConditions[] = sprintf("%s,%s,%s", $field, $op, $value);
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
    
    public function incrementTriggersSent(): self
    {
        $this->triggersSent++;
        return $this;
    }

    public function getTriggerAction(): int
    {
        return $this->triggerAction;
    }

    public function setTriggerAction(int $triggerAction)
    {
        $this->triggerAction = $triggerAction;

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
}
