<?php

namespace App\Controller;

use App\Common\Controller\UserTraitController;
use App\Common\Exceptions\BasicException;
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
        return $this->render('UserAccount/account.html.twig');
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
     * @Route("/account/patreon", name="user_account_patreon")
     */
    public function accountPatreon(Request $request)
    {
        $user = $this->users->getUser(true);

        if ($user->isPatron() == false) {
            throw new BasicException("Sorry, you are not a patreon member and cannot view this page.");
        }

        /**
         * Try get the users main character
         */
        $character = $user->getMainCharacter() ? $this->userCharacters->getCharacter(
            $user->getMainCharacter()
        ) : null;

        $this->users->setLastUrl($request);
        return $this->render('UserAccount/patreon.html.twig', [
            'character' => $character,
        ]);
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
