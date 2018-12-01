<?php

namespace App\Services\GameData;

class GameDataMateria extends GameDataAbstract
{
    public function populate()
    {
        /*
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
        */
    }
}
