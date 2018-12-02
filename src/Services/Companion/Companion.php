<?php

namespace App\Services\Companion;

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
     */
    public function getItemPrices(string $server, int $itemId): \stdClass
    {
        return $this->xivapi->market->price($server, $itemId);
    }

    /**
     * Get item history
     */
    public function getItemHistory(string $server, int $itemId): \stdClass
    {
        return $this->xivapi->market->history($server, $itemId);
    }
}
