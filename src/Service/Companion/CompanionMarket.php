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
    const SAVE_DIRECTORY = __DIR__.'/../../../../companion_data/';
    
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get companion market data for an item across multiple servers
     */
    public function get(array $servers, int $itemId)
    {
        $key = "mbv5_market_{$itemId}_". md5(serialize($servers));
        
        if ($data = Redis::cache()->get($key)) {
            return json_decode(json_encode($data), true);
        }
        
        $data = [];
        foreach ($servers as $server) {
            $serverId   = GameServers::getServerId($server);
            $source     = $this->getMarketDocument($serverId, $itemId);

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
        $filename = "{$folder}/{$itemId}.serialised";

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
        foreach ($source['Prices'] as $i => $price) {
            unset(
                $price['RetainerID'],
                $price['CreatorSignatureID'],
                $price['StainID']
            );
    
            $source['Prices'][$i] = $price;
        }
    
        foreach ($source['History'] as $i => $history) {
            unset(
                $history['CharacterID']
            );
        
            $source['History'][$i] = $history;
        }

        // slice history
        $source['History'] = array_slice($source['History'], 0, 100);
    
        // add update queue
        $stmt = $this->em->getConnection()->prepare(
            "SELECT normal_queue FROM companion_market_items WHERE item = ? AND server = ? LIMIT 1"
        );
    
        $stmt->execute([
            $itemId,
            $server
        ]);
        
        $source['UpdatePriority'] = $stmt->fetch()['normal_queue'] ?? null;

        return $source;
    }
}
