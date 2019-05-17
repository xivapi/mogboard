<?php

namespace App\Service\UserRetainers;

use App\Common\Entity\Maintenance;
use App\Common\Entity\UserRetainer;
use App\Common\Game\GameServers;
use App\Common\Repository\UserRetainerRepository;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Entity\CompanionRetainer;
use App\Exceptions\GeneralJsonException;
use App\Exceptions\UnauthorisedRetainerOwnershipException;
use App\Service\Companion\Companion;
use Doctrine\ORM\EntityManagerInterface;
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
            throw new GeneralJsonException('Server currently not supported.');
        }
        
        $unique = UserRetainer::unique($name, $server);

        if ($this->getViaUniqueRef($unique, true)) {
            throw new GeneralJsonException('Retainer already exists and is confirmed.');
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
            
            if ($market->Error) {
                return [
                    false, "Error A1: {$market->Message}"
                ];
            }
    
            // find listing
            foreach ($market->entries as $entry) {
                if ($entry->sellRetainerName == $name && $entry->sellPrice == $itemPrice) {
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

        // get retainer items
        $data = (new XIVAPI())->_private->retainerItems(
            getenv('XIVAPI_COMPANION_KEY'),
            $retainer->getApiRetainerId()
        );

        Redis::cache()->set($key, $data);
        return $data;
    }
}
