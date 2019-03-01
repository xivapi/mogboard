<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Service\Redis\Redis;
use App\Service\Companion\Companion;
use App\Service\Companion\CompanionCensus;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Redis */
    private $cache;
    /** @var Companion */
    private $companion;
    /** @var Companion */
    private $companionCensus;
    /** @var Users */
    private $users;
    
    public function __construct(
        EntityManagerInterface $em,
        Redis $cache,
        Companion $companion,
        CompanionCensus $companionCensus,
        Users $users
    ) {
        $this->em = $em;
        $this->cache = $cache;
        $this->companion = $companion;
        $this->companionCensus = $companionCensus;
        $this->users = $users;
    }
    
    /**
     * @Route("/market/{itemId}", name="item_page")
     */
    public function index(int $itemId)
    {
        $user = $this->users->getUser();
        
        /** @var \stdClass $item */
        $item = $this->cache->get("xiv_Item_{$itemId}") ?: false;
        
        if (!$item) {
            return $this->redirectToRoute('404');
        }
        
        // if it has recipes, grab those
        $recipes = [];
        if (isset($item['GameContentLinks']['Recipe']['ItemResult'])) {
            foreach ($item['GameContentLinks']['Recipe']['ItemResult'] as $id) {
                $recipe = $this->cache->get("xiv_Recipe_{$id}");
                $recipes[] = [
                    'id'        => $id,
                    'level'     => $recipe['RecipeLevelTable']['ClassJobLevel'],
                    'classjob'  => ucwords($recipe['ClassJob']['Name']),
                    'icon'      => $recipe['ClassJob']['Icon'],
                ];
            }
        }
    
        $server     = GameServers::getServer();
        $dc         = GameServers::getDataCenter($server);
        $dcServers  = GameServers::getDataCenterServers($server);
    
        $market = $this->companion->getMultiServer($dcServers, $itemId);
        $census = $this->companionCensus->generate($market);
    
        return $this->render('Product/index.html.twig', [
            'item'     => $item,
            'market'   => $market,
            'census'   => $census,
            'recipes'  => $recipes,
            'alerts'   => $this->em->getRepository(Alert::class)->findBy([ 'itemId' => $itemId ]),
            'faved'    => $user ? $user->hasFavouriteItem($itemId) : false,
            'lists'    => $user ? $user->getListsPersonal() : [],
            'server'   => [
                'name'       => $server,
                'dc'         => $dc,
                'dc_servers' => $dcServers
            ]
        ]);
    }
    
    /**
     * @Route("/market/{itemId}/refresh", name="item_refresh")
     */
    public function refresh(int $itemId)
    {
        return $this->json([ 'ok' => true ]);
    }
}
