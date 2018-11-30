<?php

namespace App\Services\GameData;

use App\Resources\Resources;

class Materia extends GameDataAbstract
{
    const QUERIES = [
        'limit' => 250,
        'columns' => [
            "ID",
            "BaseParam.ID",
            "BaseParam.Name_*",
            "BaseParam.Description_*",
            "Item0.ID",
            "Item1.ID",
            "Item2.ID",
            "Item3.ID",
            "Item4.ID",
            "Item5.ID",
            "Item6.ID",
            "Item7.ID",
            "Item8.ID",
            "Item9.ID",
            "Value0",
            "Value1",
            "Value2",
            "Value3",
            "Value4",
            "Value5",
            "Value6",
            "Value7",
            "Value8",
            "Value9",
        ]
    ];

    public function populate()
    {
        $data  = [];
        $first = $this->xivapi->queries(self::QUERIES)->content->Materia()->list();
        $this->io->text("Obtained materia page 1/{$first->Pagination->PageTotal}");

        // save first set
        foreach($first->Results as $obj) {
            $data[$obj->ID] = $obj;
        }

        foreach(range(2, $first->Pagination->PageTotal) as $page) {
            // increment to the next page
            $queries = self::QUERIES;
            $queries['page'] = $page;

            // grab from xivapi
            $objects = $this->xivapi->queries($queries)->content->Materia()->list();
            $this->io->text("Obtained materia page {$page}/{$first->Pagination->PageTotal}");

            // append
            foreach($objects->Results as $obj) {
                $data[$obj->ID] = $obj;
            }
        }

        Resources::save('Materia', $data);
    }
}
