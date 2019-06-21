<?php

namespace App\Controller;

use App\Common\Entity\Maintenance;
use App\Common\Entity\UserAlert;
use App\Common\Exceptions\BasicException;
use App\Common\Exceptions\JsonException;
use App\Common\Game\GameServers;
use App\Common\Service\Redis\RedisTracking;
use App\Common\User\Users;
use App\Common\Utils\Language;
use App\Constants\CompanionConstants;
use App\Service\Companion\CompanionStatistics;
use App\Service\GameData\GameDataSource;
use App\Service\Companion\Companion;
use App\Service\Companion\CompanionCensus;
use App\Service\Items\Popularity;
use App\Service\Items\Views;
use App\Common\Service\Redis\Redis;
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
    /** @var UserLists */
    private $userLists;
    /** @var Popularity */
    private $itemPopularity;
    /** @var Views */
    private $itemViews;
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
        Popularity $itemPopularity,
        Views $itemViews
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
        $this->itemViews           = $itemViews;
        $this->xivapi              = new XIVAPI();
    }
    
    /**
     * @Route("/market/{itemId}", name="item_page")
     */
    public function index(Request $request, $itemId)
    {
        if ($itemId === '-id-') {
            throw new JsonException("Something went wrong during the request... Contact a mogboard staff admin.");
        }
        
        if (filter_var($itemId, FILTER_VALIDATE_INT) === false) {
            return $this->redirectToRoute('404');
        }
        
        RedisTracking::increment('PAGE_VIEW');
        
        $this->users->setLastUrl($request);

        $user = $this->users->getUser(false);
        
        /** @var \stdClass $item */
        $item = $this->gameDataSource->getItem($itemId);
        
        if (!$item) {
            return $this->redirectToRoute('404');
        }
        
        $this->itemPopularity->hit($request, $itemId);
        $this->itemViews->hit($request, $itemId);

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
    
        $server     = GameServers::getServer($request->get('server'));
        $dc         = GameServers::getDataCenter($server);
        $dcServers  = GameServers::getDataCenterServers($server);
        $market     = $this->companion->getByDataCenter($dc, $itemId, 500);
        
        $canUpdate = false;
        foreach ($market as $marketData) {
            if (!isset($marketData->UpdatePriority)) {
                continue;
            }
            
            if (in_array($marketData->UpdatePriority, CompanionConstants::QUEUES)) {
                $canUpdate = true;
                break;
            }
        }

        // build census
        $census = Redis::Cache()->get("census_{$dc}_{$itemId}");
        if ($census == null) {
            // generate census and cache it, it's only cached for a short
            // period just to avoid spamming and multiple users.
            $census = $this->companionCensus->generate($item, $market)->getCensus();
            Redis::Cache()->set("census_{$dc}_{$itemId}", $census, 120);
        }
        
        // add to recently viewed
        $this->userLists->handleRecentlyViewed($itemId);
        
        // get market item entry
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare("SELECT * FROM companion_market_item_source WHERE item = {$itemId}");
        $stmt->execute();
        
        $shops = [];
        if ($shopData = $stmt->fetch()) {
            $shopData = json_decode($shopData['data']);
            $shopData = array_unique($shopData);
            
            foreach ($shopData as $shopId) {
                $shops[] = Language::handle(
                    Redis::Cache()->get("xiv_GilShopData_{$shopId}")
                );
            }
        }

        // get market stats
        $marketStats = $this->companionStatistics->stats();
        $updateTimes = [];
        foreach ($market as $m) {
            $updateTimes[] = [
                'name'     => GameServers::LIST[$m->Server],
                'updated'  => $m->Updated,
                'priority' => $m->UpdatePriority ?? null,
            ];
        }

        // response
        $data = [
            'item'           => $item,
            'market'         => $market,
            'marketStats'    => json_decode(json_encode($marketStats), true),
            'census'         => json_decode(json_encode($census), true),
            'canUpdate'      => $canUpdate,
            'junkvalue'      => 2.5,
            'recipes'        => $recipes,
            'faved'          => $user ? $user->hasFavouriteItem($itemId) : false,
            'lists'          => $user ? $user->getCustomLists() : [],
            'cheapest'       => $this->companionStatistics->cheapest($market),
            'shops'          => $shops,
            'update_times'   => $updateTimes,
            'chart_max'      => 100,
            'server'         => [
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
    
    /**
     * @Route("/market/{itemId}/update", name="item_update")
     */
    public function update(int $itemId)
    {
        
        /**
         * Check maintenance status
         * @var Maintenance $maintenance
         */
        $maintenance = $this->em->getRepository(Maintenance::class)->findOneBy(['id' => 1 ]);
        if ($maintenance && $maintenance->isCompanionMaintenance()) {
            return $this->json([
                'message' => 'Companion is down for maintenance or the mogboard accounts are offline, manual update is not available at this time. Please try again later.'
            ]);
        }

        $user = $this->users->getUser(true);
    
        /**
         * Check patron status of the user
         */
        if ($user->isPatron() == false) {
            return $this->json([
                'message' => 'Only patrons at this time can update items manually.'
            ]);
        }
        
        $itemId = (int)$itemId;
        $server = GameServers::getServerId(GameServers::getServer());
        $dc     = GameServers::getDataCenter(GameServers::getServer());
    
        $key1 = 'mogboard_'. __METHOD__ . $itemId . $dc;
        $key2 = 'mogboard_'. __METHOD__ . $user->getId();
    
        /**
         * If the user has reached their limit
         */
        if (Redis::Cache()->get($key2) >= 5) {
            return $this->json([
                'message' => 'You have reached the maximum of 5 items updated.<br>Try again in 1 hour.',
            ]);
        }
    
        /**
         * Check the item hasn't been updated already by someone
         */
        if (Redis::Cache()->get($key1)) {
            return $this->json([
                'message' => 'Item already updated recently by a MogBoard member!',
            ]);
        }
        
    
        /**
         * Request update
         */
        $xivapi = new XIVAPI();
        
        [$ok, $time, $message] = $xivapi->_private->manualItemUpdateForce(
            getenv('XIVAPI_COMPANION_KEY'),
            $itemId,
            $server
        );
        
        // if response was OK, set restrictions for user to avoid spam
        if ($ok) {
            $count = Redis::Cache()->get($key2);
            $count = $count ? $count + 1 : 1;
            Redis::Cache()->set($key2, $count);
            Redis::Cache()->set($key1, true);
        }
        
        return $this->json([
            'message' => $message
        ]);
    }
}
