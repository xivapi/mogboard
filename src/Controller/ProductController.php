<?php

namespace App\Controller;

use App\Services\Cache\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /** @var Cache */
    private $cache;
    
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * @Route("/market/{server}/{id}", name="product_page")
     */
    public function index(string $server, int $id)
    {
        $item = $this->cache->get("xiv_Item_{$id}") ?: false;
        
        if (!$item) {
            throw new NotFoundHttpException();
        }

        return $this->render('Product/item.html.twig', [
            'item'   => $item,
            'server' => $server
        ]);
    }
}
