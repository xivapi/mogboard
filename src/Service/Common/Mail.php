<?php

namespace App\Service\Common;

use App\Service\Redis\Redis;
use Postmark\PostmarkClient;
use Twig\Environment;

class Mail
{
    /** @var Environment */
    private $twig;
    
    /**
     * TestTwig constructor.
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }
    
    /**
     * Send an email
     */
    public function send(
        string $email,
        string $subject,
        string $template,
        array $templateVariables
    ) {
        // build html
        $html = $this->twig->render($template, $templateVariables);
        $hash = sha1($html);
        
        // don't send the same email
        if (Redis::Cache()->get("email_spam_reduce_{$hash}")) {
            return;
        }
    
        Redis::Cache()->set("email_spam_reduce_{$hash}", true);
    
        // send
        $client = new PostmarkClient(getenv('POSTMARK_KEY'));
        $client->sendEmail(
            "mog@mogboard.com",
            $email,
            $subject,
            $html
        );
    }
}
