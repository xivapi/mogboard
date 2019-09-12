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
        //$this->itemViews->hit($request, $itemId);
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

            /* TODO
            if ($md['Updated'] > $updated) {
                $updated = $md['Updated'];
            }
            */
    
            $activityCount += count($md['listings']);
            $activityCount += count($md['recentHistory']);

            $times[] = [
                'name'     => $marketServer,
                'updated'  => $md['lastUploadTime']
            ];
        }

        // grab market census
        $census = $this->companionCensus->generate($dc, $itemId, $market);

        $loadSpeed = microtime(true) - $time;

        // response
        $data = [
            'item'           => $item,
            'market'         => $market,
            'census'         => $census,
            'faved'          => $user ? $user->hasFavouriteItem($itemId) : false,
            'lists'          => $user ? $user->getCustomLists() : [],
            'cheapest'       => $this->companionStatistics->cheapest($market),
            'update_times'   => $times,
            'activity_count' => $activityCount,
            'chart_max'      => 100,
            'load_speed'     => round($loadSpeed, 3),
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
}
