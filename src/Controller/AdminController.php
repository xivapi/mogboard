<?php

namespace App\Controller;

use App\Service\Redis\RedisTracking;
use App\Service\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /** @var Users */
    private $users;
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
    
    /**
     * @Route("/admin", name="admin")
     */
    public function admin()
    {
        $user = $this->users->getUser();
        $user->mustBeAdmin();
    
        RedisTracking::increment(RedisTracking::TEST);
        
        return $this->render('Admin/statistics.html.twig');
    }
}
