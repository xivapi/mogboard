<?php

namespace App\Service\Companion;

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
    }

    /**
     * Get the current prices for an item
     */
    public function get(int $server, int $itemId)
    {
        $key = "mb_market_{$server}_{$itemId}";
        
        if ($source = Redis::cache()->get($key)) {
            return json_decode(json_encode($source), true);
        }
        
        $this->connect();
    
        $result = $this->elastic->getDocument(self::INDEX, self::INDEX, "{$server}_{$itemId}");
        $source = $result['_source'];
        
        // remove some stuff, try reduce memory
        foreach ($source['Prices'] as $i => $price) {
            unset(
                $price['ID'],
                $price['Added'],
                $price['RetainerID'],
                $price['CreatorSignatureID'],
                $price['StainID'],
            );
    
            $source['Prices'][$i] = $price;
        }
    
        foreach ($source['History'] as $i => $history) {
            unset(
                $history['ID'],
                $history['Added'],
                $history['CharacterID'],
            );
        
            $source['History'][$i] = $history;
        }

        // slice history
        $source['History'] = array_slice($source['History'], 0, 500);
    
        // add update queue
        $stmt = $this->em->getConnection()->prepare(
            "SELECT normal_queue FROM companion_market_items WHERE item = ? AND server = ? LIMIT 1"
        );
    
        $stmt->execute([
            $itemId,
            $server
        ]);
    
        $source['UpdatePriority'] = $stmt->fetch()['normal_queue'] ?? null;
    
        Redis::cache()->set($key, $source, 60);
        
        return $source;
    }
}
