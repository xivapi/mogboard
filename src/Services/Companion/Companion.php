<?php

namespace App\Services\Companion;

use App\Services\Cache\Redis;
use GuzzleHttp\Promise;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use XIVAPI\XIVAPI;

class Companion
{
    use CompanionStatisticsTrait;
    
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct()
    {
        $this->xivapi = new XIVAPI();
    }
    
    /**
     * Get prices for an item on a server
     * @return \stdClass|\GuzzleHttp\Promise\Promise
     */
    public function getItemPrices(string $server, int $itemId)
    {
        $server = ucwords($server);
        return $this->xivapi->market->price($server, $itemId);
    }

    /**
     * Get item history
     * @return \stdClass|\GuzzleHttp\Promise\Promise
     */
    public function getItemHistory(string $server, int $itemId)
    {
        $server = ucwords($server);
        return $this->xivapi->market->history($server, $itemId);
    }
    
    /**
     * Get prices for an item across multiple servers
     */
    public function getItemPricesCrossWorld(string $server, int $itemId): array
    {
        $server = ucwords($server);
        
        $redis = (new Redis())->connect();

        // grab server info
        $serversToDc = $redis->get('mog_DataCentersServers');
        $dcToServers = $redis->get('mog_DataCenters');
        
        // throw error it server doesn't exist
        if (!isset($serversToDc->{$server})) {
            throw new NotFoundHttpException();
        }
        
        $dc = $serversToDc->{$server};
        $servers = $dcToServers->{$dc};
    
        # todo - comment this, debugging purposes
        /*
        if (file_exists(__DIR__.'/dev.json')) {
            return [
                json_decode(file_get_contents(__DIR__.'/dev.json')),
                $dc,
                $servers,
                0.1234
            ];
        }*/
        
        // concurrent api requests!!!!!!
        $this->xivapi->async();

        // build all requests
        $promises = [];
        foreach ($servers as $server) {
            $promises[$server . '_current'] = $this->getItemPrices($server, $itemId);
            $promises[$server . '_history'] = $this->getItemHistory($server, $itemId);
        }
    
        // fire off request
        $start = microtime(true);
        $results = Promise\settle($promises)->wait();
        $prices = $this->xivapi->unwrap($results);
        $duration = microtime(true) - $start;
        
        # todo - comment this, debugging purposes
        // file_put_contents(__DIR__.'/dev.json', json_encode($prices));
        
        return [ $prices, $dc, $servers, $duration ];
    }
}
