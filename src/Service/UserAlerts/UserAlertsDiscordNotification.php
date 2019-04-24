<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
use Carbon\Carbon;

class UserAlertsDiscordNotification
{
    const FOOTER = [
        'text'     => 'mogboard.com',
        'icon_url' => 'https://mogboard.com/favicon.png',
    ];
    
    const ALERT_AUTHOR = [
        'name' => 'Mogboard Alert!',
        'icon_url' => 'https://cdn.discordapp.com/emojis/474543539771015168.png?v=1',
    ];
    
    const COLOR_GREEN  = '#6de258';
    const COLOR_RED    = '#ed493d';
    const COLOR_BLUE   = '#4fc7ff';
    const COLOR_YELLOW = '#edd23c';
    const COLOR_PURPLE = '#c588f7';
    const TIME_FORMAT  = 'F j, Y, g:i a';

    /**
     * Send the saved alert notification
     */
    public function sendSavedAlertNotification(UserAlert $alert)
    {
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");

        $conditions = [];
        foreach($alert->getTriggerConditionsFormatted() as $cond) {
            [$field, $operator, $value, $operatorShort, $operatorLong] = $cond;

            $conditions[] = [
                'name'   => $field,
                'value'  => "`{$operatorLong} {$value}`",
                'inline' => true,
            ];
        }

        $embed = [
            'title'         => "Alert Saved: {$alert->getName()}",
            'description'   => "Your alert for the item: {$item->Name_en} has been saved. You will be alerted via discord when the alert is triggered.\n\n",
            'color'         => hexdec(self::COLOR_GREEN),
            'footer'        => self::FOOTER,
            'fields'        => $conditions,
        ];

        Discord::seraymeric()->sendMessage($alert->getUser()->getSsoDiscordId(), null, $embed);
    }

    /**
     * Send the deleted notification
     */
    public function sendDeletedAlertNotification(UserAlert $alert)
    {
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");

        $embed = [
            'title'         => "Alert Deleted: {$alert->getName()}",
            'description'   => "Your alert for the item: {$item->Name_en} has been deleted.",
            'color'         => hexdec(self::COLOR_RED),
            'footer'        => self::FOOTER,
        ];

        Discord::seraymeric()->sendMessage($alert->getUser()->getSsoDiscordId(), null, $embed);
    }
    
    /**
     * Send a notification regarding triggers
     */
    public function sendAlertTriggerNotification(UserAlert $alert, array $triggeredMarketRows, string $hash)
    {
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");

        $fields = [];
        foreach($triggeredMarketRows as $i => $marketRow) {
            [$server, $row] = $marketRow;
            
            $name = sprintf(
                "%sx %s Gil - Total: %s",
                number_format($row->Quantity),
                number_format($row->PricePerUnit),
                number_format($row->PriceTotal),
                $row->IsHQ ? 'HQ' : 'NQ',
                $alert->getTriggerType() == 'Prices' ? $row->RetainerName : $row->CharacterName
            );
            
            $purchaseDate = null;
            if ($alert->getTriggerType() == 'History') {
                $carbon = Carbon::createFromTimestamp($row->PurchaseDate);
                $purchaseDate = $carbon->fromNow();
            }
    
            $value = sprintf(
                "%s - %s - %s",
                "({$server})",
                $alert->getTriggerType() == 'Prices' ? "Retainer: {$row->RetainerName}" : "Buyer: {$row->CharacterName}",
                $alert->getTriggerType() == 'Prices' ? "Signature: {$row->CreatorSignatureName}" : "Purchased: {$purchaseDate}"
            );

            $fields[] = [
                'name'  => $name,
                'value' => $value,
                'inline' => false,
            ];
        }
        
        // print trigger conditions
        /*
        $triggers = [];
        foreach($alert->getTriggerConditionsFormatted() as $trigger) {
            [$field, $op, $value, $operatorShort, $operatorLong] = $trigger;
            [$type, $field] = explode('_', $field);
            
            if (empty($triggers)) {
                $triggers[] = "Trigger Conditions ($type):";
            }
            
            $triggers[] = "- {$field} {$operatorLong} {$value}";
        }
        $triggers = "```". implode("\n", $triggers) ."```";
        */
        
        // modify footer
        $footer = self::FOOTER;
        $footer['text'] = "{$footer['text']} - Alert ID: {$alert->getUniq()} - {$hash}";
        
        // build embed
        $embed = [
            'author'        => self::ALERT_AUTHOR,
            'title'         => $alert->getName(),
            'description'   => "The item: **{$item->Name_en}** has triggered ". count($triggeredMarketRows) ." market alerts under the type: {$alert->getTriggerType()}.\n ",
            'url'           => getenv('SITE_CONFIG_DOMAIN') . "/market/{$item->ID}",
            'color'         => hexdec(self::COLOR_YELLOW),
            'footer'        => $footer,
            'thumbnail'     => [ 'url' => "https://xivapi.com{$item->Icon}" ],
            'fields'        => $fields,
        ];
        
        Discord::seraymeric()->sendMessage($alert->getUser()->getSsoDiscordId(), null, $embed);
    }
}
