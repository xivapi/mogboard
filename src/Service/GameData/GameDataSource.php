<?php

namespace App\Service\GameData;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Language;

class GameDataSource
{
    public function getItem(int $itemId)
    {
        return $this->handle("xiv_Item_{$itemId}");
    }
    
    public function getRecipe(int $recipeId)
    {
        return $this->handle("xiv_Recipe_{$recipeId}");
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
