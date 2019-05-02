<?php

namespace App\Service\Items;

use App\Entity\PopularItem;
use App\Repository\PopularItemRepository;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ItemPopularity
{
    const MAX_HITS  = 10;
    const MAX_DELAY = (60 * 60 * 6);
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var PopularItemRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(PopularItem::class);
    }
    
    /**
     * Get current popular items
     * @return PopularItem[]
     */
    public function get()
    {
        $ids = Redis::Cache()->get('mogboard_trending_items');

        if ($ids == null) {
            $ids   = [];
            $items = (array)$this->repository->findBy([], [ 'count' => 'desc' ], 20);
            
            /** @var PopularItem $item */
            foreach ($items as $item) {
                $ids[] = $item->getItem();
            }
            shuffle($ids);
            
            Redis::Cache()->set('mogboard_trending_items', $ids, self::MAX_DELAY);
        }

        return $ids;
    }
    
    /**
     * Resets popular items by resetting all the counts back to 0.
     * This should be done every 12 hours so data can be collected every AM/PM
     */
    public function reset()
    {
        /** @var PopularItem $item */
        foreach ($this->repository->findAll() as $item) {
            $item->setCount(0)->setUpdated(time());
            $this->em->persist($item);
            $this->em->flush();
        }
    }
    
    /**
     * Record an item hit
     */
    public function hit(Request $request, int $itemId)
    {
        $key     = "item_hit_". sha1($request->getClientIp());
        $current = Redis::Cache()->get($key);
        
        // ignore if we've hit the limit
        if ($current > self::MAX_HITS) {
            return;
        }
        
        // up users hit counter
        $current = $current ? $current + 1 : 1;
        Redis::Cache()->set($key, $current, self::MAX_DELAY);
        
        // grab popular item entry
        $entity = $this->repository->findOneBy([ 'item' => $itemId ]) ?: new PopularItem();
        
        $entity
            ->setItem($itemId)
            ->setUpdated(time())
            ->setCount($entity->getCount() + 1);
        
        $this->em->persist($entity);
        $this->em->flush();
    }
}
