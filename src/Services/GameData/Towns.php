<?php

namespace App\Services\GameData;

use App\Resources\Resources;

class Towns extends GameDataAbstract
{
    const QUERIES = [
        'limit' => 50,
        'columns' => [
            "ID",
            "Name_*",
            "Icon",
        ]
    ];

    public function populate()
    {
        $data = [];
        $towns = $this->xivapi->queries(self::QUERIES)->content->Town()->list()->Results;

        foreach ($towns as $town) {
            $data[$town->ID] = $town;
        }

        Resources::save('Towns', $data);
    }
}
