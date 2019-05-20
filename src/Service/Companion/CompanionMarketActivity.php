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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use XIVAPI\XIVAPI;

class CompanionMarketActivity
{
    const TYPE_ALERT_EVENT = 'ALERT_EVENT';
    const TYPE_LIST_PRICES = 'LIST_PRICES';

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function getFeed(?User $user = null)
    {
        return $user ? Redis::cache()->get("user_home_feed_{$user->getId()}") : null;
    }
    
    public function buildUserMarketFeeds()
    {
        $start = time();

        $console = new ConsoleOutput();
        $console->writeln("Building market feeds for users ...");
        $section = $console->section();

        /**
         * Grab all users who have been online in past 7 days or are new
         */
        $deadline = time() - (60 * 60 * 24 * 7);
        $stmt = $this->em->getConnection()->prepare(
            "SELECT id, username FROM users WHERE (last_online > {$deadline} OR last_online = 0)"
        );
        $stmt->execute();

        $users      = $stmt->fetchAll();
        $usersTotal = count($users);
        $console->writeln("Total Users: ". number_format($usersTotal));

        foreach ($users as $i => $user) {
            $id   = $user['id'];
            $name = $user['username'];

            $section->writeln("{$i} / {$usersTotal} - {$id}  {$name}");

            $feed = [];
            $checkGeneratedRecent = "mb_user_home_feed_recent_{$id}";
            $cacheGeneratedFeed   = "mb_user_home_feed_generated_{$id}";

            if (Redis::cache()->get($checkGeneratedRecent)) {
                continue;
            }

            $section->writeln("{$i} / {$usersTotal} - {$id}  {$name}  -- Building recent alert events");
            $feed = $this->addRecentAlerts($id, $feed);

            $section->writeln("{$i} / {$usersTotal} - {$id}  {$name}  -- Building recent price lists");
            $feed = $this->addRecentPriceUpdates($id, $feed)

            /**
             * Order by timestamp and slice the top 30 results.
             */
            Arrays::sortBySubKey($feed, 'timestamp');
            array_splice($feed, 0, 30);

            // cache for the user
            Redis::cache()->set($cacheGeneratedFeed, $feed, RedisConstants::TIME_7_DAYS);
        }

        /**
         * Report duration
         */
        $duration = time() - $start;
        $console->writeln("Complete");
        $console->writeln("Took: ". $duration / 60 . " minutes");
    }

    /**
     * Add any recent alert triggers
     */
    private function addRecentAlerts(string $userId, array $feed)
    {
        // we only care about events in the past 2 days
        $deadline = time() - (60 * 60 * 24 * 2);

        /**
         * Get all the alert events for this user
         */
        $stmt = $this->em->getConnection()->prepare(
            "SELECT event_id, added, `data` FROM users WHERE added > {$deadline} AND user_id = '{$userId}'"
        );
        $stmt->execute();

        $alertEvents = $stmt->fetchAll();
        
        // if no events, skip
        if (empty($alertEvents)) {
            return $feed;
        }

        // grab the alert for each event
        foreach ($alertEvents as $event) {
            $eventId    = $event['event_id'];
            $added      = $event['added'];
            $marketData = json_decode($event['data']);

            // grab alert
            $alert = $this->em->getRepository(UserAlert::class)->find($eventId);

            /**
             * Build a mini market table
             */
            $marketTable = [];
            foreach ($marketData as $row) {
                $prices          = $row[1];
                $prices->_Server = $row[0];
                $marketTable[]   = $prices;
            }

            $feed[] = [
                'timestamp'  => $added,
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

        unset($alertEvents);

        return $feed;
    }
    
    /**
     * Add recent price updates
     */
    private function addRecentPriceUpdates(string $userId, array $feed)
    {
        // disable this for now because i have no way to get a users server at the moment.
        return $feed;

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
            // ignore "recently viewed" for now as it takes forever
            if (in_array($list->getCustomType(), [ UserList::CUSTOM_RECENTLY_VIEWED ])) {
                continue;
            }

            $itemIds = array_merge($itemIds, $list->getItems());
            
            foreach ($list->getItems() as $id) {
                $itemIdsToLists[$id] = $list;
            }
        }
        
        $itemIds = array_unique($itemIds);
        arsort($itemIds);

        if (empty($itemIds)) {
            return;
        }

        array_splice($itemIds, 200);

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
