<?php

namespace App\Service\Common;

use Postmark\PostmarkClient;

class Mail
{
    public static function send($to)
    {
        $client = new PostmarkClient(getenv('POSTMARK_KEY'));
    
        $sendResult = $client->sendEmail(
            "sender@example.org",
            "josh@viion.co.uk",
            "Hello from Postmark!",
            "This is just a friendly 'hello' from your friends at Postmark."
        );
    }
}
