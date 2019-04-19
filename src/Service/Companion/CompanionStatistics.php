<?php

namespace App\Service\Companion;

use App\Service\Redis\Redis;
use XIVAPI\XIVAPI;

class CompanionStatistics
{
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct()
    {
        $this->xivapi = new XIVAPI();
    }
    
    /**
     * Get market stats
     */
    public function stats()
    {
        // get market status
        $apiStats = Redis::Cache()->get('mogboard_companion_update_stats');
        
        if ($apiStats == null) {
            $apiStats = $this->xivapi->market->stats();
            Redis::Cache()->set('mogboard_companion_update_stats', $apiStats);
        }
        
        return $apiStats;
    }
}
