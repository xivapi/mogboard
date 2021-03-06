<?php

namespace App\Controller;

use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\User\Users;
use App\Common\Utils\Mail;
use App\Service\Companion\CompanionMarketActivity;
use App\Service\Companion\CompanionStatistics;
use App\Service\Items\Popularity;
use App\Common\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

class IndexController extends AbstractController
{
    /** @var Popularity */
    private $itemPopularity;
    /** @var CompanionStatistics */
    private $companionStatistics;
    /** @var CompanionMarketActivity */
    private $companionMarketActivity;
    /** @var Users */
    private $users;
    /** @var Mail */
    private $mail;
    
    public function __construct(
        Popularity $itemPopularity,
        CompanionStatistics $companionStatistics,
        CompanionMarketActivity $companionMarketActivity,
        Users $users,
        Mail $mail
    ) {
        $this->itemPopularity           = $itemPopularity;
        $this->companionStatistics      = $companionStatistics;
        $this->companionMarketActivity  = $companionMarketActivity;
        $this->users                    = $users;
        $this->mail                     = $mail;
    }
    
    /**
     * @Route("/", name="home")
     */
    public function home(Request $request)
    {
        $this->users->setLastUrl($request);
        
        // grab the users market feed
        $marketFeed = $this->companionMarketActivity->getFeed($this->users->getUser());
        $marketFeed = json_decode(json_encode($marketFeed), true);
        
        return $this->render('Home/home.html.twig',[
            'market_feed'   => $marketFeed,
            'popular_items' => $this->itemPopularity->get(),
        ]);
    }
    
    /**
     * @Route("/.well-known/acme-challenge/{hash}")
     */
    public function le($hash)
    {
        return $this->json($hash);
    }
    
    /**
     * @Route("/404", name="404")
     */
    public function fourOfour()
    {
        return $this->render('Errors/404.html.twig');
    }

    /**
     * @Route("/error", name="error")
     */
    public function error()
    {
        throw new \Exception("This is a test error");
    }
    
    /**
     * @Route("/news", name="news_index")
     * @Route("/news/{slug}", name="news")
     */
    public function news(?string $slug = null)
    {
        $templates = [
            'manual-updating'                               => '2019_05_05.html.twig',
            'homepage-retainer-lists-and-privacy-changes'   => '2019_05_20.html.twig',
            'updating-and-faq'                              => '2019_06_17.html.twig',
            'thank-you-and-shadow-bringers'                 => '2019_06_23.html.twig',
        ];

        $slug = $slug ?: 'thank-you-and-shadow-bringers';
        
        return $this->render('News/'. $templates[$slug]);
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
        $user = $this->users->getUser(true);
        
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
    
        if (strtoupper($request->get('gil')) !== 'NO') {
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
        
        Discord::mog()->sendMessage('574593645626523669', null, $embed);
        
        return $this->redirectToRoute('feedback');
    }
    
    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        $stats = $this->companionStatistics->stats();
        $stats = json_decode(json_encode($stats), true);

        return $this->render('Pages/about.html.twig', [
            'market_stats' => $stats,
        ]);
    }
    
    /**
     * @Route("/server-status", name="server_status")
     */
    public function serverStatus()
    {
        $xivapi  = new XIVAPI();
        $status  = $xivapi->market->online();
        $list    = [];
        
        foreach ($status->Status as $i => $serverStatus) {
            $list[$serverStatus->Server] = $serverStatus;
        }
        
        return $this->render('Pages/servers.html.twig',[
            'servers_status'  => $list
        ]);
    }
}
