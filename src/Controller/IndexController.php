<?php

namespace App\Controller;

use App\Service\Items\ItemPopularity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /** @var ItemPopularity */
    private $itemPopularity;
    
    public function __construct(ItemPopularity $itemPopularity)
    {
        $this->itemPopularity = $itemPopularity;
    }
    
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
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
