<?php

namespace App\Controller;

use App\Service\GameData\GameDataSource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /** @var GameDataSource */
    private $gameDataSource;
    
    public function __construct(GameDataSource $gameDataSource)
    {
        $this->gameDataSource = $gameDataSource;
    }
    
    /**
     * @Route("/item/category/list/{categoryId}", name="item_category_list")
     */
    public function index($categoryId)
    {
        return $this->render('Search/item_category_list.html.twig', [
            'category'  => $this->gameDataSource->getItemSearchCategories($categoryId),
            'items'     => $this->gameDataSource->getItemSearchCategoryItems($categoryId),
        ]);
    }
}
