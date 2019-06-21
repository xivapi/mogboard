<?php

namespace App\Service\UserRetainers;

use App\Common\Entity\Maintenance;
use App\Common\Entity\UserRetainer;
use App\Common\Exceptions\BasicException;
use App\Common\Game\GameServers;
use App\Common\Repository\UserRetainerRepository;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Common\Utils\Arrays;
use App\Common\Exceptions\JsonException;
use App\Exceptions\UnauthorisedRetainerOwnershipException;
use App\Service\Companion\Companion;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use MathPHP\Statistics\Average;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use XIVAPI\XIVAPI;

class UserRetainers
{
    // the maximum amount of time an unconfirmed retainer may exist.
    const MAX_LURK_TIME = (60 * 60 * 24);
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var UserRetainerRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(
        EntityManagerInterface $em,
        Users $users,
        Companion $companion
    ) {
        $this->em         = $em;
        $this->users      = $users;
        $this->companion  = $companion;
        $this->repository = $em->getRepository(UserRetainer::class);
        $this->console    = new ConsoleOutput();
    }

    /**
     * Get a retainer, the logged in user MUST be the owner.
     */
    public function get(string $retainerId)
    {
        $user = $this->users->getUser(true);

        return $this->repository->findOneBy([
            'id'        => $retainerId,
            'user'      => $user,
            'confirmed' => true,
        ]);
    }

    /**
     * Get a retainer
     */
    public function getViaUniqueRef(string $unique, bool $confirmed = false)
    {
        return $this->repository->findOneBy([
            'uniq'      => $unique,
            'confirmed' => $confirmed,
        ]);
    }

    /**
     * Add a new user retainer to the site
     */
    public function add(Request $request)
    {
        $name   = trim($request->get('name'));
        $server = ucwords(trim($request->get('server')));
        $itemId = (int)trim($request->get('itemId'));
    
        if (in_array(GameServers::getServerId($server), GameServers::MARKET_OFFLINE)) {
            throw new JsonException('Server currently not supported.');
        }
        
        $unique = UserRetainer::unique($name, $server);

        if ($this->getViaUniqueRef($unique, true)) {
            throw new JsonException('Retainer already exists and is confirmed.');
        }

        $retainer = (new UserRetainer())
            ->setUser($this->users->getUser(true))
            ->setName($name)
            ->setServer($server)
            ->setUniq($unique)
            ->setConfirmItem($itemId)
            ->setConfirmPrice(mt_rand(1000000, 15000000));

        $this->save($retainer);
        return true;
    }

    /**
     * Confirm a characters ownership
     */
    public function confirm(UserRetainer $retainer)
    {
        /**
         * Check maintenance status
         * @var Maintenance $maintenance
         */
        $maintenance = $this->em->getRepository(Maintenance::class)->findOneBy(['id' => 1 ]);
        if ($maintenance && $maintenance->isCompanionMaintenance()) {
            return [
                false,
                'Companion is down for maintenance or the mogboard accounts are offline, retainer verification is not available at this time. Please try again later.'
            ];
        }

        $found = false;
        $message = '';

        // enforce the user is online
        $this->users->getUser(true);
        
        // if the retainer was updated recently, don't do anything
        if ($retainer->isRecent()) {
            return [
                $found,
                'Error D4: Tried too recently',
            ];
        }

        // confirmation variables
        $server    = $retainer->getServer();
        $itemId    = $retainer->getConfirmItem();
        $itemPrice = $retainer->getConfirmPrice();
        $name      = $retainer->getName();
    
        // mark updated regardless of what happens
        $retainer->setUpdated(time());
        $this->save($retainer);
        
        // query market
        $xivapi = new XIVAPI();
        try {
            $market = $xivapi->_private->itemPrices(
                getenv('XIVAPI_COMPANION_KEY'),
                $itemId,
                $server
            );
            
            if (isset($market->Error)) {
                throw new BasicException("XIVAPI Error 42: ". $market->Message);
            }
            
            if (!isset($market->entries)) {
                throw new BasicException("XIVAPI Error 88: Market entry data was empty");
            }

            if (empty($market->entries)) {
                throw new BasicException("There were no sales on the market for this item on this server.");
            }
    
            // find listing
            foreach ($market->entries as $entry) {
                if (strtolower($entry->sellRetainerName) == strtolower($name) && $entry->sellPrice == $itemPrice) {
                    $found = true;
                    break;
                }
            }
        } catch (\Exception $ex) {
            return [
                false, "Error B2: {$ex->getMessage()}"
            ];
        }

        // could not verify
        if ($found === false) {
            return [
                false,
                'Error C3: Could not find the item at the correct price on the market, try again soon as companion may be having issues.'
            ];
        }

        // confirm ownership and save
        $retainer
            ->setConfirmPrice(0)
            ->setConfirmItem(0)
            ->setConfirmed(true)
            ->setUpdated(time());

        $this->save($retainer);
        
        return [
            true, $message
        ];
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserRetainer $userRetainer)
    {
        $this->em->persist($userRetainer);
        $this->em->flush();
        return true;
    }
    
    /**
     * Delete a retainer
     */
    public function delete(UserRetainer $userRetainer)
    {
        if ($userRetainer->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedRetainerOwnershipException();
        }
        
        $this->em->remove($userRetainer);
        $this->em->flush();
        return true;
    }

