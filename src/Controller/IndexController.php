<?php

namespace App\Controller;

use App\Service\Companion\CompanionStatistics;
use App\Service\Items\ItemPopularity;
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
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct(
        ItemPopularity $itemPopularity,
        CompanionStatistics $companionStatistics,
        Users $users
    ) {
        $this->itemPopularity      = $itemPopularity;
        $this->companionStatistics = $companionStatistics;
        $this->users               = $users;
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
        $status = $this->xivapi->market->online();
        $offline = $status->Offline;
        $list = [];
        
        foreach ($status->Status as $i => $serverStatus) {
            $list[$serverStatus->Server] = $serverStatus;
        }
        
        return $this->render('Pages/servers.html.twig',[
            'servers_status'  => $list,
            'servers_offline' => $offline
        ]);
    }
}
