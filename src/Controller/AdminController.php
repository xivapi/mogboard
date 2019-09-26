<?php

namespace App\Controller;

use App\Common\Service\Redis\RedisTracking;
use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Mailgun\Mailgun;

class AdminController extends AbstractController
{
    /** @var Users */
    private $users;
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
    
    /**
     * @Route("/admin")
     */
    public function admin()
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
    
        return $this->render('Admin/statistics.html.twig');
    }
    
    /**
     * @Route("/admin/tracking_stats")
     */
    public function adminTrackingStats()
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
        
        $report = RedisTracking::get();
        $report = (Array)$report;
        ksort($report);
        
        return new Response(
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * @Route("/admin/tracking_stats_reset")
     */
    public function adminTrackingStatsReset()
    {
        $user = $this->users->getUser(true);
        $user->mustBeAdmin();
        
        RedisTracking::reset();
        return $this->json(true);
    }

    /**
     * @Route("/admin/test_discord")
     */
    public function adminTestDiscord()
    {
        // build embed
        $embed = [
            'author'        => 'Universalis Professional Crafter Alerts',
            'title'         => 'ass',
            'description'   => "alert happened\n ",
            'url'           => getenv('SITE_CONFIG_DOMAIN') . "/market/0",
            'color'         => hexdec('#edd23c'),
            'thumbnail'     => [ 'url' => "https://xivapi.com/i2/ls/4815.png" ],
        ];
        
        Discord::mog()->sendDirectMessage(123830058426040321, $embed);
    }

    /**
     * @Route("/admin/test_mail")
     */
    public function adminTestMail()
    {
        // send
        $client = Mailgun::create(getenv('MAILGUN_KEY'));
        $client->messages()->send(getenv('MAILGUN_DOMAIN'), array(
            'from'	=> 'Universalis Alerts <alerts@universalis.app>',
            'to'	=> '',
            'subject' => 'ass',
            'text'	=> 'hello'
        ));
    }
}
