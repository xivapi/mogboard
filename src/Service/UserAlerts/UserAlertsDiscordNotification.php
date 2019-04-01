<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Service\Common\Mog;
use App\Service\Common\SerAymeric;
use App\Service\Redis\Redis;

class UserAlertsDiscordNotification
{
    const FOOTER       = 'mogboard.com';
    const COLOR_GREEN  = '#6de258';
    const COLOR_RED    = '#ed493d';
    const COLOR_BLUE   = '#4fc7ff';
    const COLOR_YELLOW = '#edd23c';
    const COLOR_PURPLE = '#c588f7';

    /**
     * Send the saved alert notification
     */
    public function sendSavedAlertNotification(UserAlert $alert)
    {
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");

        $conditions = [];
        foreach($alert->getTriggerConditionsFormatted() as $cond) {
            [$field, $operator, $value] = $cond;

            $conditions[] = [
                'name'   => $field,
                'value'  => "{$operator} {$value}",
                'inline' => true,
            ];
        }

        $embed = [
            'title'         => "Alert Saved: **{$alert->getName()}",
            'description'   => "Your alert for the item: {$item->Name_en} has been saved.",
            'color'         => self::COLOR_GREEN,
            'footer'        => self::FOOTER,
            'fields'        => $conditions,
        ];

        SerAymeric::sendEmbed($embed, $alert->getUser()->getSsoDiscordId());
    }

    /**
     * Send the deleted notification
     */
    public function sendDeletedAlertNotification(UserAlert $alert)
    {
        $item = Redis::Cache()->get("xiv_Item_{$alert->getItemId()}");

        $embed = [
            'title'         => "Alert Deleted: **{$alert->getName()}",
            'description'   => "Your alert for the item: {$item->Name_en} has been deleted.",
            'color'         => self::COLOR_RED,
            'footer'        => self::FOOTER,
        ];

        SerAymeric::sendEmbed($embed, $alert->getUser()->getSsoDiscordId());
    }
}
