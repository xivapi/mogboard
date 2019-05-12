<?php

namespace App\Controller;

use App\Common\Game\GameServers;
use App\Service\Companion\Companion;
use App\Common\Service\Redis\Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

class SpecialShopController extends AbstractController
{
    /** @var Companion */
    private $companion;
    
    public function __construct(
        Companion $companion
    ) {
        $this->companion = $companion;
    }
    
    /**
     * @Route("/special-shop", name="special_shop_list")
     */
    public function index(Request $request)
    {
        # $this->users->setLastUrl($request);

        $xivapi = new XIVAPI();

        $queries = [
            'columns' => [
                'ID', 'Name'
            ],
            'limit' => 20,
        ];
        
        $shops = $xivapi->queries($queries)->content->SpecialShop()->list();
        
        print_r($shops);
        die;
        
        return $this->render('SpecialShop/index.html.twig', [
            'special_shops' => $shops
        ]);
    }
    
    /**
     * @Route("/special-shop/{id}", name="special_shop_view")
     */
    public function shop(int $id, Request $request)
    {
        $key = __METHOD__ . $id;
        
        if ($data = Redis::Cache()->get($key)) {
            $data = json_decode(json_encode($data), true);
            return $this->render('SpecialShop/shop.html.twig', $data);
        }
        
        $specialshop = Redis::Cache()->get("xiv_SpecialShop_{$id}");
        
        $server = GameServers::getServer();
        $items  = [];
        
        foreach ($specialshop as $field => $value) {
            $fieldName = preg_replace('/[0-9]+/', null, $field);
            
            if ($fieldName === 'ItemReceive' && $value) {
                $assoItemCost     = str_replace('ItemReceive', 'ItemCost', $field);
                $assoCountReceive = str_replace('ItemReceive', 'CountReceive', $field);
                $assoCountCost    = str_replace('ItemReceive', 'CountCost', $field);
                $assoHqReceive    = str_replace('ItemReceive', 'HQReceive', $field);
                $assoHqCost       = str_replace('ItemReceive', 'HQCost', $field);
    
                $prices = $value->ItemSearchCategory ? $this->companion->getByServer($server, $value->ID) : null;
    
                $items[] = [
                    'Item' => $value,
                    'Market' => $prices,
                    'Info' => [
                        'ItemCost' => $specialshop->{$assoItemCost} ?? null,
                        'ItemCostCount' => $specialshop->{$assoCountCost} ?? null,
                        'ItemCostReceive' => $specialshop->{$assoCountReceive} ?? null,
                        'HqReceive' => $specialshop->{$assoHqReceive} ?? null,
                        'HqCost' => $specialshop->{$assoHqCost} ?? null,
                    ]
                ];
            }
        }
        
        $data = [
            'special_shop' => [
                'ID' => $specialshop->ID,
                'Name' => $specialshop->Name_en
            ],
            'special_shop_items' => $items,
            'server' => $server,
        ];
        
        Redis::Cache()->set($key, $data, (60 * 10));
        
        return $this->render('SpecialShop/shop.html.twig', $data);
    }
}
