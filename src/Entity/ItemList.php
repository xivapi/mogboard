<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="list")
 * @ORM\Entity(repositoryClass="App\Repository\ItemListRepository")
 */
class ItemList
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="lists")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $listId;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $favourite = false;
    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $items = [];
    
    public function __construct()
    {
        $this->id     = Uuid::uuid4();
        $this->listId = Uuid::uuid4();
        $this->added  = time();
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
    
    public function setUser(User $user)
    {
        $this->user = $user;
        
        return $this;
    }
    
    public function getListId(): string
    {
        return $this->listId;
    }
    
    public function setListId(string $listId)
    {
        $this->listId = $listId;
        
        return $this;
    }
    
    public function getAdded(): int
    {
        return $this->added;
    }
    
    public function setAdded(int $added)
    {
        $this->added = $added;
        
        return $this;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    public function isFavourite(): bool
    {
        return $this->favourite;
    }
    
    public function setFavourite(bool $favourite)
    {
        $this->favourite = $favourite;
        
        return $this;
    }
    
    public function getItems(): array
    {
        return $this->items;
    }
    
    public function setItems(array $items)
    {
        $this->items = $items;
        
        return $this;
    }
    
    public function addItem(int $itemId)
    {
        // ignore non existing ones
        if (in_array($itemId, $this->items)) {
            return $this;
        }
        
        $this->items[] = $itemId;
        
        return $this;
    }
    
    public function removeItem(int $itemId)
    {
        $index = array_search($itemId, $this->items);
        unset($this->items[$index]);
        
        return $this;
    }
    
    public function hasItem(int $itemId)
    {
        return array_search($itemId, $this->items) !== false;
    }
}
