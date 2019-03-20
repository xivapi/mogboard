<?php

namespace App\Controller;

use App\Entity\UserList;
use App\Service\UserLists\UserCharacters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserListsController extends AbstractController
{
    /** @var UserCharacters */
    private $lists;
    
    public function __construct(UserCharacters $lists)
    {
        $this->lists = $lists;
    }

    /**
     * @Route("/lists/favourite", name="lists_favourite")
     */
    public function favourite(Request $request)
    {
        $json = json_decode($request->getContent());
        $list = $this->lists->handleFavourite($json->itemId);

        return $this->json([
            'state' => $list->hasItem($json->itemId)
        ]);
    }
    
    /**
     * @Route("/lists/create", name="lists_create")
     */
    public function create(Request $request)
    {
        // todo - logic to create a new list
    }

    /**
     * @Route("/lists/{list}/delete", name="list_delete")
     */
    public function delete(UserList $list)
    {
        // todo - logic to delete a list
    }
    
    /**
     * @Route("/lists/{list}/update", name="list_update")
     */
    public function update(Request $request, UserList $list)
    {
        // todo - logic to update a list
    }
    
    /**
     * @Route("/lists/{list}/add-item", name="lists_add_item")
     */
    public function addItem(Request $request, UserList $list)
    {
        // todo - add item
    }
    
    /**
     * @Route("/lists/{list}/remove-item", name="lists_remove_item")
     */
    public function removeItem(Request $request, UserList $list)
    {
        // todo - remove item
    }
}
