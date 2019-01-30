<?php

namespace App\Services\GameData;

use App\Services\Cache\Cache;

class GameDataSource
{
    public function getItem(int $id)
    {
        return Cache::get('xiv_Item_'. $id);
    }
    
    public function getTown(int $id)
    {
        return Cache::get('xiv_ITown_'. $id);
    }
}
