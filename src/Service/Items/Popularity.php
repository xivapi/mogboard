<?php

namespace App\Service\Items;

use App\Entity\ItemPopularity;
use App\Repository\ItemPopularityRepository;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class Popularity
{
    const REDIS_KEY = 'mogboard_trending_items';
    const MAX_HITS  = 2;

    /** @var EntityManagerInterface */
    private $em;
    /** @var ItemPopularityRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(ItemPopularity::class);
    }
    
    /**
     * Get current popular items ids
     * @return array
     */
    public function get()
    {
        return Redis::Cache()->get(self::REDIS_KEY);
    }

    /**
     * Generate a list of the top 20 items.
     */
    public function generate()
    {
        $ids   = [];
        $items = (array)$this->repository->findBy([], [ 'count' => 'desc' ], 20);

        /** @var ItemPopularity $item */
        foreach ($items as $item) {
            $ids[] = $item->getItem();
        }

        shuffle($ids);

        Redis::Cache()->set(self::REDIS_KEY, $ids, (60 * 60 * 24 * 5));
    }
    
    /**
     * Generates the current list and then truncates the popularity
     * table for the next cycle of item hits.
     */
    public function reset()
    {
        // generate
        $this->generate();

        // truncate db
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare('TRUNCATE TABLE items_popularity');
        $stmt->execute();
    }
    
    /**
     * Record an item hit
     */
    public function hit(Request $request, int $itemId)
    {
        $key     = "item_hit_". $itemId . sha1($request->getClientIp());
        $current = Redis::Cache()->get($key);
        
        // ignore if we've hit the limit
        if ($current > self::MAX_HITS) {
            return;
        }
        
        // up users hit counter
        $current = $current ? $current + 1 : 1;
        Redis::Cache()->set($key, $current, (60 * 60 * 8));
        
        // grab popular item entry
        $entity = $this->repository->findOneBy([ 'item' => $itemId ]) ?: new ItemPopularity();
        
        $entity
            ->setItem($itemId)
            ->setUpdated(time())
            ->setCount($entity->getCount() + 1);
        
        $this->em->persist($entity);
        $this->em->flush();
    }
}
