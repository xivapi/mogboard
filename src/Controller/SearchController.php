<?php

namespace App\Controller;

use App\Resources\Resources;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends Controller
{
    /**
     * @Route("/item/category/list/{categoryId}", name="item_category_list")
     */
    public function index($categoryId)
    {
        return $this->render('Search/item_category_list.html.twig', [
            'category'  => Resources::load('ItemSearchCategoriesByID')[$categoryId],
            'items'     => Resources::load("ItemsWithinSearchCategory_{$categoryId}"),
        ]);
    }
}
