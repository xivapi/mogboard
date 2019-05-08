<?php

namespace App\Controller;

use App\Service\Redis\RedisTracking;
use App\Service\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
     * @Route("/admin")
     */
    public function admin()
    {
        $user = $this->users->getUser();
        $user->mustBeAdmin();
    
        return $this->render('Admin/statistics.html.twig');
    }
    
    /**
     * @Route("/admin/tracking_stats")
     */
    public function adminTrackingStats()
    {
        $report = RedisTracking::get();
        return new Response(
            json_encode($report, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * @Route("/admin/tracking_stats_reset")
     */
    public function adminTrackingStatsReset()
    {
        RedisTracking::reset();
        return $this->json(true);
    }
}
