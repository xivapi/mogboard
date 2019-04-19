<?php

namespace App\Controller;

use App\Service\Companion\CompanionStatistics;
use App\Service\Items\ItemPopularity;
use App\Service\Redis\Redis;
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
     * @Route("/theme", name="theme")
     */
    public function theme()
    {
        return $this->render('Theme/theme.html.twig');
    }
}
