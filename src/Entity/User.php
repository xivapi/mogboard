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
    const PATREON_BENEFIT    = 9;
    const PATREON_DPS        = 4;
    const PATREON_HEALER     = 3;
    const PATREON_TANK       = 2;
    const PATREON_ADVENTURER = 1;
    
    const ALERTS_MAX = 10;
    const ALERTS_MAX_BENEFIT = 20;
    const ALERTS_MAX_PATREON = 50;
    
    const ALERT_EXPIRY_TIMEOUT = (60 * 60 * 24 * 7);
    const ALERT_EXPIRY_TIMEOUT_PATREON = (60 * 60 * 24 * 45);
    
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
     */
    private $retainers;
    
    // -- alerts
    /**
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user")
     */
    private $alerts;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsMax = self::ALERTS_MAX;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsExpiry = self::ALERT_EXPIRY_TIMEOUT;
    
    // -- discord sso
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

    public function getAlerts()
    {
        return $this->alerts;
    }

    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;

        return $this;
    }

    public function addAlert(UserAlert $alert)
    {
        $this->alerts[] = $alert;
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
    
    public function getAlertsMax(): int
    {
        return $this->alertsMax;
    }
    
    public function setAlertsMax(int $alertsMax)
    {
        $this->alertsMax = $alertsMax;
        
        return $this;
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
    public function getListsPersonal()
    {
        $lists = [];
        
        /** @var UserList $list */
        foreach ($this->lists as $list) {
            if ($list->isFavourite()) {
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
            if ($list->isFavourite() && $list->hasItem($itemId)) {
                return true;
            }
        }
        
        return false;
    }

    public function getCharacters()
    {
        return $this->characters;
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
    
    public function setSsoDiscordAvatar(string $ssoDiscordAvatar)
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
