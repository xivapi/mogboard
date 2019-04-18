<?php

namespace App\Controller;

use App\Entity\UserList;
use App\Service\UserLists\UserLists;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/lists/{list}/delete", name="list_delete")
     */
    public function delete(UserList $list)
    {
        $this->lists->delete($list);
        return $this->json(true);
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
        return $this->json(
            $this->lists->removeItem(
                $list,
                $request->get('itemId')
            )
        );
    }
}
