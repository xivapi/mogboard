<?php

namespace App\Controller;

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
    /** @var Users */
    private $users;
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct(
        ItemPopularity $itemPopularity,
        Users $users
    ) {
        $this->itemPopularity   = $itemPopularity;
        $this->users            = $users;
        $this->xivapi           = new XIVAPI();
    }
    
    /**
     * @Route("/", name="home")
     */
    public function home(Request $request)
    {
        $this->users->setLastUrl($request);
    
        /**
         * Market Statistics
         * todo - this should have a service
         */
        $marketStats = Redis::Cache()->get('mogboard_market_statistics');
        if (true || $marketStats == null) {
            $marketStats = $this->xivapi->market->stats();
            $marketStats->Stats->Report = (array)$marketStats->Stats->Report;
            Redis::Cache()->set('mogboard_market_statistics', $marketStats);
        }

        return $this->render('Pages/home.html.twig',[
            'popular_items' => $this->itemPopularity->get(),
            'market_stats'  => $marketStats,
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
