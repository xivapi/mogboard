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
    const TRIGGER_FIELDS = [
        // Prices
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

        // History
        'History_Added',
        'History_CharacterName',
        'History_IsHQ',
        'History_PricePerUnit',
        'History_PriceTotal',
        'History_PurchaseDate',
        'History_Quantity',

        // Custom
        'Custom_TotalStockCount',
    ];

    // what to do once the trigger is fired
    const TRIGGER_ACTION_CONTINUE = 'continue';
    const TRIGGER_ACTION_DELETE   = 'delete';
    const TRIGGER_ACTION_PAUSE    = 'pause';

    // the maximum number of times a trigger will send in 1 day
    const LIMIT_DEFAULT      = 10;

    // the delay between sending triggers - 10 mins
    const DELAY_DEFAULT      = (60 * 10);

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
     * @var int
     * @ORM\Column(type="integer")
     */
    private $triggerAction = 0;
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
        $this->uniq   = Random::randomSecureString(8);
    }

    /**
     * Build a new alert from a json payload request.
     */
    public static function buildFromRequest(Request $request, ?UserAlert $alert = null): UserAlert
    {
        $json  = \GuzzleHttp\json_decode($request->getContent());
        $alert = $alert ?: new UserAlert();

        $alert
            ->setItemId($json->itemId ?: $alert->getItemId())
            ->setName($json->name ?: $alert->getName())
            ->setTriggerDataCenter($json->dc ?: $alert->isTriggerDataCenter())
            ->setTriggerLimit($json->limit ?: $alert->getTriggerLimit())
            ->setTriggerHq($json->hq ?: $alert->isTriggerHq())
            ->setTriggerNq($json->nq ?: $alert->isTriggerNq())
            ->setNotifiedViaDiscord($json->discord ?: $alert->isNotifiedViaDiscord())
            ->setNotifiedViaEmail($json->email ?: $alert->isNotifiedViaEmail());

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

            $conditions[] = [$field, $operator, $value];
        }

        return $conditions;
    }

    public function setTriggerConditions(array $triggerConditions)
    {
        $this->triggerConditions = $triggerConditions;

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
