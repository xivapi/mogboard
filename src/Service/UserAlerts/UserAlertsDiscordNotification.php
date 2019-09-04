<?php

namespace App\Service\UserAlerts;

use App\Common\Entity\UserAlert;
use App\Common\Service\Redis\Redis;
use App\Common\ServicesThirdParty\Discord\Discord;
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
        
        Discord::mog()->sendDirectMessage($alert->getUser()->getSsoDiscordId(), null, $embed);
    }
}
