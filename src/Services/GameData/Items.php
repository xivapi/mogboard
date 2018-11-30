<?php

namespace App\Services\GameData;

use App\Resources\Resources;

class Items extends GameDataAbstract
{
    const QUERIES = [
        'limit' => 250,
        'columns' => [
            "ID",
            "Name_*",
            "Icon",
            "Description_*",
            "LevelEquip",
            "LevelItem",
            "Rarity",
            "StackSize",
            "PriceLow",
            "PriceMid",

            "IsAdvancedMeldingPermitted",
            "IsCollectable",
            "IsCrestWorthy",
            "IsDyeable",
            "IsEquippable",
            "IsGlamourous",
            "IsIndisposable",
            "IsPvP",
            "IsUnique",
            "IsUntradable",

            "ClassJobCategory.ID",
            "ClassJobCategory.Name_*",
            "ItemUICategory.ID",
            "ItemUICategory.Name_*",
            "ItemUICategory.Icon",
            "ItemSearchCategory.ID",
            "ItemSearchCategory.Name_*",
            "ItemSearchCategory.Icon",
            "ItemKind.ID",
            "ItemKind.Name_*",
            "GameContentLinks",
            "GamePatch"
        ]
    ];

    public function populate()
    {
        $data  = [];
        $first = $this->xivapi->queries(self::QUERIES)->content->Item()->list();
        $this->io->text("Obtained items page 1/{$first->Pagination->PageTotal}");

        // save first set
        foreach($first->Results as $obj) {
            $data[$obj->ID] = $obj;
        }

        foreach(range(2, $first->Pagination->PageTotal) as $page) {
            // increment to the next page
            $queries = self::QUERIES;
            $queries['page'] = $page;

            // grab from xivapi
            $objects = $this->xivapi->queries($queries)->content->Item()->list();
            $this->io->text("Obtained items page {$page}/{$first->Pagination->PageTotal}");

            // append
            foreach($objects->Results as $obj) {
                $data[$obj->ID] = $obj;
            }
        }

        Resources::save('Items', $data);
    }
}
