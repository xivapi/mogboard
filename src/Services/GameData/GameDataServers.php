<?php

namespace App\Services\GameData;

use App\Exceptions\CompanionMarketServerException;
use App\Services\Cache\Redis;
use XIVAPI\XIVAPI;

class GameDataServers extends GameDataAbstract
{
    public function populate()
    {
        $cache  = (new Redis())->connect();
        $xivapi = new XIVAPI();
        $dcs    = $xivapi->content->serversByDataCenter();
        $cache->set('mog_DataCenters', $dcs, GameData::CACHE_TIME);
        
        $serverToDc = [];
        foreach ($dcs as $dc => $servers) {
            foreach ($servers as $server) {
                $serverToDc[$server] = $dc;
            }
        }
    
        $cache->set("mog_DataCentersServers", $serverToDc, GameData::CACHE_TIME);
        $cache->disconnect();
    }
    
    /**
     * Get a server id from a server string
     */
    public static function getServerId(string $server): int
    {
        $index = array_search(ucwords($server), GameServers::LIST);
        
        if ($index === false) {
            throw new CompanionMarketServerException();
        }
        
        return $index;
    }
    
    /**
     * Get the Data Center for
     */
    public static function getDataCenter(string $server): ?string
    {
        foreach (GameServers::LIST_DC as $dc => $servers) {
            if (in_array($server, $servers)) {
                return $dc;
            }
        }
        
        return null;
    }
    
    /**
     * Get the data center servers for a specific server
     */
    public static function getDataCenterServers(string $server): ?array
    {
        $dc = self::getDataCenter($server);
        return $dc ? GameServers::LIST_DC[$dc] : null;
    }
}
