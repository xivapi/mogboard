<?php

namespace App\Controller;

use App\Services\User\Discord\DiscordSignIn;
use App\Services\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserAccountController extends AbstractController
{
    /** @var Users */
    private $users;
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
}
