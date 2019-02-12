<?php

namespace App\Controller;

use App\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /** @var Redis */
    private $cache;
    
    public function __construct(Redis $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * @Route("/item/category/list/{categoryId}", name="item_category_list")
     */
    public function index($categoryId)
    {
        return $this->render('Search/item_category_list.html.twig', [
            'category'  => $this->cache->get("xiv_ItemSearchCategory_{$categoryId}"),
            'items'     => $this->cache->get("mog_ItemSearchCategory_{$categoryId}_Items"),
        ]);
    }
}
