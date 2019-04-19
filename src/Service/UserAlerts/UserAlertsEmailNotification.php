<?php

namespace App\Service\UserAlerts;

use App\Entity\UserAlert;
use App\Service\Common\Mail;
use App\Service\GameData\GameDataSource;
use App\Service\Redis\Redis;

class UserAlertsEmailNotification
{
    /** @var Mail */
    private $mail;
    /** @var GameDataSource */
    private $game;
    
    public function __construct(Mail $mail, GameDataSource $game)
    {
        $this->mail = $mail;
        $this->game = $game;
    }
    
    /**
     * Send a notification regarding triggers
     */
    public function sendAlertTriggerNotification(UserAlert $alert, array $triggeredMarketRows, string $hash)
    {
        $this->mail->send(
            $alert->getUser()->getEmail(),
            "MogBoard Alert: {$alert->getName()}",
            "Emails/item_alert.html.twig",
            [
                'hash'  => $hash,
                'alert' => $alert,
                'item'  => $this->game->getItem($alert->getItemId()),
                'triggeredMarketRows' => $triggeredMarketRows,
            ]
        );
    }
}
