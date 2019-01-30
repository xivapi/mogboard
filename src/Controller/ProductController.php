<?php

namespace App\Controller;

use App\Services\Cache\Cache;
use App\Services\Companion\Companion;
use App\Services\Companion\CompanionCensus;
use App\Services\GameData\GameServers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /** @var Cache */
    private $cache;
    /** @var Companion */
    private $companion;
    /** @var Companion */
    private $companionCensus;
    
    public function __construct(Cache $cache, Companion $companion, CompanionCensus $companionCensus)
    {
        $this->cache = $cache;
        $this->companion = $companion;
        $this->companionCensus = $companionCensus;
    }
    
    /**
     * @Route("/market/{itemId}", name="product_page")
     */
    public function index(int $itemId)
    {
        $item = $this->cache->get("xiv_Item_{$itemId}") ?: false;
        
        if (!$item) {
            return $this->redirectToRoute('404');
        }
        
        $server     = GameServers::getServer();
        $dc         = GameServers::getDataCenter($server);
        $dcServers  = GameServers::getDataCenterServers($server);
        
        $market = $this->companion->getMultiServer($dcServers, $itemId);
        $census = $this->companionCensus->generate($market);
        
        return $this->render('Product/index.html.twig', [
            'item'     => $item,
            'market'   => $market,
            'census'   => $census,
            'server'   => [
                'name'       => $server,
                'dc'         => $dc,
                'dc_servers' => $dcServers
            ]
        ]);
    }
}
