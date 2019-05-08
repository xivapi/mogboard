<?php

namespace App\Controller;

use App\Service\User\SignInDiscord;
use App\Service\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /** @var Users */
    private $users;
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
    
    /**
     * @Route("/account", name="user_account")
     */
    public function account(Request $request)
    {
        $this->users->setLastUrl($request);
        return $this->render('UserAccount/account.html.twig');
    }
    
    /**
     * @Route("/account/confirm-patreon", name="user_account_confirm_patreon")
     */
    public function accountConfirmPatreon(Request $request)
    {
        $user = $this->users->getUser();
    
        $this->users->checkPatreonTierForUser($user);
        
        return $this->redirectToRoute('user_account', [
            'patreon_checked' => $user->getPatreonTier(),
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
    
    /**
     * @Route("/users/login/discord", name="user_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        return $this->redirect(
            $this->users->setSsoProvider(new SignInDiscord($request))->login()
        );
    }
    
    /**
     * @Route("/users/login/discord/success", name="user_login_discord_success")
     */
    public function loginDiscordResponse(Request $request)
    {
        if ($request->get('error') == 'access_denied') {
            return $this->redirectToRoute('home');
        }
        
        try {
            $this->users->setSsoProvider(new SignInDiscord($request))->authenticate();

            // redirect to their previous url if one exists
            $lastUrl = $this->users->getLastUrl($request);
            return $lastUrl ? $this->redirect($lastUrl) : $this->redirectToRoute('home');
        } catch (\Exception $ex) {
            return $this->redirectToRoute('user_login_failed');
        }
    }
    
    /**
     * @Route("/users/logout", name="user_logout")
     */
    public function logout()
    {
        $this->users->logout();
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/users/login/failed", name="user_login_failed)
     */
    public function loginFailed()
    {
        return $this->render('UserAccount/login_failed.html.twig');
    }
}
