<?php

namespace App\Services\GameData;

/**
 * Handle populating game data, MogBoard does not connect connect directly to XIVAPI for
 * every request as this would be very slow. Instead it will download everything it
 * needs over the command-line and then this is referenced during requests.
 */
class GameData extends GameDataAbstract
{
    public function populate()
    {
        $classes = [
            new ItemSearchCategories($this->io),
            new Items($this->io),
            new Towns($this->io),
            new Materia($this->io)
        ];

        /** @var GameDataAbstract $class */
        foreach ($classes as $class) {
            $this->io->section(get_class($class));
            $class->populate();
        }
    }
}
