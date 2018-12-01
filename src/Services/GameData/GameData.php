<?php

namespace App\Services\GameData;

/**
 * Handle populating game data, MogBoard does not connect connect directly to XIVAPI for
 * every request as this would be very slow. Instead it will download everything it
 * needs over the command-line and then this is referenced during requests.
 */
class GameData extends GameDataAbstract
{
    const CACHE_TIME = (60*60*24*365);
    
    public function populate()
    {
        // clear current db
        #(new Cache())->connect()->flush();
        
        $classes = [
            new GameDataItems($this->io),
            new GameDataItemSearchCategories($this->io),
            #new Towns($this->io),
            #new Materia($this->io),
        ];
    
        // begin populating
        /** @var GameDataAbstract $class */
        foreach ($classes as $class) {
            $this->io->section(get_class($class));
            $class->populate();
        }
    }
}
