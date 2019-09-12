<?php

namespace App\Service\Companion;

use App\Common\Service\Redis\Redis;
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
        $apiStats = Redis::Cache()->get('mogboard_marketstats');
        
        if ($apiStats == null) {
            $apiStats = $this->xivapi->market->stats();
            Redis::Cache()->set('mogboard_marketstats', $apiStats, (60 * 60));
        }
        
        return json_decode(json_encode($apiStats), true);
    }
    
    /**
     * Get the cheapest NQ and HQ prices from the market
     */
    public function cheapest($market)
    {
        $cheapest = [];
        
        foreach ($market as $server => $serverMarket) {
            $cheapestHq = 0;
            $cheapestNq = 0;
            
            foreach ($serverMarket['listings'] as $m) {
                if ($cheapestNq === 0 && $m['hq'] === false) {
                    $cheapestNq = $m['pricePerUnit'];
                }
    
                if ($cheapestHq === 0 && $m['hq'] === true) {
                    $cheapestHq = $m['pricePerUnit'];
                }
            }
            
            $cheapest[$server] = [
                'HQ' => $cheapestHq,
                'NQ' => $cheapestNq
            ];
        }
        
        return $cheapest;
    }
}
