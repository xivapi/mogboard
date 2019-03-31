<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Entity\UserAlertEvents;
use App\Service\Common\Mog;
use App\Service\Companion\Companion;
use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use App\Service\Redis\Redis;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

/**
 * todo - optimise and refactor this, a lot of code duplication
 * todo - email alerts
 * todo - NQ/HQ checking
 */
class UserAlertsTriggers
{
    const TIME_FORMAT = 'F j, Y, g:i a';
    const MAX_TRIGGERS_PER_ALERT = 5;

    /** @var EntityManagerInterface */
    private $em;
    /** @var UserAlertsTriggersLogic */
    private $logic;
    /** @var UserAlerts */
    private $userAlerts;
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
        UserAlertsTriggersLogic $userAlertsTriggersLogic,
        UserAlerts $userAlerts,
        Companion $companion,
        GameDataSource $gamedata
    ) {
        $this->em           = $em;
        $this->logic        = $userAlertsTriggersLogic;
        $this->userAlerts   = $userAlerts;
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
        
        /** @var UserAlert $alert */
        foreach ($alerts as $alert) {
            $this->console->writeln("- Alert: <comment>{$alert->getName()}</comment> by <info>{$alert->getUser()->getUsername()}</info>");
            $this->console->writeln("--> Trigger Condition: <comment>{$alert->getTriggerOptionFormula()}</comment>");
            $this->console->writeln("--> Communication: <comment>". ($alert->isNotifiedViaDiscord() ? 'Discord' : 'None') ."</comment>");
            
            // if its null, ignore
            if ($alert->getTriggerValue() === null) {
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
                $patronTimeout = time() - UserAlert::PATRON_UPDATE_TIME;
                foreach ($market as $server => $marketData) {
                    // if out of date, request update
                    if ($marketData->Updated < $patronTimeout) {
                        $this->console->writeln('--> Requesting manual update');
                        $this->xivapi->market->manualUpdateItem(getenv('XIVAPI_COMPANION_KEY'), $alert->getItemId(), $alert->getServer());
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

            if ($alert->getTriggersSent() > $alert->getTriggerLimit()) {
                $this->console->writeln('--> This trigger has exceeded its limit');
                unset($market);
                continue;
            }
            
            // check triggers
            $this->console->writeln("--> Checking Triggers");
            foreach ($market as $server => $data) {
                // \App\Entity\UserAlert::TRIGGERS
                switch($alert->getTriggerOption()) {
                    case 100:
                    case 110:
                    case 120:
                        $this->triggerPricePerUnit($server, $alert, $data->Prices);
                        break;
    
                    case 200:
                    case 210:
                    case 220:
                        $this->triggerPriceTotal($server, $alert, $data->Prices);
                        break;
    
                    case 300:
                    case 310:
                    case 320:
                        $this->triggerSingleStockQuantity($server, $alert, $data->Prices);
                        break;
    
                    case 400:
                    case 410:
                    case 420:
                        $this->triggerTotalStockQuantity($server, $alert, $data->Prices);
                        break;
                        
                    case 600:
                    case 700:
                    case 800:
                        $this->triggerNameMatches($server, $alert, $data->Prices, $data->History);
                        break;
                }
            }
    
            // if alerts, send them
            if ($this->triggered) {
                $alert
                    ->incrementTriggersSent()
                    ->setTriggerLastSent(time());
                
                $event = new UserAlertEvents();
                $event
                    ->setUserId($alert->getUser()->getId())
                    ->setUserAlert($alert)
                    ->setData($this->triggered);
    
                $this->em->persist($alert);
                $this->em->persist($event);
                $this->em->flush();
                
                // grab item
                $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");
                
                // send discord msg
                // todo - this should be optional
                
                $triggers = implode("\n- ", $this->triggered);
                $message = "```markdown\n";
                $message .= "# MOGBOARD TRIGGER: {$alert->getName()} - {$alert->getTriggerOptionFormula()} \n";
                $message .= "# Item: [{$item->ID}] {$item->Name_en} - {$item->ItemSearchCategory->Name_en} \n\n";
                $message .= "- {$triggers}\n";
                $message .= "``` ";
                $message .= "https://beta.mogboard.com/market/{$item->ID}";

                Mog::aymeric($message, $alert->getUser()->getSsoDiscordId());
                
                // todo - implement email
    
                // reset
                $this->triggered = [];
            } else {
                $this->console->writeln("--> No triggers to send");
            }
        }
    }
    
    private function atMaxTriggers()
    {
        return count($this->triggered) >= self::MAX_TRIGGERS_PER_ALERT;
    }
    

    
    /**
     * Price Per Unit triggers
     */
    private function triggerPricePerUnit(string $server, UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }

            if ($this->logic->handleTriCondition([100, 110, 120], $option, $price->PricePerUnit, $value)) {
                $this->formatPricePerUnit($server, $price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerPricePerUnit
     */
    private function formatPricePerUnit(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(PRICE PER UNIT)(%s) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
            $server,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            $price->RetainerName,
            $this->gamedata->getTown($price->TownID)['Name'],
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
    
        $this->triggered[] = $string;
    }
    
    /**
     * Price Total triggers
     */
    private function triggerPriceTotal(string $server, UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }

            if ($this->logic->handleTriCondition([200, 210, 220], $option, $price->PriceTotal, $value)) {
                $this->formatPriceTotal($server, $price);

                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerPricePerUnit
     */
    private function formatPriceTotal(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(PRICE TOTAL)(%s) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
            $server,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            $price->RetainerName,
            $this->gamedata->getTown($price->TownID)['Name'],
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
        
        $this->triggered[] = $string;
    }
    
    /**
     * Single stock Quantity
     */
    private function triggerSingleStockQuantity(string $server, UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }

            if ($this->logic->handleTriCondition([300, 310, 320], $option, $price->Quantity, $value)) {
                $this->formatSingleStockQuantity($server, $price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerSingleStockQuantity
     */
    private function formatSingleStockQuantity(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(SINGLE STOCK QUANTITY)(%s) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
            $server,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            $price->RetainerName,
            $this->gamedata->getTown($price->TownID)['Name'],
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
        
        $this->triggered[] = $string;
    }
    
    /**
     * Total stock Quantity
     */
    private function triggerTotalStockQuantity(string $server, UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        $totalStock = 0;
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            $totalStock += $price->Quantity;
        }

        if ($this->logic->handleTriCondition([400, 410, 420], $option, $totalStock, $value)) {
            $this->formatTotalStockQuantity($server, $totalStock);
            return;
        }
    }
    
    /**
     * Format the text for triggerTotalStockQuantity
     */
    private function formatTotalStockQuantity(string $server, int $totalStock)
    {
        // build visual string
        $string = sprintf(
            "(TOTAL STOCK QUANTITY)(%s) Qty: %s",
            $server,
            $totalStock
        );
        
        $this->triggered[] = $string;
    }
    
    /**
     * Price Name match triggers
     */
    private function triggerNameMatches(string $server, UserAlert $userAlert, array $prices, array $history)
    {
        $option = $userAlert->getTriggerOption();
        $value  = strtolower($userAlert->getTriggerValue());
    
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            if ($option === 600 && strtolower($price->RetainerName) == $value) {
                $this->formatRetainerNameMatch($server, $price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
            
            if ($option === 800 && strtolower($price->CreatorSignatureName) == $value) {
                $this->formatCreatorSignatureNameMatch($server, $price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
        
        foreach ($history as $event) {
            // skip incorrect quality checks
            if ($this->logic->isCorrectQuality($userAlert, $event) === false) {
                continue;
            }
            
            // we only care about purchases AFTER the alert was created.
            $withinTime = $event->PurchaseDate > $userAlert->getAdded();
            
            if ($option === 700 && $withinTime && strtolower($event->CharacterName) == $value) {
                $this->formatCharacterBuyerNameMatch($server, $event);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerNameMatches
     */
    private function formatRetainerNameMatch(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(RETAINER NAME MATCH)(%s) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
            $server,
            $price->RetainerName,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            $price->RetainerName,
            $this->gamedata->getTown($price->TownID)['Name'],
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
        
        $this->triggered[] = $string;
    }
    
    /**
     * Format the text for triggerNameMatches
     */
    private function formatCreatorSignatureNameMatch(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(CREATOR SIGNATURE NAME)(%s) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
            $server,
            $price->CreatorSignatureName,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            $price->RetainerName,
            $this->gamedata->getTown($price->TownID)['Name'],
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
        
        $this->triggered[] = $string;
    }
    
    /**
     * Format the text for triggerNameMatches
     */
    private function formatCharacterBuyerNameMatch(string $server, \stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(CHARACTER BUYER)(%s) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Date Purchased: %s UTC - Date Added: %s UTC",
            $server,
            $price->CharacterName,
            $price->PricePerUnit,
            $price->PriceTotal,
            $price->Quantity,
            $price->IsHQ ? 'Yes' : 'No',
            Carbon::createFromTimestamp($price->PurchaseDate)->format(self::TIME_FORMAT),
            Carbon::createFromTimestamp($price->Added)->format(self::TIME_FORMAT)
        );
        
        $this->triggered[] = $string;
    }
}
