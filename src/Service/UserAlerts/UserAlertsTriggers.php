<?php

namespace App\Service\UserAlerts;

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
            
            //
            // handle server
            //
            $dc         = GameServers::getDataCenter($alert->getServer());
            $dcServers  = GameServers::getDataCenterServers($alert->getServer());
            $servers    = $alert->isTriggerDataCenter() ? $dcServers : [ $alert->getServer() ];
            $this->console->writeln("--> Data Center: <info>{$dc}</info>: ". implode(', ', $servers));
            
            // grab market data
            $this->console->writeln("--> Getting market info");
            $market = $this->companion->getByServers($servers, $alert->getItemId());
            
            // - if this user is a patron user and the prices are older than a few minutes
            //   it will query companion directly.
            if ($patrons) {
                // different patreon tiers get different timeouts
                $patreonTimeoutInSeconds = UserAlert::PATRON_UPDATE_TIME;
                if ($alert->getUser()->isPatron(UserAlert::PATRON_UPDATE_TIME_TIER4)) {
                    $patreonTimeoutInSeconds = UserAlert::PATRON_UPDATE_TIME_TIER4;
                }
                
                $patronTimeout = time() - $patreonTimeoutInSeconds;
                foreach ($market as $server => $marketData) {
                    // if out of date, request update
                    if ($marketData->Updated < $patronTimeout) {
                        // this only needs to request once as it does the whole DC regardless of the alert choice.
                        $this->console->writeln('--> Requesting manual update');
                        $this->xivapi->market->manualUpdateItem(getenv('XIVAPI_COMPANION_KEY'), $alert->getItemId(), $alert->getServer());
                        break;
                    }
                }
            }

            // check if the alert has been sent recently, wait the delay
            $timeout = $alert->getTriggerLastSent() + $alert->getTriggerDelay();
            if ($timeout > time()) {
                $this->console->writeln('--> Skipping as alert was triggered recently.');
                unset($market);
                continue;
            }

            // check if the trigger has exceeded its limit
            if ($alert->getTriggersSent() > $alert->getTriggerLimit()) {
                $this->console->writeln('--> This trigger has exceeded its limit');
                unset($market);
                continue;
            }
            
            // loop through data and find a match for this trigger
            $this->console->writeln("--> Checking Triggers: ({$alert->getTriggerType()})");
            foreach ($market as $server => $data) {
                if ($this->atMaxTriggers()) {
                    break;
                }

                // grab dataset
                $marketDataSet = $data->{$alert->getTriggerType()};
    
                // loop through data
                foreach ($marketDataSet as $marketRow) {
                    if ($this->atMaxTriggers()) {
                        break;
                    }
                    
                    // if quality is wrong, skip
                    if ($this->isCorrectQuality($alert, $marketRow) == false) {
                        continue;
                    }
                    
                    // if alert type is "history", ignore anything from before the alert was created
                    if ($alert->getTriggerType() === 'History' && $marketRow->PurchaseDate < $alert->getAdded()) {
                        continue;
                    }
                    
                    // loop through triggers
                    foreach ($alert->getTriggerConditions() as $i => $trigger) {
                        [$field, $op, $value] = explode(',', $trigger);
                        [$category, $field]   = explode('_', $field);

                        // grab value for this field
                        $marketValue = $marketRow->{$field};
        
                        // output trigger to console
                        #$opName = UserAlert::TRIGGER_OPERATORS_SHORT[$op];
                        #$this->console->writeln("--> <comment>({$category}) {$field}={$marketValue}</comment> {$opName} <info>{$value}</info>");
        
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
                $alert
                    ->incrementTriggersSent()
                    ->setTriggerLastSent(time());
                
                $event = new UserAlertEvent();
                $event
                    ->setUserId($alert->getUser()->getId())
                    ->setUserAlert($alert)
                    ->setData($this->triggered);
    
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
