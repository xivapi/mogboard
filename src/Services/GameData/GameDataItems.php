<?php

namespace App\Services\GameData;

use App\Services\Cache\Redis;
use App\Services\Common\Arrays;

/**
 * Copy item data from XIVAPI cache to MogBoard Cache
 */
class GameDataItems extends GameDataAbstract
{
    public function populate()
    {
        $cache = (new Redis())->connect();
        
        // we want to save cat id to item id
        $categoryToItemId = [];
        
        // build redis key list
        $keys = [];
        foreach ($cache->get('ids_Item') as $i => $id) {
            $keys[$i] = "xiv_Item_{$id}";
        }
        
        $keys  = array_chunk($keys, 1000);
        $total = count($keys);
        
        $this->io->text("Chunks to download: {$total}");
        foreach ($keys as $i => $chunk) {
            $memory  = (memory_get_peak_usage(true)/1024/1024);
            $objects = $cache->getMulti($chunk);
    
            // save locally
            $cache->startPipeline();
            foreach ($objects as $obj) {
                // save item
                $cache->set("mog_Item_{$obj->ID}", [
                    'ID'                => $obj->ID,
                    'Name_en'           => $obj->Name_en,
                    'Name_de'           => $obj->Name_de,
                    'Name_fr'           => $obj->Name_fr,
                    'Name_ja'           => $obj->Name_ja,
                    'Icon'              => $obj->Icon,
                    'LevelEquip'        => $obj->LevelEquip,
                    'LevelItem'         => $obj->LevelItem,
                    'Rarity'            => $obj->Rarity,
                    'StackSize'         => $obj->StackSize,
                    'IsCollectable'     => $obj->IsCollectable,
                    'IsCrestWorthy'     => $obj->IsCrestWorthy,
                    'IsDyeable'         => $obj->IsDyeable,
                    'IsEquippable'      => $obj->IsEquippable,
                    'IsGlamourous'      => $obj->IsGlamourous,
                    'IsIndisposable'    => $obj->IsIndisposable,
                    'IsPvP'             => $obj->IsPvP,
                    'IsUnique'          => $obj->IsUnique,
                    'IsUntradable'      => $obj->IsUntradable,
                    'IsAdvancedMeldingPermitted' => $obj->IsAdvancedMeldingPermitted,
                    'ClassJobCategory'  => [
                        'ID' => $obj->ClassJobCategory->ID ?? null,
                        'Name_en' => $obj->ClassJobCategory->Name_en ?? null,
                        'Name_de' => $obj->ClassJobCategory->Name_de ?? null,
                        'Name_fr' => $obj->ClassJobCategory->Name_fr ?? null,
                        'Name_ja' => $obj->ClassJobCategory->Name_ja ?? null,
                    ],
                    'ItemUICategory'  => [
                        'ID'    => $obj->ItemUICategory->ID ?? null,
                        'Icon'    => $obj->ItemUICategory->Icon ?? null,
                        'Name_en' => $obj->ItemUICategory->Name_en ?? null,
                        'Name_de' => $obj->ItemUICategory->Name_de ?? null,
                        'Name_fr' => $obj->ItemUICategory->Name_fr ?? null,
                        'Name_ja' => $obj->ItemUICategory->Name_ja ?? null,
                    ],
                    'ItemSearchCategory'  => [
                        'ID' => $obj->ItemSearchCategory->ID ?? null,
                        'Name_en' => $obj->ItemSearchCategory->Name_en ?? null,
                        'Name_de' => $obj->ItemSearchCategory->Name_de ?? null,
                        'Name_fr' => $obj->ItemSearchCategory->Name_fr ?? null,
                        'Name_ja' => $obj->ItemSearchCategory->Name_ja ?? null,
                    ],
                    'ItemKind'  => [
                        'ID' => $obj->ItemKind->ID ?? null,
                        'Name_en' => $obj->ItemKind->Name_en ?? null,
                        'Name_de' => $obj->ItemKind->Name_de ?? null,
                        'Name_fr' => $obj->ItemKind->Name_fr ?? null,
                        'Name_ja' => $obj->ItemKind->Name_ja ?? null,
                    ],
                    
                ], GameData::CACHE_TIME);
                
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
            $cache->executePipeline();
            unset($items);
    
            $this->io->text("Saved chunk: ". ($i+1) ."/{$total} - {$memory} MB");
        }
    
        // save categories
        $this->io->text('Saving ItemSearchCategory Item IDs');
        foreach ($categoryToItemId as $itemSearchCategoryId => $items) {
            // sort by item level
            Arrays::sksort($items, 'LevelItem');
            
            // save
            $key = "mog_ItemSearchCategory_{$itemSearchCategoryId}_Items";
            $cache->set($key, $items, GameData::CACHE_TIME);
            $this->io->text(count($items) . " items saved to category: {$key}");
        }
        
        $this->io->text('Complete');
        $cache->disconnect();
    }
}