    /**
     * This will remove retainers which have not been verified for more than 24 hours.
     */
    public function removeLurkingRetainers()
    {
        $console = new ConsoleOutput();
        $console->writeln("Removing lurking retainers.");

        $retainers = $this->repository->findBy([ 'confirmed' => false ], [ 'added' => 'asc' ]);
        $deadline  = time() - self::MAX_LURK_TIME;
        
        /** @var UserRetainer $retainer */
        foreach ($retainers as $retainer) {
            if ($deadline > $retainer->getAdded()) {
                $this->em->remove($retainer);
                $this->em->flush();
            }
        }

        $console->writeln("Complete");
    }
    
    /**
     * This will link retainers to the companion api ID
     */
    public function linkCompanionApiIdentities()
    {
        $console = new ConsoleOutput();
        $console->writeln("Finding retainers on the companion database.");
        
        $retainers = $this->repository->findBy(
            [
                'confirmed' => true,
                'apiRetainerId' => null,
            ],
            [
                'updated' => 'desc',
            ],
            50
        );
        
        $console->writeln(count($retainers) .' retainers to find.');
        $console = $console->section();
        
        /** @var UserRetainer $retainer */
        foreach ($retainers as $retainer) {
            $console->overwrite("Finding: {$retainer->getName()} - {$retainer->getServer()}");
            
            // find retainer in companion table
            $companionData = $this->repository->findRetainerInCompanionTable(
                $retainer->getName(),
                GameServers::getServerId($retainer->getServer())
            );
            
            // if it isn't null, assign it!
            if ($companionData !== null) {
                $retainer->setApiRetainerId($companionData['id']);
            }
            
            $retainer->setUpdated(time());
            $this->em->persist($retainer);
            $this->em->flush();
        }

        $console->writeln("Complete");
    }

    /**
     * Get the retainer store!
     */
    public function getStore(UserRetainer $retainer)
    {
        $key = __METHOD__ . $retainer->getId();

        // check cache
        if ($data = Redis::cache()->get($key)) {
            return $data;
        }

        if (empty($retainer->getApiRetainerId())) {
            throw new BasicException("Your retainer has not yet been synced on XIVAPI. This can be caused by the retainer never selling anything or is new to the site. Try again soon or contact a Mogboard staff member");
        }

        try {
            // get retainer items
            $data = (new XIVAPI())->_private->retainerItems(
                getenv('XIVAPI_COMPANION_KEY'),
                $retainer->getApiRetainerId()
            );
        } catch (ClientException $ex) {
            $error = json_decode($ex->getResponse()->getBody()->getContents());
            throw new BasicException(
                "{$error->Subject} -- {$error->Message} -- {$error->Note}"
            );
        }

        Redis::cache()->set($key, $data, 900);
        return $data;
    }
    
    /**
     * Get market data for a retainer
     */
    public function getMarketData($items, bool $homeOnly = false)
    {
        $key = __METHOD__ . md5(serialize($items)) . ($homeOnly ? 'homeonly' : 'crossworldworld');
    
        // check cache
        if ($data = Redis::cache()->get($key)) {
            return json_decode(json_encode($data), true);
        }
        
        // grab just the item ids
        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item->Item->ID;
        }
        
        // we only care about unique items
        $itemIds = array_unique($itemIds);
        
        $server  = GameServers::getServer();
        $dc      = GameServers::getDataCenter($server);
        $market  = $homeOnly
            ? $this->companion->getItemsOnServer($itemIds, $server)
            : $this->companion->getItemsOnDataCenter($itemIds, $dc);
    
        $serverMarketStats = [];
        $lastUpdatedTimes  = [];
        
        // go through all items and find some info about each one
        foreach ($itemIds as $i => $itemId) {
            $itemMarket = $market[$i];
    
            $lastUpdatedTimes[$itemId]  = [];
            $serverMarketStats[$itemId] = [
                'TotalForSale'        => 0,
                'RoughUpdateTime'     => 0,
                'Top5CheapestServers' => [],
                'Top5HistorySales'    => [],
            ];
        
            /**
             * Find the cheapest server and prices
             */
            foreach ($itemMarket as $server => $serverMarket) {
                foreach ($serverMarket->Prices as $price) {
                    $price->_Server = $server;
                    $serverMarketStats[$itemId]['Top5CheapestServers'][] = (array)$price;
                    $serverMarketStats[$itemId]['TotalForSale']++;
                }
            
                foreach ($serverMarket->History as $history) {
                    $history->_Server = $server;
                    $serverMarketStats[$itemId]['Top5HistorySales'][] = (array)$history;
                }
    
                $lastUpdatedTimes[$itemId][] = $serverMarket->Updated;
            }
        
            Arrays::sortBySubKey($serverMarketStats[$itemId]['Top5CheapestServers'], 'PricePerUnit', true);
            Arrays::sortBySubKey($serverMarketStats[$itemId]['Top5HistorySales'], 'PurchaseDate');
        
            array_splice($serverMarketStats[$itemId]['Top5CheapestServers'], 5);
            array_splice($serverMarketStats[$itemId]['Top5HistorySales'], 5);
    
            $serverMarketStats[$itemId]['RoughUpdateTime'] = round(Average::mean($lastUpdatedTimes[$itemId]));
        }
    
        Redis::cache()->set($key, $serverMarketStats, 60);
        return $serverMarketStats;
    }
}
