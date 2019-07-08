<?php

namespace App\Service\Items;

use App\Common\Entity\ItemViews;
use App\Common\Repository\ItemPopularityRepository;
use App\Common\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class Views
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ItemPopularityRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(ItemViews::class);
    }
    
    /**
     * Record an item hit
     */
    public function hit(Request $request, int $itemId)
    {
        $key = "item_hit2_". sha1($request->getClientIp());

        // ignore if this user already performed a hit this hour
        if (Redis::Cache()->get($key)) {
            return;
        }

        Redis::Cache()->set($key, true);

        /** @var ItemViews $entity */
        $entity = $this->repository->findOneBy([ 'item' => $itemId ]) ?: new ItemViews();

        $entity
            ->setItem($itemId)
            ->setLastview(time());
        
        $this->em->persist($entity);
        $this->em->flush();

        //
        // Track usage
        //
        $hits = Redis::cache()->get($key .'_count');
        $hits = $hits ? ($hits + 1) : 0;
        if ($hits < 50) {
            Redis::cache()->set($key .'_count', $hits);
            $sql = "UPDATE companion_items SET last_visit = ". time() ." WHERE item_id = ". $itemId;
            $sql = $this->em->getConnection()->prepare($sql);
            $sql->execute();
        }
    }
}
