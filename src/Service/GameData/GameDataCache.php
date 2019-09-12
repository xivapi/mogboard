<?php

namespace App\Service\GameData;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Arrays;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

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

    /** @var GameDataSource */
    private $gameDataSource;
    
    /** @var XIVAPI */
    private $xivapi;

    public function __construct(GameDataSource $gameDataSource)
    {
        $this->console = new ConsoleOutput();
        $this->gameDataSource = $gameDataSource;
        $this->xivapi = new XIVAPI();
    }
    
    public function populate()
    {
        $this->cacheGameItems();
        $this->cacheGameTowns();
        $this->cacheGameWorlds();
        $this->cacheItemSearchCategories();
        //$this->cacheGameCategories();
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

        /* Let's not do this for now - we will cache full items are they are needed
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
        */

        $this->console->writeln('>> Caching ItemSearchCategory Item IDs');
        $section = $this->console->section();
    
        // save categories
        $section->writeln('Starting ...');

        foreach (\json_decode(\file_get_contents('DataExports/ItemSearchCategory_Keys.json')) as $i => $id) {
            $itemsJsonPath = "DataExports/ItemSearchCategory_{$i}.json";
            $key = "mog_ItemSearchCategory_{$id}_Items";

            if (!\file_exists($itemsJsonPath)){
                // no items for this category, save an empty array
                Redis::Cache()->set($key, [], GameDataCache::CACHE_TIME);
                continue;
            }

            $items = \json_decode(\file_get_contents($itemsJsonPath), true);

            // sort by item level
            Arrays::sortBySubKey($items, 'LevelItem');
            
            // save
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
        $this->console->writeln(\getcwd());
        $towns = \json_decode(file_get_contents('DataExports/Town.json'));

        foreach ($towns as $town) {
            $this->console->writeln($town->Name_en);
            Redis::Cache()->set('xiv_Town_'. $town->ID, $town, self::CACHE_TIME);
            Redis::Cache()->set('mog_Town_'. $town->ID, $town, self::CACHE_TIME);
        }
    }

    /**
     * Cache the game worlds
     */
    private function cacheGameWorlds()
    {
        $this->console->writeln('>> Caching Game Worlds');
        $this->console->writeln(\getcwd());
        $worlds = \json_decode(file_get_contents('DataExports/World.json'));

        $worldMap = [];
        foreach ($worlds as $world) {
            $this->console->writeln($world->Name);
            $worldMap[$world->ID] = $world->Name;
            Redis::Cache()->set('xiv_World_'. $world->ID, $world, self::CACHE_TIME);
        }

        Redis::Cache()->set('xiv_World_Map', $worldMap, self::CACHE_TIME);
    }
    
    /**
     * Cache the item search categories
     */
    private function cacheItemSearchCategories()
    {
        $this->console->writeln('>> Caching Item Search Categories');
        
        // build redis key list
        $keys = [];
        $objects = [];
        foreach (\json_decode(\file_get_contents('DataExports/ItemSearchCategory_Keys.json')) as $i => $id) {
            $keys[$i] = "xiv_ItemSearchCategory_{$id}";
            
            $objects[$i] = $this->xivapi->content->ItemSearchCategory()->one($i);
            $this->console->writeln($keys[$i].': '.$objects[$i]->Name_en);
        }

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

    private function cacheGameCategories()
    {
        $this->console->writeln('>> Caching menu categories to js files');
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

        $root = __DIR__.'/../../../public/data';

        // save category data
        file_put_contents("{$root}/categories_50_en.js", json_encode($itemsEn));
        file_put_contents("{$root}/categories_50_de.js", json_encode($itemsDe));
        file_put_contents("{$root}/categories_50_fr.js", json_encode($itemsFr));
        file_put_contents("{$root}/categories_50_ja.js", json_encode($itemsJa));
    }
}
