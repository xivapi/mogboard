<?php

namespace App\Service\GameData;

use App\Service\Common\Language;
use App\Service\Redis\Redis;

class GameDataSource
{
    public function getItem(int $id)
    {
        $obj = Redis::Cache()->get('xiv_Item_'. $id);
        $obj = Language::handle($obj);
        return $obj;
    }
    
    public function getTown(int $id)
    {
        $obj = Redis::Cache()->get('xiv_Town_'. $id);
        $obj = Language::handle($obj);
        return $obj;
    }
}
