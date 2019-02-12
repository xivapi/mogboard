<?php

namespace App\Service\GameData;

use App\Service\Redis\Redis;

class GameDataSource
{
    public function getItem(int $id)
    {
        return Redis::get('xiv_Item_'. $id);
    }
    
    public function getTown(int $id)
    {
        return Redis::get('xiv_ITown_'. $id);
    }
}
