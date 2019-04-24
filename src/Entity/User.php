<?php

namespace App\Entity;

use App\Service\User\SignInDiscord;
use App\Utils\Random;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    const NORMAL_USER        = 0;
    const PATREON_ADVENTURER = 1;
    const PATREON_TANK       = 2;
    const PATREON_HEALER     = 3;
    const PATREON_DPS        = 4;
    const PATREON_BENEFIT    = 9;
    
    const DEFAULT_MAX           = 5;
    const DEFAULT_MAX_NOTIFY    = 20;
    const DEFAULT_TIMEOUT       = (60 * 60);
    const DEFAULT_EXPIRY        = (60 * 60 * 24 * 3);

    /**
     * Alert benefits per patreon
     */
    const ALERT_LIMITS = [
        self::NORMAL_USER => [
            'MAX'                   => self::DEFAULT_MAX,
            'MAX_NOTIFICATIONS'     => self::DEFAULT_MAX_NOTIFY,
            'NOTIFY_TIMEOUT'        => self::DEFAULT_TIMEOUT,
            'EXPIRY_TIMEOUT'        => self::DEFAULT_EXPIRY,
            'UPDATE_TIMEOUT'        => false,
        ],
        self::PATREON_ADVENTURER => [
            'MAX'                   => 10,
            'MAX_NOTIFICATIONS'     => 100,
            'NOTIFY_TIMEOUT'        => (60 * 10),
            'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 10),
            'UPDATE_TIMEOUT'        => false,
        ],
        self::PATREON_TANK => [
            'MAX'                   => 10,
            'MAX_NOTIFICATIONS'     => 100,
            'NOTIFY_TIMEOUT'        => (60 * 10),
            'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 10),
            'UPDATE_TIMEOUT'        => false,
        ],
        self::PATREON_HEALER => [
            'MAX'                   => 10,
            'MAX_NOTIFICATIONS'     => 100,
            'NOTIFY_TIMEOUT'        => (60 * 10),
            'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 10),
            'UPDATE_TIMEOUT'        => false,
        ],
        self::PATREON_DPS => [
            'MAX'                   => 25,
            'MAX_NOTIFICATIONS'     => 9999,
            'NOTIFY_TIMEOUT'        => (60 * 10),
            'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 20),
            'UPDATE_TIMEOUT'        => (60 * 10),
        ],
        self::PATREON_BENEFIT => [
            'MAX'                   => 5,
            'MAX_NOTIFICATIONS'     => 20,
            'NOTIFY_TIMEOUT'        => (60 * 10),
            'EXPIRY_TIMEOUT'        => (60 * 60 * 24 * 3),
            'UPDATE_TIMEOUT'        => false,
        ],
    ];

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * The name of the SSO provider
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $sso;
    /**
     * @var string
     * A random hash saved to cookie to retrieve the token
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $session;
    /**
     * @var string
     * Username provided by the SSO provider (updates on token refresh)
     * @ORM\Column(type="string", length=64)
     */
    private $username;
    /**
     * @var string
     * Email provided by the SSO token, this is considered "unique", if someone changes their
     * email then this would in-affect create a new account.
     * @ORM\Column(type="string", length=128)
     */
    private $email;
    /**
     * Either provided by SSO provider or default
     *
     *  DISCORD: https://cdn.discordapp.com/avatars/<USER ID>/<AVATAR ID>.png?size=256
     *
     * @var string
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $avatar = 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png';
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $patron = 0;
    /**
     * @ORM\OneToMany(targetEntity="UserList", mappedBy="user")
     */
    private $lists;
    /**
     * @ORM\OneToMany(targetEntity="UserReport", mappedBy="user")
     */
    private $reports;
    /**
     * @ORM\OneToMany(targetEntity="UserCharacter", mappedBy="user")
     */
    private $characters;
    /**
     * @ORM\OneToMany(targetEntity="UserRetainer", mappedBy="user")
     * @ORM\OrderBy({"added" = "DESC"})
     */
    private $retainers;
    
    //
    // -------- ALERTS --------
    //

    /**
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user")
     */
    private $alerts;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsMax = self::DEFAULT_MAX;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsMaxNotifications = self::DEFAULT_MAX_NOTIFY;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsNotificationCount = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsNotifyTimeout = self::DEFAULT_TIMEOUT;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsExpiry = self::DEFAULT_EXPIRY;

    //
    // -------- DISCORD SSO --------
    //

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordId;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordAvatar;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $ssoDiscordTokenExpires = 0;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordTokenAccess;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordTokenRefresh;

    public function __construct()
    {
        $this->id         = Uuid::uuid4();
        $this->alerts     = new ArrayCollection();
        $this->lists      = new ArrayCollection();
        $this->reports    = new ArrayCollection();
        $this->characters = new ArrayCollection();
        $this->retainers  = new ArrayCollection();
    
        $this->generateSession();
    }

    public function generateSession()
    {
        $this->session = Random::randomSecureString(250);
        return;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getSso()
    {
        return $this->sso;
    }
    
    public function setSso($sso)
    {
        $this->sso = $sso;
        
        return $this;
    }
    
    public function getSession()
    {
        return $this->session;
    }
    
    public function setSession($session)
    {
        $this->session = $session;
        
        return $this;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
    
    public function setUsername(string $username)
    {
        $this->username = $username;
        
        return $this;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function setEmail(string $email)
    {
        $this->email = $email;
        
        return $this;
    }
    
    public function getAvatar(): string
    {
        if ($this->sso == SignInDiscord::NAME) {
            $this->avatar = sprintf("https://cdn.discordapp.com/avatars/%s/%s.png?size=256",
                $this->ssoDiscordId,
                $this->ssoDiscordAvatar
            );
        }
        
        return $this->avatar;
    }
    
    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;
        
        return $this;
    }

    //
    // Alerts ----------------------------------------------------------------------------------------------------------
    //

    public function getAlerts()
    {
        return $this->alerts;
    }
    
    public function totalAlerts()
    {
        return $this->alerts ? count($this->alerts) : 0;
    }

    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;

        return $this;
    }
    
    public function getAlertsPerItem()
    {
        $itemAlerts = [];
        
        /** @var UserAlert $alert */
        foreach ($this->alerts as $alert) {
            $itemId = $alert->getItemId();
            
            if (!isset($itemAlerts[$itemId])) {
                $itemAlerts[$itemId] = [];
            }
    
            $itemAlerts[$itemId][] = $alert;
        }
        
        return $itemAlerts;
    }

    public function getAlertsMax(): int
    {
        return $this->alertsMax;
    }

    public function setAlertsMax(int $alertsMax)
    {
        $this->alertsMax = $alertsMax;

        return $this;
    }

    public function getAlertsMaxNotifications(): int
    {
        return $this->alertsMaxNotifications;
    }

    public function setAlertsMaxNotifications(int $alertsMaxNotifications)
    {
        $this->alertsMaxNotifications = $alertsMaxNotifications;

        return $this;
    }

    public function getAlertsNotificationCount(): int
    {
        return $this->alertsNotificationCount;
    }

    public function setAlertsNotificationCount(int $alertsNotificationCount)
    {
        $this->alertsNotificationCount = $alertsNotificationCount;

        return $this;
    }

    public function getAlertsNotifyTimeout(): int
    {
        return $this->alertsNotifyTimeout;
    }

    public function setAlertsNotifyTimeout(int $alertsNotifyTimeout)
    {
        $this->alertsNotifyTimeout = $alertsNotifyTimeout;

        return $this;
    }

    public function getAlertsExpiry(): int
    {
        return $this->alertsExpiry;
    }

    public function setAlertsExpiry(int $alertsExpiry)
    {
        $this->alertsExpiry = $alertsExpiry;

        return $this;
    }

    public function isAtMaxNotifications()
    {
        return $this->alertsNotificationCount >= $this->alertsMaxNotifications;
    }

    public function incrementNotificationCount()
    {
        $this->alertsNotificationCount++;
        return $this;
    }

    //
    // -----------------------------------------------------------------------------------------------------------------
    //

    public function getReports()
    {
        return $this->reports;
    }

    public function setReports($reports)
    {
        $this->reports = $reports;

        return $this;
    }

    public function addReport(UserReport $report)
    {
        $this->reports[] = $report;
        return $this;
    }
    
    public function isPatron(int $tier = null): bool
    {
        return $tier ? $this->patron === $tier : $this->patron > 0;
    }
    
    public function setPatron(int $patron)
    {
        $this->patron = $patron;
        
        return $this;
    }
    
    public function getPatreonTier(): string
    {
        $tiers = [
            'None',
            self::PATREON_DPS         => 'DPS',
            self::PATREON_HEALER      => 'Healer',
            self::PATREON_TANK        => 'Tank',
            self::PATREON_ADVENTURER  => 'Adventurer',
            self::PATREON_BENEFIT     => 'Benefit',
        ];
        
        return $tiers[$this->patron] ?? null;
    }
    
    public function getLists()
    {
        return $this->lists;
    }
    
    public function setLists($lists)
    {
        $this->lists = $lists;
        
        return $this;
    }
    
    /**
     * Get personal lists
     */
    public function getCustomLists()
    {
        $lists = [];
        
        /** @var UserList $list */
        foreach ($this->lists as $list) {
            if ($list->isCustom()) {
                continue;
            }
            
            $lists[] = $list;
        }
        
        return $lists;
    }
    
    public function hasFavouriteItem(int $itemId)
    {
        /** @var UserList $list */
        foreach ($this->lists as $list) {
            if ($list->getCustomType() === UserList::CUSTOM_FAVOURITES && $list->hasItem($itemId)) {
                return true;
            }
        }
        
        return false;
    }

    public function getCharacters()
    {
        return $this->characters;
    }
    
    public function getMainCharacter()
    {
        /** @var UserCharacter $character */
        foreach ($this->characters as $character) {
            if ($character->isMain()) {
                return $character;
            }
        }
        
        return null;
    }

    public function setCharacters($characters)
    {
        $this->characters = $characters;

        return $this;
    }

    public function addCharacter(UserCharacter $character)
    {
        $this->characters[] = $character;
        return $this;
    }

    public function getRetainers()
    {
        return $this->retainers;
    }

    public function setRetainers($retainers)
    {
        $this->retainers = $retainers;

        return $this;
    }

    public function addRetainer(UserRetainer $retainer)
    {
        $this->retainers[] = $retainer;
        return $this;
    }

    public function getCharacterPassPhrase()
    {
        return strtoupper('mb'. substr(sha1($this->id), 0, 5));
    }

    //
    // Discord SSO -----------------------------------------------------------------------------------------------------
    //
    
    public function getSsoDiscordId(): string
    {
        return $this->ssoDiscordId;
    }
    
    public function setSsoDiscordId(string $ssoDiscordId)
    {
        $this->ssoDiscordId = $ssoDiscordId;
        
        return $this;
    }
    
    public function getSsoDiscordAvatar(): string
    {
        return $this->ssoDiscordAvatar;
    }
    
    public function setSsoDiscordAvatar(?string $ssoDiscordAvatar = null)
    {
        $this->ssoDiscordAvatar = $ssoDiscordAvatar;
        
        return $this;
    }
    
    public function getSsoDiscordTokenExpires(): int
    {
        return $this->ssoDiscordTokenExpires;
    }
    
    public function setSsoDiscordTokenExpires(int $ssoDiscordTokenExpires)
    {
        $this->ssoDiscordTokenExpires = $ssoDiscordTokenExpires;
        
        return $this;
    }
    
    public function getSsoDiscordTokenAccess(): string
    {
        return $this->ssoDiscordTokenAccess;
    }
    
    public function setSsoDiscordTokenAccess(string $ssoDiscordTokenAccess)
    {
        $this->ssoDiscordTokenAccess = $ssoDiscordTokenAccess;
        
        return $this;
    }
    
    public function getSsoDiscordTokenRefresh(): string
    {
        return $this->ssoDiscordTokenRefresh;
    }
    
    public function setSsoDiscordTokenRefresh(string $ssoDiscordTokenRefresh)
    {
        $this->ssoDiscordTokenRefresh = $ssoDiscordTokenRefresh;
        
        return $this;
    }
}
