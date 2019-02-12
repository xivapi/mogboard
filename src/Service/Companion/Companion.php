<?php

namespace App\Service\Companion;

use GuzzleHttp\Promise;
use XIVAPI\XIVAPI;

class Companion
{
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct()
    {
        $this->xivapi = new XIVAPI(XIVAPI::DEV);
    }
    
    /**
     * Get market data from XIVAPI
     * @return \stdClass|\GuzzleHttp\Promise\Promise
     */
    public function get(string $server, int $itemId)
    {
        return $this->xivapi->market->get($server, $itemId);
    }
    
    /**
     * Get prices for an item across multiple servers
     */
    public function getMultiServer(array $servers, int $itemId)
    {
        // enable async mode
        $this->xivapi->async();
        
        // build promises
        $promises = [];
        foreach ($servers as $server) {
            $promises[$server] = $this->get($server, $itemId);
        }
    
        // grab results
        $results = Promise\settle($promises)->wait();
        
        // unwrap market prices
        $market = $this->xivapi->unwrap($results);
        
        return $market;
    }
}
