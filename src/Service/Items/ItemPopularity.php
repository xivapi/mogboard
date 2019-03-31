<?php

namespace App\Service\Items;

use App\Entity\PopularItem;
use App\Repository\PopularItemRepository;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ItemPopularity
{
    const MAX_HITS  = 5;
    const MAX_DELAY = (60 * 60 * 12);
    
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
        return $this->repository->findBy([], [ 'count' => 'desc' ], 20);
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
