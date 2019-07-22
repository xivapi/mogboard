<?php

namespace App\Service\Companion;

use App\Common\Exceptions\BasicException;
use App\Common\Game\GameServers;
use App\Common\Service\ElasticSearch\ElasticSearch;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles the Elastic Search Companion Market info
 */
class CompanionMarket
{
    const INDEX = 'companion';
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var ElasticSearch */
    private $elastic;
    
    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }
    
    public function connect()
    {
        if ($this->elastic === null) {
            $this->elastic  = new ElasticSearch('ELASTIC_SERVER_COMPANION');
        }
        
        return $this;
    }
    
    
    public function get(array $servers, int $itemId)
    {
        $key = "mbv4_market_{$itemId}_". md5(serialize($servers));
        
        if ($data = Redis::cache()->get($key)) {
            return json_decode(json_encode($data), true);
        }
        
        $requests = [];
        
        foreach ($servers as $server) {
            $serverId   = GameServers::getServerId($server);
            $requests[] = "{$serverId}_{$itemId}";
        }
    
        $data      = [];
        $results   = $this->connect()->elastic->getDocumentsBulk(self::INDEX, self::INDEX, $requests);
        $results   = $results['docs'];
        
        foreach ($results as $result) {
            [$serverId, $itemId] = explode('_', $result['_id']);
            $source        = $result['_source'] ?? null;
            $server        = GameServers::LIST[$serverId];
            
            if ($source === null) {
                continue;
            }
            
            $data[$server] = $this->handle($itemId, $serverId, $source);
        }
        
        Redis::cache()->set($key, $data, 60);
        
        return $data;
    }

    /**
     * Get the current prices for an item
     */
    private function handle($itemId, $server, $source)
    {
        // remove some stuff, try reduce memory
        foreach ($source['Prices'] as $i => $price) {
            unset(
                $price['Added'],
                $price['RetainerID'],
                $price['CreatorSignatureID'],
                $price['StainID']
            );
    
            $source['Prices'][$i] = $price;
        }
    
        foreach ($source['History'] as $i => $history) {
            unset(
                $history['Added'],
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
