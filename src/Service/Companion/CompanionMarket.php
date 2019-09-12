<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles the Elastic Search Companion Market info
 */
class CompanionMarket
{
    const SAVE_DIRECTORY = __DIR__.'/../../../../companion_data';
    
    /** @var EntityManagerInterface */
    private $em;

    /** @var UniversalisApi */
    private $universalis;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->universalis = new UniversalisApi();
    }

    /**
     * Get companion market data for an item across multiple servers
     */
    public function get(array $servers, int $itemId)
    {
        $key = "mb_file_market_{$itemId}_". md5(serialize($servers));
        
        if ($data = Redis::cache()->get($key)) {
            return json_decode(json_encode($data), true);
        }
        
        $data = [];
        foreach ($servers as $server) {
            $serverId   = GameServers::getServerId($server);
            $source     = $this->universalis->getItem($serverId, $itemId);

            if ($source == null) {
                $data[$server] = [
                    'ID'      => "{$serverId}_{$itemId}",
                    'Server'  => $serverId,
                    'ItemID'  => $itemId,
                    'Prices'  => [],
                    'History' => [],
                    'Updated' => null,
                    'UpdatePriority' => 0,
                ];
                continue;
            }

            $data[$server] = $this->handle($itemId, $serverId, $source);
            $data[$server]['lastUploadTime'] = ceil($data[$server]['lastUploadTime'] / 1000);
        }

        Redis::cache()->set($key, $data, 60);
        
        return $data;
    }

    /**
     * Get market doc
     */
    public function getMarketDocument($serverId, $itemId)
    {
        $folder   = $this->getFolder($serverId);
        $filename = "{$folder}/item_{$itemId}.serialised";

        if (file_exists($filename) == false) {
            return null;
        }

        $item = file_get_contents($filename);
        $item = unserialize($item);
        return $item;
    }

    /**
     * Get storage folder (also makes it if it dont exist)
     */
    private function getFolder($serverId)
    {
        $folder = self::SAVE_DIRECTORY;
        $folder = "{$folder}/server_{$serverId}";

        if (is_dir($folder) == false) {
            mkdir($folder, 0775, true);
        }

        return $folder;
    }

    /**
     * Handle the response data of $this->>get
     */
    private function handle($itemId, $server, $source)
    {
        $source = json_decode(json_encode($source), true);

        // remove some stuff, try reduce memory
        foreach ($source['listings'] as $i => $price) {
            unset(
                $price['retainerID'],
                $price['sellerID'],
                $price['stainID']
            );
    
            $source['prices'][$i] = $price;
        }
    
        foreach ($source['recentHistory'] as $i => $history) {
            unset(
                $history['buyerID'],
                $history['sellerID']
            );
        
            $source['recentHistory'][$i] = $history;
        }

        // slice history
        $source['recentHistory'] = array_slice($source['recentHistory'], 0, 200);

        return $source;
    }
}
