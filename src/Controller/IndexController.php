<?php

namespace App\Controller;

use App\Service\Common\Mail;
use App\Service\Companion\CompanionStatistics;
use App\Service\Items\ItemPopularity;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
use App\Service\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

class IndexController extends AbstractController
{
    /** @var ItemPopularity */
    private $itemPopularity;
    /** @var CompanionStatistics */
    private $companionStatistics;
    /** @var Users */
    private $users;
    /** @var Mail */
    private $mail;
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct(
        ItemPopularity $itemPopularity,
        CompanionStatistics $companionStatistics,
        Users $users,
        Mail $mail
    ) {
        $this->itemPopularity      = $itemPopularity;
        $this->companionStatistics = $companionStatistics;
        $this->users               = $users;
        $this->mail                = $mail;
        $this->xivapi              = new XIVAPI();
    }
    
    /**
     * @Route("/", name="home")
     */
    public function home(Request $request)
    {
        $this->users->setLastUrl($request);
        
        return $this->render('Pages/home.html.twig',[
            'popular_items' => $this->itemPopularity->get(),
            'market_stats'  => $this->companionStatistics->stats(),
            'server_status' => $this->xivapi->market->online(),
        ]);
    }
    
    /**
     * @Route("/404", name="404")
     */
    public function fourOfour()
    {
        return $this->render('Pages/404.html.twig');
    }
    
    /**
     * @Route("/news", name="news")
     */
    public function news()
    {
        return $this->render('Pages/news.html.twig');
    }
    
    /**
     * @Route("/patreon", name="patreon")
     */
    public function patreon()
    {
        return $this->render('Pages/patreon.html.twig', [
            'user_patrons' => $this->users->getPatrons()
        ]);
    }
    
    /**
     * @Route("/patreon/refund", name="patreon_refund")
     */
    public function patreonRefund()
    {
        return $this->render('Pages/patreon_refund.html.twig');
    }
    
    /**
     * @Route("/patreon/refund/request", name="patreon_refund_process")
     */
    public function patreonRefundProcess(Request $request)
    {
        $name = trim($request->get('name'));
        $user = $this->users->getUser();
        
        $this->mail->send(
            'josh@viion.co.uk',
            'Patreon Refund Request',
            'Emails/patreon_refund.html.twig',
            [
                'name_or_email' => $name,
                'id' => $user->getSsoDiscordId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'tier' => $user->getPatreonTier()
            ]
        );
    
        return $this->redirectToRoute('patreon_refund', [
            'complete' => 1
        ]);
    }
    
    /**
     * @Route("/feedback", name="feedback")
     */
    public function feedback(Request $request)
    {
        $sent = $request->getSession()->get('feedback_sent');
        $request->getSession()->remove('feedback_sent');
        
        return $this->render('Pages/feedback.html.twig', [
            'feedback_sent' => $sent
        ]);
    }
    
    /**
     * @Route("/feedback/send", name="feedback_send")
     */
    public function feedbackSubmit(Request $request)
    {
        $message = trim($request->get('feedback_message'));
        $message = substr($message, 0, 1000);
        $user    = $this->users->getUser(false);
    
        $request->getSession()->set('feedback_sent', 'no');
    
        if (strtolower($request->get('ted')) !== 'ffxiv') {
            return $this->redirectToRoute('feedback');
        }
        
        if (strlen($message) == 0) {
            return $this->redirectToRoute('feedback');
        }
        
        $key   = 'mb_feedback_client_'. md5($request->getClientIp());
        $count = Redis::Cache()->get($key) ?: 0;
        $count = $count + 1;
        
        if ($count > 10) {
            return $this->redirectToRoute('feedback');
        }
    
        Redis::Cache()->set($key, $count);
        $request->getSession()->set('feedback_sent', 'yes');
    
        $embed = [
            'title'         => "Mogboard Feedback",
            'description'   => $message,
            'color'         => hexdec('c588f7'),
            'fields'        => [
                [
                    'name'   => 'User',
                    'value'  => $user ? "{$user->getUsername()} ({$user->getEmail()})" : "Not online",
                    'inline' => true,
                ]
            ],
        ];
        
        Discord::mog()->sendMessage('477631558317244427', null, $embed);
        
        return $this->redirectToRoute('feedback');
    }
    
    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('Pages/about.html.twig', [
            'market_stats'    => $this->companionStatistics->stats(),
        ]);
    }
    
    /**
     * @Route("/server-status", name="server_status")
     */
    public function serverStatus()
    {
        $status  = $this->xivapi->market->online();
        $offline = $status->Offline;
        $list    = [];
        
        foreach ($status->Status as $i => $serverStatus) {
            $list[$serverStatus->Server] = $serverStatus;
        }
        
        return $this->render('Pages/servers.html.twig',[
            'servers_status'  => $list,
            'servers_offline' => $offline
        ]);
    }
}
