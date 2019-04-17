<?php

namespace App\Service\UserRetainers;

use App\Entity\UserRetainer;
use App\Exceptions\GeneralJsonException;
use App\Repository\UserRetainerRepository;
use App\Service\Companion\Companion;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use XIVAPI\XIVAPI;

class UserRetainers
{
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
     * Get a retainer
     */
    public function get(string $unique, bool $confirmed = false)
    {
        return $this->repository->findOneBy([
            'uniq'    => $unique,
            'confirmed' => $confirmed,
        ]);
    }

    /**
     * Add a new character to the site
     */
    public function add(Request $request)
    {
        $name   = trim($request->get('name'));
        $server = ucwords(trim($request->get('server')));
        $itemId = (int)trim($request->get('itemId'));
        
        $unique = UserRetainer::unique($name, $server);

        if ($this->get($unique, true)) {
            throw new GeneralJsonException('Retainer already exists and is confirmed.');
        }

        $retainer = (new UserRetainer())
            ->setUser($this->users->getUser())
            ->setName($name)
            ->setServer($server)
            ->setUniq($unique)
            ->setSlug($name)
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
        // enforce the user is online
        $this->users->getUser();
        
        // if the retainer was updated recently, don't do anything
        if ($retainer->isRecent()) {
            return false;
        }

        // confirmation variables
        $server    = $retainer->getServer();
        $itemId    = $retainer->getConfirmItem();
        $itemPrice = $retainer->getConfirmPrice();
        $name      = $retainer->getName();
        
        // query market
        $xivapi = new XIVAPI();
        $market = $xivapi->_private->itemPrices(
            getenv('XIVAPI_COMPANION_KEY'),
            $itemId,
            $server
        );
        
        // find listing
        $found = false;
        foreach ($market->entries as $entry) {
            if ($entry->sellRetainerName == $name && $entry->sellPrice == $itemPrice) {
                $found = true;
                break;
            }
        }
        
        if ($found === false) {
            $retainer->setUpdated(time());
            $this->save($retainer);
            return false;
        }

        // confirm ownership and save
        $retainer
            ->setConfirmPrice(0)
            ->setConfirmItem(0)
            ->setConfirmed(true)
            ->setUpdated(time());

        $this->save($retainer);
        return true;
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserRetainer $obj)
    {
        $this->em->persist($obj);
        $this->em->flush();
        return true;
    }
    
    /**
     * Confirm ownership
     */
    public function confirmOwnership()
    {
        $retainers = $this->repository->findBy(
            [ 'confirmed' => false ],
            [ 'updated' => 'desc' ],
            50
        );
        
        $console = new ConsoleOutput();
        $console = $console->section();
        
        /** @var UserRetainer $retainer */
        foreach ($retainers as $retainer) {
            $console->overwrite("Checking: {$retainer->getName()} on {$retainer->getServer()}");
            
            $market = $this->companion->getByServer(
                $retainer->getServer(),
                $retainer->getConfirmItem()
            );
            
            print_r($market);
            die;
        }
    }
}
