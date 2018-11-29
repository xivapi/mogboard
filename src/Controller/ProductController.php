<?php

namespace App\Controller;

use Spatie\Async\Pool;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

class ProductController extends Controller
{
    /**
     * @Route("/market/{server}/{id}", name="product_page")
     */
    public function index(string $server, int $id)
    {
        $api = new XIVAPI();
    
        $columns = [
            "ID",
            "Name",
            "Icon",
            "Description",
            "LevelEquip",
            "LevelItem",
            "ItemUICategory.Name",
            "ItemUICategory.Icon",
            "ItemSearchCategory.Name",
            "ItemSearchCategory.Icon",
            "ItemKind.Name",
            "GamePatch"
        ];
    
        $item = $api->columns($columns)->content->Item()->one($id);
        
        //$prices  = $api->market->price($server, $id);
        //$history = $api->market->history($server, $id);

        return $this->render('Product/page.html.twig', [
            'item'      => $item,
            // 'prices'    => $prices,
            // 'history'   => $history
        ]);
    }
}
