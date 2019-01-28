<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('Pages/home.html.twig');
    }
    
    /**
     * @Route("/404", name="404")
     */
    public function fourofour()
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
