<?php

namespace App\Services\GameData;

use App\Resources\Resources;
use XIVAPI\Api\SearchFilters;

class ItemSearchCategories extends GameDataAbstract
{
    const CATEGORY_NAMES = [
        1 => 'weapons',
        2 => 'armor',
        3 => 'misc',
        4 => 'housing'
    ];

    const CATEGORY_QUERIES = [
        'limit' => 500,
        'columns' => [
            'ID',
            'Icon',
            'Name_*',
            'Order',
            'Category',
            'ClassJob.ClassJobCategory.Name_*'
        ]
    ];

    const ITEM_COLUMNS = [
        'ID',
        'Icon',
        'Rarity',
        'LevelItem',
        'Name_*',
    ];

    public function populate()
    {
        $this->io->section(__METHOD__);

        $categories = $this->xivapi->queries(self::CATEGORY_QUERIES)->content->ItemSearchCategory()->list();

        $itemSearchCategories = [];
        $itemSearchCategoriesByID = [];

        foreach ($categories->Results as $i => $res) {
            // ignore empty ones
            if (!in_array($res->Category, array_keys(self::CATEGORY_NAMES))) {
                continue;
            }

            // store category
            $catName = self::CATEGORY_NAMES[$res->Category];
            $itemSearchCategories[$catName][$res->Order] = $res;
            $itemSearchCategoriesByID[$res->ID] = $res;

            // download items within the category
            $this->io->text("- Downloading items data for category: {$res->Name_en}");

            $items = $this->xivapi
                ->search
                ->indexes(['item'])
                ->filter('ItemSearchCategory.ID', $res->ID, SearchFilters::EQUAL_TO)
                ->limit(500)
                ->sort('LevelItem', 'desc')
                ->columns(self::ITEM_COLUMNS)
                ->results();

            Resources::save("ItemsWithinSearchCategory_{$res->ID}", $items->Results);

            if ($items->Pagination->ResultsTotal > 1000) {
                throw new \Exception("The total results was above 1000, this means the code will 
                    not work and you need to implement a page handler");
            }
        }

        Resources::save('ItemSearchCategories', $itemSearchCategories);
        Resources::save('ItemSearchCategoriesByID', $itemSearchCategoriesByID);
    }
}
