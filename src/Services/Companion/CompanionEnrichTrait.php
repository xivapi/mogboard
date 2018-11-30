<?php

namespace App\Services\Companion;

use App\Resources\Resources;

trait CompanionEnrichTrait
{
    /**
     * Get better town info
     */
    protected function getEnrichedTown($townId)
    {
        return Resources::load("Towns")[$townId];
    }

    /**
     * Get better materia data
     */
    protected function getEnrichedMateria(array $materia): array
    {
        $arr = [];
        foreach ($materia as $mat) {
            $mat->grade = (int)$mat->grade;

            // load materia
            $materia = Resources::load("Materia")[$mat->key];

            // grab the item
            $item = $materia->{"Item{$mat->grade}"};
            $item = Resources::load("Items")[$item->ID];

            $arr[] = $item;
        }

        return $arr;
    }
}
