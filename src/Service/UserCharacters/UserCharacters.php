<?php

namespace App\Service\UserCharacters;

use App\Common\Constants\PatreonConstants;
use App\Common\Entity\UserCharacter;
use App\Common\Exceptions\BasicException;
use App\Common\Repository\UserCharacterRepository;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Common\Exceptions\JsonException;
use App\Exceptions\UnauthorisedRetainerOwnershipException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use XIV\Utils\Arrays;
use XIVAPI\XIVAPI;

class UserCharacters
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var UserCharacterRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;
    /** @var XIVAPI */
    private $xivapi;

    public function __construct(EntityManagerInterface $em, Users $users)
    {
        $this->em         = $em;
        $this->users      = $users;
        $this->repository = $em->getRepository(UserCharacter::class);
        $this->console    = new ConsoleOutput();
        $this->xivapi     = new XIVAPI();
    }

    /**
     * Get a single character
     */
    public function get(int $id, bool $confirmed = false)
    {
        return $this->repository->findOneBy([
            'lodestoneId' => $id,
            'confirmed'   => $confirmed,
        ]);
    }

    /**
     * Confirm a characters ownership
     */
    public function confirm(int $lodestoneId)
    {
        $user = $this->users->getUser(true);

        // run character verification
        $verification = $this->xivapi->character->verify($lodestoneId);
        
        if (isset($verification->Error)) {
            throw new JsonException($verification->Message);
        }

        // test if our Users pass phrase was found
        if (stripos($verification->Bio, $user->getCharacterPassPhrase()) === false) {
            throw new JsonException('Character auth code could not be found on the characters profile bio.', 200);
        }
        
        // grab character
        /** @var \stdClass $character */
        $json = $this->xivapi->character->get($lodestoneId);

        // confirm ownership and save
        $character = new UserCharacter();
        $character
            ->setLodestoneId($lodestoneId)
            ->setName($json->Character->Name)
            ->setServer($json->Character->Server)
            ->setAvatar($json->Character->Avatar)
            ->setUpdated(time())
            ->setUser($user)
            ->setConfirmed(true)
            ->setMain(empty($user->getCharacters()));
        
        $this->save($character);
        return true;
    }

    /**
     * @param UserCharacter $userCharacter
     */
    public function main(UserCharacter $userCharacter)
    {
        $user = $this->users->getUser(true);

        /** @var UserCharacter $character */
        foreach ($user->getCharacters() as $character) {
            $character->setMain(false);
            $this->em->persist($character);
            $this->em->flush();
        }

        // set new character as main
        $userCharacter->setMain(true);
        $this->em->persist($userCharacter);
        $this->em->flush();
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserCharacter $obj)
    {
        $this->em->persist($obj);
        $this->em->flush();
        return true;
    }
    
    /**
     * Delete a character
     */
    public function delete(UserCharacter $userCharacter)
    {
        $user = $this->users->getUser(true);
        
        if ($userCharacter->getUser() !== $user) {
            throw new UnauthorisedRetainerOwnershipException();
        }
        
        $this->em->remove($userCharacter);
        $this->em->flush();
        
        // remove benefit patron status if it exist and this was their "main"
        if ($userCharacter->isMain() && $user->isPatron(PatreonConstants::PATREON_BENEFIT)) {
            $benefits = PatreonConstants::ALERT_DEFAULTS;
            
            $user
                ->setPatron(0)
                ->setPatronBenefitUser(null)
                ->setAlertsMax($benefits['MAX'])
                ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT'])
                ->setAlertsUpdate($benefits['UPDATE_TIMEOUT']);
            
            $this->em->persist($user);
            $this->em->flush();
        }
        
        return true;
    }

    /**
     * Auto-update information on characters, eg: Name, Server and Avatar
     */
    public function autoUpdateCharacterDetails()
    {
        $a = microtime(true);
        $console = new ConsoleOutput();
        $console->writeln("Getting 500 characters to update");
        
        $characters = $this->repository->findLastUpdated(100);

        /** @var UserCharacter $character */
        foreach ($characters as $character) {
            $console->writeln("- {$character->getName()}");
            
            $data = $this->xivapi->character->get($character->getLodestoneId());

            // ensure we don't get stuck on a character
            $character->setUpdated(time());
            $this->save($character);

            if (!$data) {
                continue;
            }

            $character
                ->setName($data->Character->Name)
                ->setServer($data->Character->Server)
                ->setAvatar($data->Character->Avatar);

            $this->save($character);
        }
        
        $b = round(microtime(true) - $a, 3);
        $console->writeln("Done! {$b}");
    }

    /**
     * Get a characters history
     */
    public function getHistory(UserCharacter $userCharacter)
    {
        $key = __METHOD__ . $userCharacter->getId();

        // check cache
        if ($data = Redis::cache()->get($key)) {
            return $data;
        }
    
        try {
            // get retainer items
            $data = (new XIVAPI())->_private->characterHistory(
                getenv('XIVAPI_COMPANION_KEY'),
                $userCharacter->getLodestoneId()
            );
        } catch (ClientException $ex) {
            $error = json_decode($ex->getResponse()->getBody()->getContents());
            
            if (isset($error->Subject)) {
                throw new BasicException(
                    "{$error->Subject} -- {$error->Message} -- {$error->Note}"
                );
            }
            
            throw $ex;
        }

        Redis::cache()->set($key, $data, 900);
        return $data;
    }
    
    /**
     * Get stats for a specific user
     */
    public function getHistoryStats($history)
    {
        $totalGilSpent   = 0;
        $bestMonth   = [];
        $mostItemsBought = [];
        
        foreach ($history->History as $row) {
            $monthDate = date('Y F');
            $itemId    = $row->Item->ID;
            
            if (!isset($bestMonth[$monthDate])) {
                $bestMonth[$monthDate] = 0;
            }
    
            if (!isset($mostItemsBought[$itemId])) {
                $mostItemsBought[$itemId] = [
                    'itemId' => 0,
                    'qty' => 0,
                    'gil' => 0,
                ];
            }
            
            $totalGilSpent += $row->PriceTotal;
            $bestMonth[$monthDate] += $row->PriceTotal;
    
            $mostItemsBought[$itemId]['itemId'] = $itemId;
            $mostItemsBought[$itemId]['qty'] += $row->Quantity;
            $mostItemsBought[$itemId]['gil'] += $row->PriceTotal;
        }
        
        Arrays::sortBySubKey($mostItemsBought, 'gil');
        array_splice($mostItemsBought, 5);
        
        arsort($bestMonth);
        $bestMonth = [
            'date' => array_keys($bestMonth)[0],
            'gil'  => reset($bestMonth),
        ];
        
        $data = [
            'totalGilSpent'   => $totalGilSpent,
            'mostBusyMonth'   => $bestMonth,
            'mostBoughtItem'  => $mostItemsBought,
        ];
        
        return $data;
    }
    
    /**
     * Get a character
     */
    public function getCharacter(UserCharacter $userCharacter)
    {
        $key = __METHOD__ . $userCharacter->getId() . 200;

        // check cache
        if ($data = Redis::cache()->get($key)) {
            return $data;
        }
    
        try {
            // get retainer items
            $data = (new XIVAPI())->character->get(
                $userCharacter->getLodestoneId(), [ 'FR', 'FC', 'FCM' ], true
            );
        } catch (ClientException $ex) {
            $error = json_decode($ex->getResponse()->getBody()->getContents());
            throw new BasicException(
                "{$error->Subject} -- {$error->Message} -- {$error->Note}"
            );
        }

        Redis::cache()->set($key, $data, 300);
        return $data;
    }
    
    public function updateCharacter(UserCharacter $userCharacter)
    {
        $key = __METHOD__ . $userCharacter->getId() . 200;
        
        // check cache
        if ($data = Redis::cache()->get($key)) {
            return;
        }
        
        try {
            // get retainer items
            $data = (new XIVAPI())->character->update($userCharacter->getLodestoneId());
        } catch (ClientException $ex) {
            $error = json_decode($ex->getResponse()->getBody()->getContents());
            throw new BasicException(
                "{$error->Subject} -- {$error->Message} -- {$error->Note}"
            );
        }
    
        Redis::cache()->set($key, $data, 300);
    }
    
    /**
     * Get a characters patreon status
     */
    public function getCharacterPatronState(int $lodestoneId, ?string $userId = null)
    {
        /** @var UserCharacter $character */
        $character = $this->repository->findOneBy([ 'lodestoneId' => $lodestoneId ]);
        
        if ($character == null) {
            return 1;
        }
        
        if ($character->getUser()->getPatronBenefitUser() == $userId) {
            return 4;
        }
        
        if ($character->getUser()->isPatron()) {
            return 2;
        }
        
        return 3;
    }
    
    /**
     * Get a user via a character id
     */
    public function getUserViaCharacter(int $lodestoneId)
    {
        /** @var UserCharacter $character */
        $character = $this->repository->findOneBy([ 'lodestoneId' => $lodestoneId ]);
    
        if ($character == null) {
            return null;
        }
    
        return $character->getUser();
    }
}
