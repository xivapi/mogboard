<?php

namespace App\Service\Common;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class SerAymeric
{
    /**
     * Send action
     *
     * @param string $endpoint
     * @param array $json
     */
    private static function send(string $endpoint, array $json)
    {
        (new Client())->post('https://mog.xivapi.com/aymeric'. $endpoint, [
            RequestOptions::JSON => $json,
            RequestOptions::QUERY => [
                'key' => getenv('DISCORD_BOT_USAGE_KEY')
            ],
        ]);
    }

    /**
     * Send a message to a user
     */
    public static function sendMessage(string $message, string $userId)
    {
        self::send('/say', [
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    /**
     * Send a embed to a user
     */
    public static function sendEmbed(array $embed, string $userId)
    {
        self::send('/embed', [
            'user_id' => $userId,
            'embed' => $embed,
        ]);
    }
}
