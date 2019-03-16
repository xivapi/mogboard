<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
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
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $session;
    /**
     * @var string
     * The token provided by the SSO provider
     * @ORM\Column(type="text", length=512, nullable=true)
     */
    private $token;
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
    private $avatar;
    /**
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user")
     */
    private $alerts;
    /**
     * @ORM\OneToMany(targetEntity="UserList", mappedBy="user")
     */
    private $lists;
    /**
     * @ORM\OneToMany(targetEntity="UserReport", mappedBy="user")
     */
    private $reports;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $patron = false;
    
    public function __construct()
    {
        $this->id       = Uuid::uuid4();
        $this->session  = Uuid::uuid4()->toString() . Uuid::uuid4()->toString() . Uuid::uuid4()->toString();
        $this->alerts   = new ArrayCollection();
        $this->lists    = new ArrayCollection();
        $this->reports  = new ArrayCollection();
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
    
    public function getToken()
    {
        return json_decode($this->token);
    }
    
    public function setToken($token)
    {
        $this->token = $token;
        
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
        $token = $this->getToken();
        
        if (empty($token->avatar) || stripos($this->avatar, 'xivapi.com') !== false) {
            return 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png';
        }
        
        $this->avatar = sprintf("https://cdn.discordapp.com/avatars/%s/%s.png?size=256",
            $token->id,
            $token->avatar
        );
        
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
    
    public function isPatron(): bool
    {
        return $this->patron;
    }
    
    public function setPatron(bool $patron)
    {
        $this->patron = $patron;
        
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
}
