<?php

namespace App\Service\GameData;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Arrays;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Handle populating game data, MogBoard does not connect connect directly to XIVAPI for
 * every request as this would be very slow. Instead it will download everything it
 * needs over the command-line and then this is referenced during requests.
 */
class GameDataCache
{
    // cache to 2030
    const CACHE_TIME = 1890691200;
    
    // category names for item search category
    const CATEGORY_NAMES = [
        1 => 'weapons',
        2 => 'armor',
        3 => 'items',
        4 => 'housing'
    ];
    
    /** @var ConsoleOutput */
    private $console;
    
    public function __construct()
    {
        $this->console = new ConsoleOutput();
    }
    
    public function populate()
    {
        $this->cacheGameItems();
        $this->cacheGameTowns();
        $this->cacheItemSearchCategories();
    }
    
    /**
     * Cache the game items
     */
    private function cacheGameItems()
    {
        $this->console->writeln('>> Caching Game Items');
        $section = $this->console->section();
        
        // we want to save cat id to item id
        $categoryToItemId = [];
        
        // build redis key list
        $keys = [];
        foreach (Redis::Cache()->get('ids_Item') as $i => $id) {
            $keys[$i] = "xiv_Item_{$id}";
        }
    
        $keys  = array_chunk($keys, 1000);
        $total = count($keys);
    
        $section->writeln('Starting ...');
        foreach ($keys as $i => $chunk) {
            $memory  = (memory_get_peak_usage(true) / 1024 / 1024);
            $objects = Redis::Cache()->getMulti($chunk);
        
            // save locally
            Redis::Cache()->startPipeline();
            foreach ($objects as $obj) {
                // save item
                Redis::Cache()->set("mog_Item_{$obj->ID}", $obj, GameDataCache::CACHE_TIME);
            
                // save item search category data
                if (isset($obj->ItemSearchCategory->ID)) {
                    $categoryToItemId[$obj->ItemSearchCategory->ID][] = [
                        'ID'        => $obj->ID,
                        'Name_en'   => $obj->Name_en,
                        'Name_de'   => $obj->Name_de,
                        'Name_fr'   => $obj->Name_fr,
                        'Name_ja'   => $obj->Name_ja,
                        'Icon'      => $obj->Icon,
                        'LevelItem' => $obj->LevelItem,
                        'Rarity'    => $obj->Rarity,
                    ];
                }
            }
            Redis::Cache()->executePipeline();
            unset($items);
    
            $section->overwrite("Saved chunk: ". ($i+1) ."/{$total} - {$memory} MB");
        }
        
        $this->console->writeln('>> Caching ItemSearchCategory Item IDs');
        $section = $this->console->section();
    
        // save categories
        $section->writeln('Starting ...');
        foreach ($categoryToItemId as $itemSearchCategoryId => $items) {
            // sort by item level
            Arrays::sortBySubKey($items, 'LevelItem');
        
            // save
            $key = "mog_ItemSearchCategory_{$itemSearchCategoryId}_Items";
            Redis::Cache()->set($key, $items, GameDataCache::CACHE_TIME);
            $section->writeln(count($items) . " items saved to category: {$key}");
        }
    }
    
    /**
     * Cache the game towns
     */
    private function cacheGameTowns()
    {
        $this->console->writeln('>> Caching Game Towns');
        
        foreach (Redis::Cache()->get('ids_Town') as $i => $id) {
            $town = Redis::Cache()->get('xiv_Town_'. $id);
            Redis::Cache()->set('mog_Town_'. $id, $town, self::CACHE_TIME);
        }
    }
    
    /**
     * Cache the item search categories
     */
    private function cacheItemSearchCategories()
    {
        $this->console->writeln('>> Caching Item Search Categories');
        
        // build redis key list
        $keys = [];
        foreach (Redis::Cache()->get('ids_ItemSearchCategory') as $i => $id) {
            $keys[$i] = "xiv_ItemSearchCategory_{$id}";
        }
    
        // as there are only 100 item search categories, we will get them all at once:
        $objects    = Redis::Cache()->getMulti($keys);
        $categories = [];
        $categoriesFull = [];
    
        foreach ($objects as $category) {
            // ignore empty ones
            if (!in_array($category->Category, array_keys(self::CATEGORY_NAMES)) || empty($category->Name_en)) {
                continue;
            }
            
            $this->console->writeln("- {$category->ID} {$category->Name_en}");
        
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
            Redis::Cache()->set("mog_ItemSearchCategory_{$category->ID}", $category, GameDataCache::CACHE_TIME);
            
            $categoriesFull[$category->ID] = [
                'ID'      => $category->ID,
                'Icon'    => $category->Icon,
                'Name_en' => $category->Name_en,
                'Name_de' => $category->Name_de,
                'Name_fr' => $category->Name_fr,
                'Name_ja' => $category->Name_ja,
            ];
        }
    
        ksort($categories['weapons']);
        ksort($categories['armor']);
        ksort($categories['items']);
        ksort($categories['housing']);
    
        $categories['weapons']  = array_values($categories['weapons']);
        $categories['armor']    = array_values($categories['armor']);
        $categories['items']    = array_values($categories['items']);
        $categories['housing']  = array_values($categories['housing']);
    
        Redis::Cache()->set("mog_ItemSearchCategories", $categories, (60 * 60 * 24 * 365));
        Redis::Cache()->set("mog_ItemSearchCategoriesFull", $categoriesFull, (60 * 60 * 24 * 365));
    }
}
