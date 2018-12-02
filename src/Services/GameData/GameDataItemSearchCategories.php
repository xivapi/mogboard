<?php

namespace App\Services\GameData;

use App\Services\Cache\Redis;

class GameDataItemSearchCategories extends GameDataAbstract
{
    const CATEGORY_NAMES = [
        1 => 'weapons',
        2 => 'armor',
        3 => 'items',
        4 => 'housing'
    ];

    public function populate()
    {
        $cache = (new Redis())->connect();
    
        // build redis key list
        $keys = [];
        foreach ($cache->get('ids_ItemSearchCategory') as $i => $id) {
            $keys[$i] = "xiv_ItemSearchCategory_{$id}";
        }
    
        // as there are only 100 item search categories, we will get them all at once:
        $objects    = $cache->getMulti($keys);
        $categories = [];
        
        foreach ($objects as $category) {
            // ignore empty ones
            if (!in_array($category->Category, array_keys(self::CATEGORY_NAMES)) || empty($category->Name_en)) {
                continue;
            }
    
            // store category
            $catName = self::CATEGORY_NAMES[$category->Category];
            $categories[$catName][$category->Order] = [
                'ID'      => $category->ID,
                'Icon'    => $category->Icon,
                'Name_en' => $category->Name_en,
                'Name_de' => $category->Name_de,
                'Name_fr' => $category->Name_fr,
                'Name_ja' => $category->Name_ja,
            ];
            
            // copy category over
            $cache->set("mog_ItemSearchCategory_{$category->ID}", $category, GameData::CACHE_TIME);
        }
    
        ksort($categories['weapons']);
        ksort($categories['armor']);
        ksort($categories['items']);
        ksort($categories['housing']);
    
        $categories['weapons']  = array_values($categories['weapons']);
        $categories['armor']    = array_values($categories['armor']);
        $categories['items']     = array_values($categories['items']);
        $categories['housing']  = array_values($categories['housing']);
        
        $cache->set("mog_ItemSearchCategories", $categories, GameData::CACHE_TIME);
        $cache->disconnect();
    }
}
