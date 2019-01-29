<?php

namespace App\Controller;

use App\Services\Cache\Cache;
use App\Services\Companion\Companion;
use App\Services\GameData\GameDataServers;
use Delight\Cookie\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /** @var Cache */
    private $cache;
    /** @var Companion */
    private $companion;
    
    public function __construct(Cache $cache, Companion $companion)
    {
        $this->cache = $cache;
        $this->companion = $companion;
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
        
        $server     = Cookie::get('server');
        $dc         = GameDataServers::getDataCenter($server);
        $dcServers  = GameDataServers::getDataCenterServers($server);
        
        $market = $this->companion->getMultiServer($dcServers, $itemId);
        
        return $this->render('Product/index.html.twig', [
            'item'     => $item,
            'market'   => $market,
            'server'   => [
                'name'       => $server,
                'dc'         => $dc,
                'dc_servers' => $dcServers
            ]
        ]);
    }
}
