<?php

namespace App\Controller;

use App\Entity\ItemList;
use App\Repository\ItemListRepository;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ItemListsController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var ItemListRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em, Users $users)
    {
        $this->em = $em;
        $this->users = $users;
        $this->repository = $this->em->getRepository(ItemList::class);
    }
    
    /**
     * @Route("/lists/create", name="lists_create")
     */
    public function create(Request $request)
    {
        // todo - logic to create a new list
    }
    
    /**
     * @Route("/lists/favourite", name="lists_favourite")
     */
    public function favourite(Request $request)
    {
        $payload = json_decode($request->getContent());
        
        $user = $this->users->getUser(true);
        
        $list = $this->repository->findOneBy([ 'favourite' => true, 'user' => $user ]) ?: new ItemList();
        
        // ensure list is set properly
        $list
            ->setName('Favourites')
            ->setFavourite(true)
            ->setUser($user);
        
        // either add or remove the item
        $list->hasItem($payload->itemId) ? $list->removeItem($payload->itemId) : $list->addItem($payload->itemId);
        
        $this->em->persist($list);
        $this->em->flush();
        
        return $this->json([
            'state' => $list->hasItem($payload->itemId)
        ]);
    }
    
    /**
     * @Route("/lists/{listId}/delete", name="list_delete")
     */
    public function delete($listId)
    {
        // todo - logic to delete a list
    }
    
    /**
     * @Route("/lists/{listId}/update", name="list_update")
     */
    public function update(Request $request, $listId)
    {
        // todo - logic to update a list
    }
    
    /**
     * @Route("/lists/{listId}/add-item", name="lists_add_item")
     */
    public function addItem(Request $request, $listId)
    {
        // todo - add item
    }
    
    /**
     * @Route("/lists/{listId}/remove-item", name="lists_remove_item")
     */
    public function removeItem(Request $request, $listId)
    {
        // todo - remove item
    }
}
