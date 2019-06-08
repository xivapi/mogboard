<?php

namespace App\Service\UserLists;

use App\Common\Constants\UserConstants;
use App\Common\Entity\User;
use App\Common\Entity\UserList;
use App\Common\Game\GameServers;
use App\Common\Repository\UserListRepository;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Common\Utils\Arrays;
use App\Exceptions\UnauthorisedListOwnershipException;
use App\Service\Companion\Companion;
use Doctrine\ORM\EntityManagerInterface;
use MathPHP\Statistics\Average;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserLists
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var UserListRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(
        EntityManagerInterface $em,
        Users $users,
        Companion $companion
    ) {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
        $this->repository   = $em->getRepository(UserList::class);
        $this->console      = new ConsoleOutput();
    }

    /**
     * Handle adding/removing an item from the favourites list
     */
    public function handleFavourite(int $itemId): UserList
    {
        $user = $this->users->getUser(true);
        $list = $this->getFavourites($user);

        // either add or remove the item
        $list->hasItem($itemId) ? $list->removeItem($itemId) : $list->addItem($itemId);
        $this->save($list);
        return $list;
    }
    
    /**
     * Handle adding the item to the recently viewed list
     */
    public function handleRecentlyViewed(int $itemId): ?UserList
    {
        $user = $this->users->getUser(false);
        
        if ($user == null) {
            return null;
        }
        
        $list = $this->getRecentlyViewed($user);
        $list->addItem($itemId);
        $this->save($list);
        return $list;
    }

    /**
     * Get a users favourites list
     */
    public function getFavourites(User $user)
    {
        $filters = [
            'customType' => UserList::CUSTOM_FAVOURITES,
            'user'       => $user
        ];

        if ($list = $this->repository->findOneBy($filters)) {
            return $list;
        }

        $list = new UserList();
        $list
            ->setName('Favourites')
            ->setCustomType(UserList::CUSTOM_FAVOURITES)
            ->setCustom(true)
            ->setUser($user);
        
        return $list;
    }
    
    /**
     * Get recently viewed lists
     */
    public function getRecentlyViewed(User $user)
    {
        $filters = [
            'customType' => UserList::CUSTOM_RECENTLY_VIEWED,
            'user'       => $user
        ];
    
        if ($list = $this->repository->findOneBy($filters)) {
            return $list;
        }
    
        $list = new UserList();
        $list
            ->setName('Recently Viewed')
            ->setCustomType(UserList::CUSTOM_RECENTLY_VIEWED)
            ->setCustom(true)
            ->setUser($user);
    
        return $list;
    }

    /**
     * Add an item to a list
     */
    public function addItem(UserList $userList, int $itemId): UserList
    {
        if ($userList->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedListOwnershipException();
        }

        if ($userList->hasItem($itemId) == true) {
            return $userList;
        }

        $userList->addItem($itemId);
        $this->save($userList);
        return $userList;
    }

    /**
     * Remove an item from a list
     */
    public function removeItem(UserList $userList, int $itemId): UserList
    {
        if ($userList->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedListOwnershipException();
        }

        if ($userList->hasItem($itemId) == false) {
            return $userList;
        }

        $userList->removeItem($itemId);
        $this->save($userList);
        return $userList;
    }

    /**
     * Create a brand new list
     */
    public function create(string $name, int $itemId): UserList
    {
        $user = $this->users->getUser(true);
        
        if (count($user->getLists()) > UserConstants::MAX_LISTS) {
            return null;
        }
        
        $list = new UserList();
        $list
            ->setUser($user)
            ->setName(trim($name))
            ->setItems([ $itemId ]);

        $this->save($list);
        return $list;
    }

    /**
     * Rename a list
     */
    public function rename(UserList $userList, string $name): UserList
    {
        if ($userList->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedListOwnershipException();
        }

        $userList->setName(trim($name));
        $this->save($userList);
        return $userList;
    }

    /**
     * Save a list
     */
    public function save(UserList $list): void
    {
        $list->setUpdated(time());
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Delete a list
     */
    public function delete(UserList $list): void
    {
        if ($list->getUser() !== $this->users->getUser(true)) {
            throw new UnauthorisedListOwnershipException();
        }

        $this->em->remove($list);
        $this->em->flush();
    }

    /**
     * Get market data for a list
     */
    public function getMarketData(UserList $list, bool $homeOnly = false)
    {
        $key = __METHOD__ . $list->getId();
    
        $items   = $list->getItems();
        
        if (empty($items)) {
            return null;
        }
    
        // check cache
        if ($data = Redis::cache()->get($key)) {
            return $data;
        }
        
        $server  = GameServers::getServer();
        $dc      = GameServers::getDataCenter($server);
        $market  = $homeOnly
            ? $this->companion->getItemsOnServer($items, $server)
            : $this->companion->getItemsOnDataCenter($items, $dc);
        
        $serverMarketStats = [];
        $lastUpdatedTimes  = [];
        
        // go through all items and find some info about each one
        foreach ($items as $i => $itemId) {
            $itemMarket = $market[$i] ?? null;
            
            if ($itemMarket == null) {
                $serverMarketStats[$itemId] = null;
                continue;
            }
    
            $lastUpdatedTimes[$itemId]  = [];
            $serverMarketStats[$itemId] = [
                'TotalForSale' => 0,
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
