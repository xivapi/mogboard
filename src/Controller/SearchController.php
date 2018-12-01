<?php

namespace App\Controller;

use App\Services\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /** @var Cache */
    private $cache;
    
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * @Route("/item/category/list/{categoryId}", name="item_category_list")
     */
    public function index($categoryId)
    {
        return $this->render('Search/item_category_list.html.twig', [
            'category'  => $this->cache->get("mog_ItemSearchCategory_{$categoryId}"),
            'items'     => $this->cache->get("mog_ItemSearchCategory_{$categoryId}_Items"),
        ]);
    }
}
