<?php

namespace App\Service\Companion;

use XIVAPI\XIVAPI;

class Companion
{
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct()
    {
        $this->xivapi = new XIVAPI(XIVAPI::STAGING);
    }
    
    public function getByServer(string $server, int $itemId)
    {
        return $this->xivapi->market->getServer($server, $itemId);
    }
    
    public function getByServers(array $servers, int $itemId)
    {
        return $this->xivapi->market->getServers($servers, $itemId);
    }
    
    public function getByDataCenter(string $dataCenter, int $itemId)
    {
        return $this->xivapi->market->getDataCenter($dataCenter, $itemId);
    }
}
