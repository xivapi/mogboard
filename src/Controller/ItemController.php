<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Services\Cache\Cache;
use App\Services\Companion\Companion;
use App\Services\Companion\CompanionCensus;
use App\Services\GameData\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Cache */
    private $cache;
    /** @var Companion */
    private $companion;
    /** @var Companion */
    private $companionCensus;
    
    public function __construct(EntityManagerInterface $em, Cache $cache, Companion $companion, CompanionCensus $companionCensus)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->companion = $companion;
        $this->companionCensus = $companionCensus;
    }
    
    /**
     * @Route("/market/{itemId}", name="product_page")
     */
    public function index(int $itemId)
    {
        $item = $this->cache->get("xiv_Item_{$itemId}") ?: false;
        
        if (!$item) {
            return $this->redirectToRoute('404');
        }
        
        if (file_exists(__DIR__.'/temp.json')) {
            $data = json_decode(
                file_get_contents(__DIR__.'/temp.json'),
                true
            );
        } else {
            $server     = GameServers::getServer();
            $dc         = GameServers::getDataCenter($server);
            $dcServers  = GameServers::getDataCenterServers($server);
    
            $market = $this->companion->getMultiServer($dcServers, $itemId);
            $census = $this->companionCensus->generate($market);
    
            $data = [
                'item'     => $item,
                'market'   => $market,
                'census'   => $census,
                'server'   => [
                    'name'       => $server,
                    'dc'         => $dc,
                    'dc_servers' => $dcServers
                ]
            ];

            // temp cache to avoid slow load times to prod servers
            file_put_contents(__DIR__.'/temp.json', json_encode($data));
        }
    
        $data['alerts'] = $this->em->getRepository(Alert::class)->findBy([ 'itemId' => $itemId ]);
        
        return $this->render('Product/index.html.twig', $data);
    }
}
