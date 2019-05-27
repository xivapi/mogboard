<?php

namespace App\Controller;

use App\Common\Constants\PatreonConstants;
use App\Common\Controller\UserTraitController;
use App\Common\Exceptions\BasicException;
use App\Common\Exceptions\JsonException;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Service\UserCharacters\UserCharacters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    use UserTraitController;
    
    /** @var Users */
    private $users;
    /** @var UserCharacters */
    private $userCharacters;
    
    public function __construct(Users $users, UserCharacters $userCharacters)
    {
        $this->users = $users;
        $this->userCharacters = $userCharacters;
    }
    
    /**
     * @Route("/account", name="user_account")
     */
    public function account(Request $request)
    {
        $user = $this->users->getUser(true);

        $benefitUser = null;
        if ($user->isPatron(PatreonConstants::PATREON_BENEFIT)) {
            $benefitUser = $this->users->getRepository()->findOneBy(
                [ 'id' => $user->getPatronBenefitUser() ]
            );
        }
        
        return $this->render('UserAccount/account.html.twig', [
            'benefit_user' => $benefitUser
        ]);
    }
    
    /**
     * @Route("/account/confirm-patreon", name="user_account_confirm_patreon")
     */
    public function accountConfirmPatreon()
    {
        $user = $this->users->getUser(true);
        $this->users->checkPatreonTierForUser($user);
        
        return $this->json([
            'ok' => $user->getPatreonTierNumber() > 0
        ]);
    }
    
    /**
     * @Route("/account/characters", name="user_account_characters")
     */
    public function accountCharacters(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/characters.html.twig');
    }

    /**
     * @Route("/account/patreon/perks", name="user_account_patron_perks")
     */
    public function accountPatreonPerks(Request $request)
    {
        $user = $this->users->getUser(true);

        if ($user->isPatron() == false) {
            throw new BasicException("Sorry, you are not a patreon member and cannot view this page.");
        }

        /**
         * Try get the users main character
         */
        $character = $user->getMainCharacter() && $user->isPatronHealer() ? $this->userCharacters->getCharacter(
            $user->getMainCharacter()
        ) : null;
    
        /**
         * If we have friends, see if any have patreon
         * 1 = character does not exist on XIVAPI
         * 2 = character is already a patron
         * 3 = fine to perk up
         * 4 = this user already assigned it patron, can unassign
         */
        $friendStates = [];
        if ($character && $character->Friends) {
            foreach ($character->Friends as $friend) {
                $friendStates[$friend->ID] = $this->userCharacters->getCharacterPatronState($friend->ID, $user->getId());
            }
        } else if ($user->getMainCharacter()) {
            $this->userCharacters->updateCharacter($user->getMainCharacter());
        }

        $this->users->setLastUrl($request);
        return $this->render('UserAccount/patreon.html.twig', [
            'character' => $character,
            'friend_states' => $friendStates,
        ]);
    }
    
    /**
     * @Route("/account/patreon/perks/benefit", name="user_account_patron_perks_benefits")
     */
    public function accountPatreonPerksBenefit(Request $request)
    {
        $user = $this->users->getUser(true);
    
        if ($user->isPatron() == false) {
            throw new JsonException("Sorry, you are not a patreon member and cannot view this page.");
        }
        
        if ($user->isPatronHealer() == false) {
            throw new JsonException("Sorry, you must be a healer or higher to provide benefits.");
        }
        
        $benefitCount = $this->users->getBenefitCount($user->getId());
        
        if ($benefitCount >= $user->getMaxBenefitFriends()) {
            throw new JsonException("Sorry, you have reached the maximum amount ({$user->getMaxBenefitFriends()}) of benefit friends you can dish out. Maybe De-Benefit some friends, I'll keep it a secret!");
        }
    
        $benefit = $this->userCharacters->getUserViaCharacter($request->get('id'));
        
        if ($benefit->getPatron() == PatreonConstants::PATREON_BENEFIT && $benefit->getPatronBenefitUser() == $user->getId()) {
            $benefit
                ->setPatron(0)
                ->setPatronBenefitUser(null);
    
            $this->users->save($benefit);
            return $this->json(20);
        }
        
        if ($benefit == null) {
            throw new JsonException("Sorry, there is no user on mogboard for this character. Please ask them to join!");
        }
        
        if ($benefit->isPatron()) {
            throw new JsonException("Sorry! This user is already a patron member!");
        }
        
        $benefit
            ->setPatron(PatreonConstants::PATREON_BENEFIT)
            ->setPatronBenefitUser($user->getId());
    
        // get Alert Limits
        $benefits = PatreonConstants::ALERT_LIMITS[$benefit->getPatron()];
    
        // update user
        $user
            ->setAlertsMax($benefits['MAX'])
            ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT'])
            ->setAlertsUpdate($benefits['UPDATE_TIMEOUT']);
            
        $this->users->save($benefit);
        
        return $this->json(10);
    }
    
    /**
     * @Route("/account/retainers", name="user_account_retainers")
     */
    public function accountRetainers(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/retainers.html.twig');
    }
    
    /**
     * @Route("/account/alerts", name="user_account_alerts")
     */
    public function accountAlerts(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/alerts.html.twig');
    }
    
    /**
     * @Route("/account/lists", name="user_account_lists")
     */
    public function accountLists(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/lists.html.twig');
    }
    
    /**
     * @Route("/account/reports", name="user_account_reports")
     */
    public function accountReports(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/reports.html.twig');
    }
}
