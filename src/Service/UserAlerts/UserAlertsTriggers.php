<?php

namespace App\Service\UserAlerts;

use App\Entity\User;
use App\Entity\UserAlert;
use App\Entity\UserAlertEvents;
use App\Service\Common\Mog;
use App\Service\GameData\GameServers;
use App\Service\Redis\Redis;
use Carbon\Carbon;

/**
 * todo - optimise and refactor this, a lot of code duplication
 * todo - email alerts
 * todo - NQ/HQ checking
 */
class UserAlertsTriggers extends UserAlerts
{
    const TIME_FORMAT = 'F j, Y, g:i a';
    const MAX_TRIGGERS_PER_ALERT = 5;
    
    /**
     * @var array[]
     */
    private $triggered = [];
    
    /**
     * Trigger alerts, intended to be called from commands
     */
    public function trigger(bool $patrons = false)
    {
        $this->console->writeln("Triggering Alerts");
    
        // grab all alerts
        $alerts = $this->getAllByPatronStatus($patrons);
        
        /** @var UserAlert $alert */
        foreach ($alerts as $alert) {
            $this->console->writeln("- Alert: <comment>{$alert->getName()}</comment> by <info>{$alert->getUser()->getUsername()}</info>");
            $this->console->writeln("--> Trigger Condition: <comment>{$alert->getTriggerOptionFormula()}</comment>");
            $this->console->writeln("--> Communication: <comment>". ($alert->isNotifiedViaDiscord() ? 'Discord' : 'None') ."</comment>");
            
            // check if the alert has been sent recently, wait the delay
            $timeout = $alert->getTriggerLastSent() + $alert->getTriggerDelay();
            if ($timeout > time()) {
                $this->console->writeln('--> Skipping as alert was triggered recently.');
                continue;
            }
            
            if ($alert->getTriggersSent() > $alert->getTriggerLimit()) {
                $this->console->writeln('--> This trigger has exceeded its limit');
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
            
            // check triggers
            $this->console->writeln("--> Checking Triggers");
            foreach ($market as $server => $data) {
                // \App\Entity\UserAlert::TRIGGERS
                switch($alert->getTriggerOption()) {
                    case 100:
                    case 110:
                    case 120:
                        $this->triggerPricePerUnit($alert, $data->Prices);
                        break;
    
                    case 200:
                    case 210:
                    case 220:
                        $this->triggerPriceTotal($alert, $data->Prices);
                        break;
    
                    case 300:
                    case 310:
                    case 320:
                        $this->triggerSingleStockQuantity($alert, $data->Prices);
                        break;
    
                    case 400:
                    case 410:
                    case 420:
                        $this->triggerTotalStockQuantity($alert, $data->Prices);
                        break;
                        
                    case 600:
                    case 700:
                    case 800:
                        $this->triggerNameMatches($alert, $data->Prices, $data->History);
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
                $message .= "```";

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
    
    private function isCorrectQuality(UserAlert $userAlert, $price)
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
    
    /**
     * Price Per Unit triggers
     */
    private function triggerPricePerUnit(UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            if (
                $option === 100 && $price->PricePerUnit > $value ||
                $option === 110 && $price->PricePerUnit < $value ||
                $option === 120 && $price->PricePerUnit == $value
            ) {
                $this->formatPricePerUnit($price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerPricePerUnit
     */
    private function formatPricePerUnit(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(PRICE PER UNIT) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
    private function triggerPriceTotal(UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            if (
                $option === 200 && $price->PriceTotal > $value ||
                $option === 210 && $price->PriceTotal < $value ||
                $option === 220 && $price->PriceTotal == $value
            ) {
                $this->formatPriceTotal($price);
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerPricePerUnit
     */
    private function formatPriceTotal(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(PRICE TOTAL) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
    private function triggerSingleStockQuantity(UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            if (
                $option === 300 && $price->PriceTotal > $value ||
                $option === 310 && $price->PriceTotal < $value ||
                $option === 320 && $price->PriceTotal == $value
            ) {
                $this->formatSingleStockQuantity($price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerSingleStockQuantity
     */
    private function formatSingleStockQuantity(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(SINGLE STOCK QUANTITY) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
    private function triggerTotalStockQuantity(UserAlert $userAlert, array $prices)
    {
        $option = $userAlert->getTriggerOption();
        $value  = (int)$userAlert->getTriggerValue();
        
        $totalStock = 0;
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            $totalStock += $price->Quantity;
        }
    
        if (
            $option === 400 && $totalStock > $value ||
            $option === 410 && $totalStock < $value ||
            $option === 420 && $totalStock == $value
        ) {
            $this->formatTotalStockQuantity($price);
            return;
        }
    }
    
    /**
     * Format the text for triggerTotalStockQuantity
     */
    private function formatTotalStockQuantity(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(TOTAL STOCK QUANTITY) Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
     * Price Name match triggers
     */
    private function triggerNameMatches(UserAlert $userAlert, array $prices, array $history)
    {
        $option = $userAlert->getTriggerOption();
        $value  = strtolower($userAlert->getTriggerValue());
    
        foreach ($prices as $price) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $price) === false) {
                continue;
            }
            
            if ($option === 600 && strtolower($price->RetainerName) == $value) {
                $this->formatRetainerNameMatch($price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
            
            if ($option === 800 && strtolower($price->CreatorSignatureName) == $value) {
                $this->formatCreatorSignatureNameMatch($price);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
        
        foreach ($history as $event) {
            // skip incorrect quality checks
            if ($this->isCorrectQuality($userAlert, $event) === false) {
                continue;
            }
            
            // we only care about purchases AFTER the alert was created.
            $withinTime = $event->PurchaseDate > $userAlert->getAdded();
            
            if ($option === 700 && $withinTime && strtolower($event->CharacterName) == $value) {
                $this->formatCharacterBuyerNameMatch($event);
                
                if ($this->atMaxTriggers()) {
                    break;
                }
            }
        }
    }
    
    /**
     * Format the text for triggerNameMatches
     */
    private function formatRetainerNameMatch(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(RETAINER NAME MATCH) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
    private function formatCreatorSignatureNameMatch(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(CREATOR SIGNATURE NAME) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Retainer: %s (%s) - Date Added: %s UTC",
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
    private function formatCharacterBuyerNameMatch(\stdClass $price)
    {
        // build visual string
        $string = sprintf(
            "(CHARACTER BUYER) Name: %s - Price: %s (%s) (Qty: %s) - HQ: %s - Date Purchased: %s UTC - Date Added: %s UTC",
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
