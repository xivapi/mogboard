<?php

namespace App\Service\Companion;

use App\Common\Constants\RedisConstants;
use App\Common\Entity\User;
use App\Common\Entity\UserAlert;
use App\Common\Entity\UserAlertEvent;
use App\Common\Entity\UserList;
use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Common\Utils\Arrays;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

class CompanionMarketActivity
{
    const TYPE_ALERT_EVENT = 'ALERT_EVENT';
    const TYPE_LIST_PRICES = 'LIST_PRICES';
    
    /** @var User */
    private $user;
    /** @var array */
    private $feed = [];
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
    
    public function getFeed(?User $user = null)
    {
        return $user ? Redis::cache()->get("user_home_feed_{$user->getId()}") : null;
    }
    
    public function buildUserMarketFeeds()
    {
        $start = time();
        
        $console = new ConsoleOutput();
        $console->writeln('Building market feeds for users');
        $console = $console->section();
        
        $users = $this->users->getRepository()->findAll();
        
        // if they haven't been online in a week, stop generating their feed
        $timeout = time() - (60 * 60 * 24 * 7);
        
        /** @var User $user */
        foreach ($users as $user) {
            if ($user->getLastOnline() != 0 && $user->getLastOnline() < $timeout) {
                $console->overwrite("User: {$user->getUsername()} as not been online for a week, skipping ...");
                continue;
            }
            
            $console->overwrite("Building feed for: {$user->getUsername()}");
            $this->build($user);
        }
        
        $console->writeln("Complete");
        
        $duration = time() - $start;
        $console->writeln("Took: ". $duration / 60 . " minutes");
    }
    
    private function build(User $user)
    {
        $this->user = $user;
        
        $this->addRecentAlerts();
        $this->addRecentPriceUpdates();
        
        Arrays::sortBySubKey($this->feed, 'timestamp');
        
        // cache feed
        Redis::cache()->set("user_home_feed_{$user->getId()}", $this->feed, RedisConstants::TIME_7_DAYS);
    }
    
    /**
     * Add any recent alert triggers
     */
    private function addRecentAlerts()
    {
        /** @var UserAlert[] $alerts */
        $alerts = $this->user->getAlerts();
        
        // if no alerts, skip
        if (empty($alerts)) {
            return;
        }
        
        foreach ($alerts as $alert) {
            /** @var UserAlertEvent[] $events */
            $events = $alert->getEvents();
    
            // if no events, skip
            if (empty($events)) {
                return;
            }
            
            foreach ($events as $event) {
                /**
                 * Build a mini market table
                 */
                $marketTable = [];
                foreach ($event->getData() as $row) {
                    $prices = $row[1];
                    $prices->_Server = $row[0];
                    $marketTable[] = $prices;
                }
                
                $this->feed[] = [
                    'timestamp'  => $event->getAdded(),
                    'type'       => self::TYPE_ALERT_EVENT,
                    'data'       => [
                        'market' => $marketTable,
                        'alert'  => [
                            'itemId'      => $alert->getItemId(),
                            'lastChecked' => $alert->getLastChecked(),
                            'name'        => $alert->getName(),
                            'triggers'    => $alert->getTriggerConditionsFormatted(),
                            'dc'          => $alert->isTriggerDataCenter(),
                            'hq'          => $alert->isTriggerHq(),
                            'dps_perk'    => $alert->isKeepUpdated(),
                        ],
                    ],
                ];
            }
        }
    }
    
    /**
     * Add recent price updates
     */
    private function addRecentPriceUpdates()
    {
        /** @var  $lists */
        $lists = $this->user->getLists();
        
        // if no lists
        if (empty($lists)) {
            return;
        }
        
        /**
         * First we need to get all the market info, to do this
         * we need all the unique item ids so we can do it in
         * 1 big fetch list.
         */
        $itemIds = [];
        $itemIdsToLists = [];
        
        /** @var UserList $list */
        foreach ($lists as $list) {
            $itemIds = array_merge($itemIds, $list->getItems());
            
            foreach ($list->getItems() as $id) {
                $itemIdsToLists[$id] = $list;
            }
        }
        
        $itemIds = array_unique($itemIds);
        arsort($itemIds);
    
        /**
         * Only fetch the last sale price + the current cheapest for each server
         */
        $xivapi = new XIVAPI();
        $xivapi->queries([
            'max_history' => 1,
            'max_prices'  => 1,
        ]);
        
        $server = GameServers::getServer();
        $dc     = GameServers::getDataCenter($server);
        
        // only record 15 entries, otherwise it gets spammy
        $countPerList = [];
        $countMax = 5;
        
        // fetch in batches of 50
        foreach(array_chunk($itemIds, 50) as $itemIdsChunked) {
            /**
             * Check cache first
             */
            $key = "user_home_feed_{$this->user->getId()}_". md5(implode('-', $itemIdsChunked));
            if (!$market = Redis::cache()->get($key)) {
                $market = $xivapi->market->items($itemIdsChunked, [], $dc);
                Redis::cache()->set($key, $market, 300);
            }
    
            /**
             * Process market data
             */
            foreach ($market as $i => $itemMarket) {
                $itemId = $itemIdsChunked[$i];
                $list = $itemIdsToLists[$itemId];
                
                foreach ($itemMarket as $server => $serverMarket) {
                    $lastSale = $serverMarket->History[0] ?? null;
                    $cheapest = $serverMarket->Prices[0] ?? null;
    
                    $countPerList[$list->getId()] = isset($countPerList[$list->getId()])
                        ? $countPerList[$list->getId()] + 1
                        : 1;
    
                    if ($countPerList[$list->getId()] > $countMax) break;
                    
                    $this->feed[] = [
                        'timestamp' => $serverMarket->Updated,
                        'type' => self::TYPE_LIST_PRICES,
                        'data' => [
                            'server'    => $server,
                            'itemId'    => $itemId,
                            'lastSale'  => $lastSale,
                            'cheapest'  => $cheapest,
                            'list' => [
                                'id' => $list->getId(),
                                'name' => $list->getName()
                            ]
                        ],
                    ];
                }
            }
        }
    }
}
