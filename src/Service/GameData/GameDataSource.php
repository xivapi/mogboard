<?php

namespace App\Service\GameData;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Language;
use XIVAPI\XIVAPI;

class GameDataSource
{
    /** @var XIVAPI */
    private $xivapi;

    public function __construct()
    {
        $this->xivapi = new XIVAPI();
    }

    public function getItem(int $itemId)
    {
        $cachedItem = $this->handle("xiv_Item_{$itemId}");
        if ($cachedItem == null) {
            $cachedItem = $this->xivapi->content->Item()->one($itemId);
            
            if ($cachedItem != null) {
                Redis::Cache()->set("xiv_Item_{$itemId}", $cachedItem);
            }
        }
            
        return json_decode(json_encode($cachedItem, FALSE));
    }
    
    public function getRecipe(int $recipeId)
    {
        $cachedRecipe = $this->handle("xiv_Recipe_{$itemId}");
        if ($cachedRecipe == null) {
            $cachedRecipe = $this->xivapi->content->Recipe()->one($itemId);
            
            if ($cachedRecipe != null) {
                Redis::Cache()->set("xiv_Recipe_{$itemId}", $cachedItem);
            }
        }
            
        return json_decode(json_encode($cachedRecipe, FALSE));
    }
    
    public function getWorld(int $worldId)
    {
        return $this->handle("xiv_World_{$townId}");
    }

    public function getTown(int $townId)
    {
        return $this->handle("xiv_Town_{$townId}");
    }
    
    public function getItemSearchCategories(int $categoryId)
    {
        return $this->handle("xiv_ItemSearchCategory_{$categoryId}");
    }
    
    public function getItemSearchCategoryItems(int $categoryId)
    {
        return $this->handle("mog_ItemSearchCategory_{$categoryId}_Items");
    }
    
    private function handle(string $key)
    {
        return Language::handle(
            Redis::Cache()->get($key)
        );
    }
}
