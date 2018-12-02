<?php

namespace App\Controller;

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
            'item'   => $item,
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
}
