<?php

namespace App\Service\UserLists;

use App\Entity\User;
use App\Entity\UserList;
use App\Repository\UserListRepository;
use App\Service\Companion\Companion;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserLists
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;
    /** @var Companion */
    private $companion;
    /** @var UserListRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em, Users $users, Companion $companion)
    {
        $this->em           = $em;
        $this->users        = $users;
        $this->companion    = $companion;
        $this->repository   = $em->getRepository(UserList::class);
        $this->console      = new ConsoleOutput();
    }

    /**
     * Handle adding/removing an item from the favourites list
     */
    public function handleFavourite(int $itemId): UserList
    {
        $user = $this->users->getUser(true);
        $list = $this->getFavourites($user);

        // either add or remove the item
        $list->hasItem($itemId) ? $list->removeItem($itemId) : $list->addItem($itemId);
        $this->save($list);
        return $list;
    }

    /**
     * Get a users favourites list
     */
    public function getFavourites(User $user)
    {
        $filters = [
            'favourite' => true,
            'user'      => $user
        ];

        if ($list = $this->repository->findOneBy($filters)) {
            return $list;
        }

        $list = new UserList();
        $list->setName('Favourites')->setFavourite(true)->setUser($user)->setSlug();
        return $list;
    }

    /**
     * Save a new or existing alert
     */
    public function save(UserList $list)
    {
        $this->em->persist($list);
        $this->em->flush();
        return true;
    }
}
