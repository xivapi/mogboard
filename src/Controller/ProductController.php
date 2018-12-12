<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Services\Cache\Cache;
use App\Services\Companion\Companion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
            throw new NotFoundHttpException();
        }

        return $this->render('Product/item.html.twig', [
            'triggers' => Alert::getTriggers(),
            'item'     => $item,
        ]);
    }
    
    /**
     * @Route("/market/{server}/{itemId}/prices", name="product_price")
     */
    public function prices(string $server, int $itemId)
    {
        $prices = $this->companion->getItemPrices($server, $itemId);
        $stats  = $this->companion->getItemPriceStats($prices->Prices);
        
        return $this->render('Product/prices.html.twig', [
            'prices' => $prices,
            'stats'  => $stats,
        ]);
    }
    
    /**
     * @Route("/market/{server}/{itemId}/history", name="product_history")
     */
    public function history(string $server, int $itemId)
    {
        $history = $this->companion->getItemHistory($server, $itemId);
        $stats   = $this->companion->getItemHistoryStats($history->History);
        
        return $this->render('Product/history.html.twig', [
            'history' => $history,
            'stats'   => $stats,
        ]);
    }
    
    /**
     * @Route("/market/{server}/{itemId}/prices/cross-world", name="product_cross_world")
     */
    public function pricesCrossWorld(string $server, int $itemId)
    {
        [$prices, $dc, $servers, $duration] = $this->companion->getItemPricesCrossWorld($server, $itemId);
        [$stats, $statsOverall]  = $this->companion->getItemPricesCrossWorldStats($servers, $prices);

        return $this->render('Product/cross-world.html.twig', [
            'prices'        => json_decode(json_encode($prices), true),
            'stats'         => json_decode(json_encode($stats), true),
            'statsOverall'  => $statsOverall,
            'dc'            => $dc,
            'servers'       => $servers,
            'server'        => $server,
            'duration'      => $duration,
        ]);
    }
}
