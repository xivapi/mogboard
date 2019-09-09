<?php

namespace App\Controller;

use App\Common\Entity\Maintenance;
use App\Common\Entity\UserAlert;
use App\Common\Exceptions\JsonException;
use App\Common\Game\GameServers;
use App\Common\Service\Redis\RedisTracking;
use App\Common\User\Users;
use App\Common\Utils\Language;
use App\Service\Companion\CompanionMarket;
use App\Service\Companion\CompanionStatistics;
use App\Service\GameData\GameDataSource;
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
    /** @var CompanionCensus */
    private $companionCensus;
    /** @var CompanionStatistics */
    private $companionStatistics;
    /** @var CompanionMarket */
    private $companionMarket;
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
        CompanionCensus $companionCensus,
        CompanionStatistics $companionStatistics,
        CompanionMarket $companionMarket,
        Users $users,
        UserAlerts $userAlerts,
        UserLists $userLists,
        Popularity $itemPopularity,
        Views $itemViews
    ) {
        $this->em                  = $em;
        $this->gameDataSource      = $gameDataSource;
        $this->companionCensus     = $companionCensus;
        $this->companionStatistics = $companionStatistics;
        $this->companionMarket     = $companionMarket;
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
        $time = microtime(true);
        
        if ($itemId === '-id-') {
            throw new JsonException("Something went wrong during the request... Contact a staff admin.");
        }
        
        if (filter_var($itemId, FILTER_VALIDATE_INT) === false) {
            return $this->redirectToRoute('404');
        }
    
        //
        // Grab item
        //
        $item = $this->gameDataSource->getItem($itemId);

        if ($item == null || !isset($item->ItemSearchCategory->ID)) {
            return $this->redirectToRoute('404');
        }
    
        // tracking
        RedisTracking::increment('PAGE_VIEW');
    
        // handle item
        //$item = Language::handle($item); done in gameDataSource
        
        // grab user if they're online
        $user = $this->users->getUser(false);

        // Grab server info
        $server     = GameServers::getServer($request->get('server'));
        $dcServers  = GameServers::getDataCenterServers($server);
        $dc         = GameServers::getDataCenter($server);
        
        /*
        // grab item queue info from db
        $itemQueue = "SELECT * FROM companion_market_items WHERE item = ? AND server = ? LIMIT 1";
        $itemQueue = $this->em->getConnection()->prepare($itemQueue);
        $itemQueue->execute([ $itemId, GameServers::getServerId($server) ]);
        $itemQueue = $itemQueue->fetch();
        */

        // bits n bobs
        $this->userLists->handleRecentlyViewed($itemId);
        $this->itemPopularity->hit($request, $itemId);
        $this->itemViews->hit($request, $itemId);
        $this->users->setLastUrl($request);
        
        // grab market for this dc
        $market        = $this->companionMarket->get($dcServers, $itemId);
        $times         = [];
        $updated       = 0;
        $activityCount = 0;
        
        foreach ($market as $marketServer => $md) {
            if ($md == null) {
                continue;
            }

            if ($md['Updated'] > $updated) {
                $updated = $md['Updated'];
            }
    
            $activityCount += count($md['Prices']);
            $activityCount += count($md['History']);

            $times[] = [
                'name'     => $marketServer,
                'updated'  => $md['Updated'],
                'priority' => $md['UpdatePriority'] ?? null,
            ];
        }
        
        // grab market census
        $census = $this->companionCensus->generate($dc, $itemId, $market);
        
        // get market item entry
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare("SELECT * FROM companion_market_item_source WHERE item = {$itemId}");
        $stmt->execute();
        
        // shopz
        $shops = [];
        if ($shopData = $stmt->fetch()) {
            $shopData = json_decode($shopData['data'], true);
            $shopData = array_unique($shopData);
            $shopData = array_values($shopData);
            
            foreach ($shopData as $shopId) {
                $shops[] = Language::handle(
                    Redis::Cache()->get("xiv_GilShopData_{$shopId}")
                );
            }
        }

        $loadSpeed = microtime(true) - $time;
        
        // if the item was updated less than X mins ago, remove the updating check
        if ($updated > (time() - (60 * 10))) {
            Redis::cache()->delete('mogboard_updating_' . $itemId . $dc);
        }
        
        $isBeingUpdated = Redis::cache()->get('mogboard_updating_' . $itemId . $dc);

        // response
        $data = [
            'item'           => $item,
            'market'         => $market,
            'census'         => $census,
            'queue'          => $itemQueue,
            'faved'          => $user ? $user->hasFavouriteItem($itemId) : false,
            'lists'          => $user ? $user->getCustomLists() : [],
            'cheapest'       => $this->companionStatistics->cheapest($market),
            'shops'          => $shops,
            'update_times'   => $times,
            'activity_count' => $activityCount,
            'chart_max'      => 100,
            'load_speed'     => round($loadSpeed, 3),
            'is_updating'    => $isBeingUpdated,
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
                'message' => 'Maintenance is in progress, manual update is not available at this time. Please try again later.'
            ]);
        }

        $user = $this->users->getUser(true);
        
        $itemId = (int)$itemId;
        $server = GameServers::getServerId(GameServers::getServer());
        $dc     = GameServers::getDataCenter(GameServers::getServer());
    
        $key1 = 'mogboard_'. __METHOD__ . $itemId . $dc;
        $key2 = 'mogboard_'. __METHOD__ . $user->getId();

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
        
        Redis::cache()->set('mogboard_updating_' . $itemId . $dc, true, 500);
        
        // if response was OK, set restrictions for user to avoid spam
        if ($ok) {
            $count = Redis::Cache()->get($key2);
            $count = $count ? $count + 1 : 1;
            Redis::Cache()->set($key2, $count, (60 * 30));
            Redis::Cache()->set($key1, true, (60 * 30));
        }
        
        return $this->json([
            'message' => $message
        ]);
    }
}
