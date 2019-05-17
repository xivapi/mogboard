<?php

namespace App\Service\Companion;

use XIVAPI\XIVAPI;

class Companion
{
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct()
    {
        $this->xivapi = new XIVAPI();
    }
    
    public function getByServer(string $server, int $itemId)
    {
        return $this->xivapi->market->item($itemId, [$server]);
    }
    
    public function getByServers(array $servers, int $itemId)
    {
        return $this->xivapi->market->item($itemId, $servers);
    }
    
    public function getByDataCenter(string $dataCenter, int $itemId)
    {
        return $this->xivapi->market->item($itemId, [], $dataCenter);
    }

    public function getByServersForItems(array $items, array $servers)
    {
        //return $this->xivapi->market->
    }
}
