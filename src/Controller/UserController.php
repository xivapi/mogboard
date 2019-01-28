<?php

namespace App\Controller;

use App\Services\User\Discord\DiscordSignIn;
use App\Services\User\Users;
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
     * @Route("/users/login/discord", name="user_login_discord")
     */
    public function loginDiscord(Request $request)
    {
        return $this->redirect(
            $this->users->setSsoProvider(new DiscordSignIn($request))->login()
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
        
        $this->users->setSsoProvider(new DiscordSignIn($request))->authenticate();
        return $this->redirectToRoute('home');
    }
    
    /**
     * @Route("/users/logout", name="user_logout")
     */
    public function logout()
    {
        $this->users->logout();
        return $this->redirectToRoute('home');
    }
}
