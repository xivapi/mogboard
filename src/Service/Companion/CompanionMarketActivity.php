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
        return $user ? Redis::cache()->get("mb_user_home_feed_generated_{$user->getId()}") : null;
    }

    /**
     * Build the market feeds for all the users.
     */
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

            $section->overwrite("{$i} / {$usersTotal} - {$id}  {$name}");

            $feed = [];
            $checkGeneratedRecent = "mb_user_home_feed_recent_{$id}";
            $cacheGeneratedFeed   = "mb_user_home_feed_generated_{$id}";

            if (Redis::cache()->get($checkGeneratedRecent)) {
                continue;
            }

            $section->overwrite("{$i} / {$usersTotal} - {$id}  {$name}  -- Building recent alert events");
            $feed = $this->addRecentAlerts($id, $feed);

            $section->overwrite("{$i} / {$usersTotal} - {$id}  {$name}  -- Building recent price lists");
            $feed = $this->addRecentPriceUpdates($id, $feed, $section);

            /**
             * Order by timestamp and slice
             */
            Arrays::sortBySubKey($feed, 'timestamp');
            array_splice($feed, 150);

            // cache for the user, the time on this is random so not all feeds are generated same time
            Redis::cache()->set($checkGeneratedRecent, $feed, mt_rand(60, 570));
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
        /**
         * Get all the alert events for this user
         */
        $deadline = time() - (60 * 60 * 24 * 7);
        $stmt = $this->em->getConnection()->prepare(
            "SELECT event_id, added, `data` FROM users_alerts_events WHERE added > {$deadline} AND user_id = '{$userId}' ORDER BY added DESC"
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
            /** @var UserAlert $alert */
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
                        'type'        => $alert->getTriggerType(),
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
    private function addRecentPriceUpdates(string $userId, array $feed, ConsoleSectionOutput $section)
    {
        $section->writeln("Getting character ...");
        
        /**
         * The user MUST have a verified main character, this is how I get the server...
         */
        $stmt = $this->em->getConnection()->prepare(
            "SELECT server FROM users_characters WHERE user_id = '{$userId}' AND main = 1 LIMIT 1"
        );
    
        $stmt->execute();
        $character = $stmt->fetch();

        // if no character, skip
        if (empty($character)) {
            return $feed;
        }

        // Get users DC
        $dc = GameServers::getDataCenter($character['server']);

        /**
         * Get lists
         */
        $section->writeln("Fetching lists");
        $stmt = $this->em->getConnection()->prepare(
            "SELECT id, `name`, items FROM users_lists WHERE custom = 0 AND user_id = '{$userId}' ORDER BY added DESC"
        );
        $stmt->execute();

        $lists = $stmt->fetchAll();

        // if no lists, SKIP!
        if (empty($lists)) {
            return $feed;
        }
        
        /**
         * First we need to get all the market info, to do this
         * we need all the unique item ids so we can do it in
         * 1 big fetch list.
         */
        $itemIds = [];
        $itemIdsToLists = [];
        
        foreach ($lists as $list) {
            $listItems = unserialize($list['items']);
            $itemIds   = array_merge($itemIds, $listItems);
            
            foreach ($listItems as $id) {
                $itemIdsToLists[$id] = [
                    'id'   => $list['id'],
                    'name' => $list['name']
                ];
            }
        }
        
        $itemIds = array_unique($itemIds);
        arsort($itemIds);

        // some how still no items? SKIP!!
        if (empty($itemIds)) {
            return $feed;
        }
    
        $section->writeln("Items: ". count((array)$itemIds));
        
        // set a max
        array_splice($itemIds, 200);

        /**
         * Only fetch the last sale price + the current cheapest for each server
         */
        $xivapi = new XIVAPI();
        $xivapi->queries([
            'max_history' => 1,
            'max_prices'  => 1,
        ]);

        // only record 15 entries, otherwise it gets spammy
        $countPerList = [];
        $countMax = 20;
        
        // fetch in batches of 50
        foreach(array_chunk($itemIds, 5) as $j => $itemIdsChunked) {
            $section->writeln("Chunk: {$j}");
            
            // get market info
            $market = $xivapi->market->items($itemIdsChunked, [], $dc);
    
            /**
             * Process market data
             */
            foreach ($market as $i => $itemMarket) {
                $itemId = $itemIdsChunked[$i];
                $list   = $itemIdsToLists[$itemId];
                $listId = $list['id'];
                
                foreach ($itemMarket as $server => $serverMarket) {
                    $lastSale = $serverMarket->History[0] ?? null;
                    $cheapest = $serverMarket->Prices[0]  ?? null;
    
                    $countPerList[$listId] = isset($countPerList[$listId]) ? $countPerList[$listId] + 1 : 1;
    
                    if ($countPerList[$listId] > $countMax) {
                        break;
                    };
                    
                    $feed[] = [
                        'timestamp' => $serverMarket->Updated,
                        'type'      => self::TYPE_LIST_PRICES,
                        'data'      => [
                            'server'   => $server,
                            'itemId'   => $itemId,
                            'lastSale' => $lastSale,
                            'cheapest' => $cheapest,
                            'list'     => $list
                        ],
                    ];
                }
            }
        }

        return $feed;
    }
}
