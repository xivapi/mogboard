<?php

namespace App\Controller;

use App\Entity\UserAlert;
use App\Service\Companion\CompanionStatistics;
use App\Service\GameData\GameDataSource;
use App\Service\Companion\Companion;
use App\Service\Companion\CompanionCensus;
use App\Service\GameData\GameServers;
use App\Service\Items\ItemPopularity;
use App\Service\Redis\Redis;
use App\Service\User\Users;
use App\Service\UserAlerts\UserAlerts;
use App\Service\UserLists\UserLists;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

class ItemController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var GameDataSource */
    private $gameDataSource;
    /** @var Companion */
    private $companion;
    /** @var Companion */
    private $companionCensus;
    /** @var CompanionStatistics */
    private $companionStatistics;
    /** @var Users */
    private $users;
    /** @var UserAlerts */
    private $userAlerts;
    /** @var userLists */
    private $userLists;
    /** @var ItemPopularity */
    private $itemPopularity;
    /** @var XIVAPI */
    private $xivapi;
    
    public function __construct(
        EntityManagerInterface $em,
        GameDataSource $gameDataSource,
        Companion $companion,
        CompanionCensus $companionCensus,
        CompanionStatistics $companionStatistics,
        Users $users,
        UserAlerts $userAlerts,
        UserLists $userLists,
        ItemPopularity $itemPopularity
    ) {
        $this->em                  = $em;
        $this->gameDataSource      = $gameDataSource;
        $this->companion           = $companion;
        $this->companionCensus     = $companionCensus;
        $this->companionStatistics = $companionStatistics;
        $this->users               = $users;
        $this->userAlerts          = $userAlerts;
        $this->userLists           = $userLists;
        $this->itemPopularity      = $itemPopularity;
        $this->xivapi              = new XIVAPI();
    }
    
    /**
     * @Route("/market/{itemId}", name="item_page")
     */
    public function index(Request $request, int $itemId)
    {
        $this->users->setLastUrl($request);

        $user = $this->users->getUser(false);
        
        /** @var \stdClass $item */
        $item = $this->gameDataSource->getItem($itemId);
        
        if (!$item) {
            return $this->redirectToRoute('404');
        }
        
        $this->itemPopularity->hit($request, $itemId);
        
        // if it has recipes, grab those
        $recipes = [];
        if (isset($item['GameContentLinks']['Recipe']['ItemResult'])) {
            foreach ($item['GameContentLinks']['Recipe']['ItemResult'] as $id) {
                $recipe = $this->gameDataSource->getRecipe($id);
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
        $market     = $this->companion->getByDataCenter($dc, $itemId);

        // build census
        $census = Redis::Cache()->get("census_{$dc}_{$itemId}");
        if ($census == null) {
            // generate census and cache it, it's only cached for a short
            // period just to avoid spamming and multiple users.
            $census = $this->companionCensus->generate($market)->getCensus();
            Redis::Cache()->set("census_{$dc}_{$itemId}", $census, 60);
        }
        
        // add to recently viewed
        $this->userLists->handleRecentlyViewed($itemId);


        // response
        $data = [
            'item'      => $item,
            'market'    => $market,
            'census'    => $census,
            'junkvalue' => CompanionCensus::JUNK_PRICE_FACTOR,
            'recipes'   => $recipes,
            'faved'     => $user ? $user->hasFavouriteItem($itemId) : false,
            'lists'     => $user ? $user->getCustomLists() : [],
            'api_stats' => $this->companionStatistics->stats(),
            'cheapest'  => $this->companionStatistics->cheapest($market),
            'server'    => [
                'name'       => $server,
                'dc'         => $dc,
                'dc_servers' => $dcServers
            ],
            'alerts'    => [
                'users'             => $user ? $this->userAlerts->getAllForItemForCurrentUser($itemId) : [],
                'trigger_fields'    => UserAlert::TRIGGER_FIELDS,
                'trigger_operators' => UserAlert::TRIGGER_OPERATORS,
                'trigger_actions'   => [
                    UserAlert::TRIGGER_ACTION_CONTINUE => 'Continue',
                    UserAlert::TRIGGER_ACTION_DELETE   => 'Delete',
                    UserAlert::TRIGGER_ACTION_PAUSE    => 'Pause',
                ],
            ],
        ];
        
        return $this->render('Product/index.html.twig', $data);
    }
}
