<?php

namespace App\Service\UserAlerts;

use App\Entity\User;
use App\Entity\UserAlert;
use App\Entity\UserAlertEvent;
use App\Service\Companion\Companion;
use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

/**
 * todo - email alerts
 */
class UserAlertsTriggers
{
    const MAX_TRIGGERS_PER_ALERT = 5;

    /** @var EntityManagerInterface */
    private $em;
    /** @var UserAlerts */
    private $userAlerts;
    /** @var UserAlertsDiscordNotification */
    private $discord;
    /** @var Companion */
    private $companion;
    /** @var GameDataSource */
    private $gamedata;
    /** @var ConsoleOutput */
    private $console;
    /** @var XIVAPI */
    private $xivapi;
    /** @var array[] */
    private $triggered = [];


    public function __construct(
        EntityManagerInterface $em,
        UserAlertsDiscordNotification $userAlertsDiscordNotification,
        UserAlerts $userAlerts,
        Companion $companion,
        GameDataSource $gamedata
    ) {
        $this->em           = $em;
        $this->userAlerts   = $userAlerts;
        $this->discord      = $userAlertsDiscordNotification;
        $this->companion    = $companion;
        $this->gamedata     = $gamedata;
        $this->console      = new ConsoleOutput();
        $this->xivapi       = new XIVAPI();
    }
    
    /**
     * Trigger alerts, intended to be called from commands
     */
    public function trigger(bool $patrons = false)
    {
        $this->console->writeln("Triggering Alerts");
    
        // grab all alerts
        $alerts = $this->userAlerts->getAllByPatronStatus($patrons);
        $total = count($alerts);
        $this->console->writeln("Total: {$total}");
        
        /** @var UserAlert $alert */
        foreach ($alerts as $alert) {
            $this->console->writeln("- Alert: <comment>{$alert->getName()}</comment> by <info>{$alert->getUser()->getUsername()}</info>");
            
            // if trigger conditions are empty (shouldn't happen), delete it.
            if (empty($alert->getTriggerConditions())) {
                $this->userAlerts->delete($alert, true);
                continue;
            }

            // get user for the alert
            $user = $alert->getUser();

            /**
             * Handle the server for the alert,
             * todo - this should be reverted back to DC once World Visit is available.
             */
            $servers = [ $alert->getServer() ];

            # $dc         = GameServers::getDataCenter($alert->getServer());
            # $dcServers  = GameServers::getDataCenterServers($alert->getServer());
            # $servers    = $alert->isTriggerDataCenter() ? $dcServers : [ $alert->getServer() ];

            /**
             * todo - this should use Companion internally. Look into making the Companion code "common"
             * Fetch the market data from companion
             */
            $this->console->writeln("--> Getting market info");
            $market = $this->companion->getByServers($servers, $alert->getItemId());

            /**
             * DPS patrons get auto-price updating.
             */
            if ($user->isPatron(User::PATREON_DPS)) {
                // Send an update request, XIVAPI handles throttling this.
                $this->console->writeln('--> Requesting manual update');
                $this->xivapi->market->manualUpdateItem(
                    getenv('XIVAPI_COMPANION_KEY'),
                    $alert->getItemId(),
                    $alert->getServer()
                );
            }

            /**
             * Handle alert delay
             * if the notification delay is greater than the current time, we skip
             */
            $alertNotificationDelay = $alert->getTriggerLastSent() + $user->getAlertsNotifyTimeout();
            if ($alertNotificationDelay > time()) {
                $this->console->writeln('--> Skipping: Alert is on notification cooldown.');
                unset($market);
                continue;
            }

            /**
             * Handle max alert notifications.
             * If the user has hit the daily limit, we skip.
             */
            if ($user->isAtMaxNotifications()) {
                $this->console->writeln('--> Skipping: User has reached maximum alerts.');
                unset($market);
                continue;
            }
            
            // loop through data and find a match for this trigger
            $this->console->writeln("--> Checking Triggers: ({$alert->getTriggerType()})");
            foreach ($market as $server => $data) {
                if ($this->atMaxTriggers()) {
                    break;
                }

                /**
                 * Grab the market data
                 */
                $marketDataSet = $data->{$alert->getTriggerType()};
    
                // loop through data
                foreach ($marketDataSet as $marketRow) {
                    /**
                     * if we hit the maximum number of triggers for an individual alert, break
                     */
                    if ($this->atMaxTriggers()) {
                        break;
                    }

                    /**
                     * If the item quality is incorrect, we skip
                     */
                    if ($this->isCorrectQuality($alert, $marketRow) == false) {
                        continue;
                    }

                    /**
                     * If the alert type is a "History" event, we ignore any market
                     * entries prior to when the alert was created
                     */
                    if ($alert->getTriggerType() === 'History' && $marketRow->PurchaseDate < $alert->getAdded()) {
                        continue;
                    }
                    
                    // loop through triggers
                    $triggers = [];
                    foreach ($alert->getTriggerConditions() as $i => $trigger) {
                        [$field, $op, $value] = explode(',', $trigger);
                        [$category, $field]   = explode('_', $field);

                        // grab value for this field
                        $marketValue = $marketRow->{$field};
        
                        // run all trigger tests
                        switch ($op) {
                            case 1:
                                $triggers[$i] = ($marketValue > $value);
                                break;
            
                            case 2:
                                $triggers[$i] = ($marketValue >= $value);
                                break;
            
                            case 3:
                                $triggers[$i] = ($marketValue < $value);
                                break;
            
                            case 4:
                                $triggers[$i] = ($marketValue <= $value);
                                break;
            
                            case 5:
                                $triggers[$i] = ($marketValue == $value);
                                break;
            
                            case 6:
                                $triggers[$i] = ($marketValue != $value);
                                break;
            
                            case 7:
                                $triggers[$i] = (($marketValue % $value) == 0);
                                break;
                        }
                    }
                    
                    // check if the trigger passed
                    if (count($triggers) == array_sum($triggers)) {
                        $this->triggered[] = [
                            $server,
                            $marketRow
                        ];
                    }
                }
            }
    
            // if alerts, send them
            if ($this->triggered) {
                $user->incrementNotificationCount();

                $alert
                    ->incrementTriggersSent()
                    ->setTriggerLastSent(time());
                
                $event = new UserAlertEvent();
                $event
                    ->setUserId($alert->getUser()->getId())
                    ->setUserAlert($alert)
                    ->setData($this->triggered);

                $this->em->persist($user);
                $this->em->persist($alert);
                $this->em->persist($event);
                $this->em->flush();

                if ($alert->isNotifiedViaDiscord()) {
                    $this->discord->sendAlertTriggerNotification($alert, $this->triggered);
                }
                
                if ($alert->isNotifiedViaEmail()) {
                    // todo - email logic
                }
                
                // reset
                $this->triggered = [];
            } else {
                $this->console->writeln("--> No triggers to send");
            }
        }
    }
    
    /**
     * Check if we've hit the maximum number of triggers per alert
     */
    private function atMaxTriggers()
    {
        return count($this->triggered) >= self::MAX_TRIGGERS_PER_ALERT;
    }
    
    /**
     * States if a UserAlert and a Price match up with HQ/NQ settings.
     */
    public function isCorrectQuality(UserAlert $userAlert, $price)
    {
        if ($userAlert->isTriggerHq() !== $userAlert->isTriggerNq()) {
            if (
                $userAlert->isTriggerHq() && $price->IsHQ == false ||
                $userAlert->isTriggerNq() && $price->IsHQ == true
            ) {
                return false;
            }
        }
        
        return true;
    }
}
