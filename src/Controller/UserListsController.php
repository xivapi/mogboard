<?php

namespace App\Controller;

use App\Common\Entity\UserList;
use App\Common\Service\Redis\Redis;
use App\Service\UserLists\UserLists;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserListsController extends AbstractController
{
    /** @var UserLists */
    private $lists;
    
    public function __construct(UserLists $lists)
    {
        $this->lists = $lists;
    }

    /**
     * @Route("/lists/favourite", name="lists_favourite")
     */
    public function favourite(Request $request)
    {
        $itemId = (int)$request->get('itemId');
        $list   = $this->lists->handleFavourite($itemId);

        return $this->json([
            'state' => $list->hasItem($itemId)
        ]);
    }
    
    /**
     * @Route("/lists/render", name="lists_render")
     */
    public function renderListTable(Request $request)
    {
        return $this->render('Product/lists_table.html.twig', [
            'itemId' => $request->get('itemId')
        ]);
    }
    
    /**
     * @Route("/lists/create", name="lists_create")
     */
    public function create(Request $request)
    {
        return $this->json(
            $this->lists->create(
                $request->get('name'),
                $request->get('itemId')
            )
        );
    }
    
    /**
     * @Route("/list/{list}", name="lists_view")
     */
    public function view(UserList $list)
    {
        $marketStats = $this->lists->getMarketData($list);

        return $this->render('UserLists/index.html.twig', [
            'list'         => $list,
            'max_items'    => UserList::MAX_ITEMS,
            'market_stats' => $marketStats,
        ]);
    }

    /**
     * @Route("/lists/{list}/delete", name="list_delete")
     */
    public function delete(UserList $list)
    {
        if ($list->isCustom()) {
            throw new \Exception('Cannot delete custom made lists.');
        }
        
        $this->lists->delete($list);
        return $this->redirectToRoute('user_account_lists');
    }
    
    /**
     * @Route("/lists/{list}/rename", name="list_rename")
     */
    public function update(Request $request, UserList $list)
    {
        return $this->json(
            $this->lists->rename(
                $list,
                $request->get('name')
            )
        );
    }
    
    /**
     * @Route("/lists/{list}/add-item", name="lists_add_item")
     */
    public function addItem(Request $request, UserList $list)
    {
        return $this->json(
            $this->lists->addItem(
                $list,
                $request->get('itemId')
            )
        );
    }
    
    /**
     * @Route("/lists/{list}/remove-item", name="lists_remove_item")
     */
    public function removeItem(Request $request, UserList $list)
    {
        $response = $this->lists->removeItem(
            $list,
            $request->get('itemId')
        );
        
        if ($request->get('redirect')) {
            return $this->redirectToRoute('lists_view', [
                'list' => $list->getId()
            ]);
        }
        
        return $this->json($response);
    }
}
