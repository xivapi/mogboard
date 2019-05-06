<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use XIV\Constants\PatreonConstants;
use XIV\User\User as CommonUser;

class User extends CommonUser
{
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
    
    // Alerts ----------------------------------------------------------------------------------------------------------

    /**
     * @ORM\OneToMany(targetEntity="UserAlert", mappedBy="user")
     */
    private $alerts;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsMax = PatreonConstants::DEFAULT_MAX;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsMaxNotifications = PatreonConstants::DEFAULT_MAX_NOTIFY;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsNotificationCount = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsNotifyTimeout = PatreonConstants::DEFAULT_TIMEOUT;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $alertsExpiry = PatreonConstants::DEFAULT_EXPIRY;

    public function __construct()
    {
        parent::__construct();
        
        $this->alerts     = new ArrayCollection();
        $this->lists      = new ArrayCollection();
        $this->reports    = new ArrayCollection();
        $this->characters = new ArrayCollection();
        $this->retainers  = new ArrayCollection();
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
    
    public function getPatreonTierNumber(): ?int
    {
        return $this->patron;
    }
    
    public function getPatreonTier(): string
    {
        return PatreonConstants::PATREON_TIERS[$this->patron] ?? null;
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
}
