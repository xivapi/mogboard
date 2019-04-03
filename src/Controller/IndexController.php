<?php

namespace App\Controller;

use App\Service\Items\ItemPopularity;
use App\Service\User\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /** @var ItemPopularity */
    private $itemPopularity;
    /** @var Users */
    private $users;
    
    public function __construct(
        ItemPopularity $itemPopularity,
        Users $users
    ) {
        $this->itemPopularity = $itemPopularity;
        $this->users = $users;
    }
    
    /**
     * @Route("/", name="home")
     */
    public function home(Request $request)
    {
        $this->users->setLastUrl($request);
        
        return $this->render('Pages/home.html.twig',[
            'popular_items' => $this->itemPopularity->get()
        ]);
    }
    
    /**
     * @Route("/404", name="404")
     */
    public function fourOfour()
    {
        return $this->render('Pages/404.html.twig');
    }
    
    /**
     * @Route("/theme", name="theme")
     */
    public function theme()
    {
        return $this->render('Theme/theme.html.twig');
    }
}
