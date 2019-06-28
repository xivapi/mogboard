<?php

namespace App\Controller;

use App\Service\GameData\GameDataSource;
use App\Common\Service\Redis\Redis;
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
     * @Route("/item/category/generate", name="item_category_generate")
     */
    public function generate()
    {
        $categories = Redis::Cache()->get('mog_ItemSearchCategories');
    
        $itemsEn = [];
        $itemsDe = [];
        $itemsFr = [];
        $itemsJa = [];
        
        foreach ($categories as $type => $catz) {
            foreach ($catz as $cat) {
                foreach ($this->gameDataSource->getItemSearchCategoryItems($cat->ID) as $item) {
                    if (!isset($itemsEn[$cat->ID])) {
                        $itemsEn[$cat->ID] = [];
                    }
    
                    if (!isset($itemsDe[$cat->ID])) {
                        $itemsDe[$cat->ID] = [];
                    }
    
                    if (!isset($itemsFr[$cat->ID])) {
                        $itemsFr[$cat->ID] = [];
                    }
    
                    if (!isset($itemsJa[$cat->ID])) {
                        $itemsJa[$cat->ID] = [];
                    }
    
                    $item = Redis::Cache()->get("xiv_Item_{$item->ID}");
                    
                    $itemsEn[$cat->ID][] = [
                        $item->ID,
                        $item->Name_en,
                        $item->Icon,
                        $item->LevelItem,
                        $item->Rarity,
                        $item->ClassJobCategory->Name_en ?? ''
                    ];
    
                    $itemsDe[$cat->ID][] = [
                        $item->ID,
                        $item->Name_de,
                        $item->Icon,
                        $item->LevelItem,
                        $item->Rarity,
                        $item->ClassJobCategory->Name_en ?? ''
                    ];
    
                    $itemsFr[$cat->ID][] = [
                        $item->ID,
                        $item->Name_fr,
                        $item->Icon,
                        $item->LevelItem,
                        $item->Rarity,
                        $item->ClassJobCategory->Name_en ?? ''
                    ];
    
                    $itemsJa[$cat->ID][] = [
                        $item->ID,
                        $item->Name_ja,
                        $item->Icon,
                        $item->LevelItem,
                        $item->Rarity,
                        $item->ClassJobCategory->Name_en ?? ''
                    ];
                }
            }
        }
        
        file_put_contents(
            __DIR__.'/../../public/data/categories_en.js', json_encode($itemsEn)
        );
    
        file_put_contents(
            __DIR__.'/../../public/data/categories_de.js', json_encode($itemsDe)
        );
    
        file_put_contents(
            __DIR__.'/../../public/data/categories_fr.js', json_encode($itemsFr)
        );
    
        file_put_contents(
            __DIR__.'/../../public/data/categories_ja.js', json_encode($itemsJa)
        );
        
        return $this->json(true);
    }
}
