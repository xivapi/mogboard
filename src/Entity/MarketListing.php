<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="market_listing",
 *     indexes={
 *          @ORM\Index(name="item_id", columns={"item_id"}),
 *          @ORM\Index(name="price_per_unit", columns={"price_per_unit"}),
 *          @ORM\Index(name="price_total", columns={"price_total"}),
 *          @ORM\Index(name="quantity", columns={"quantity"}),
 *          @ORM\Index(name="is_crafted", columns={"is_crafted"}),
 *          @ORM\Index(name="is_hq", columns={"is_hq"}),
 *          @ORM\Index(name="craft_signature", columns={"craft_signature"}),
 *          @ORM\Index(name="retainer_name", columns={"retainer_name"}),
 *          @ORM\Index(name="stain_id", columns={"stain_id"}),
 *          @ORM\Index(name="town_id", columns={"town_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MarketListingRepository")
 */
class MarketListing
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $pricePerUnit;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $priceTotal;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $quantity;
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isCrafted;
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isHQ;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $craftSignature;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $retainerName;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $itemID;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stainID;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $townID;
    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $materia = [];
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }
    
    public function randomize(): MarketListing
    {
        $this->pricePerUnit     = mt_rand(1,99999);
        $this->quantity         = mt_rand(1,999);
        $this->priceTotal       = $this->pricePerUnit * $this->quantity;
        $this->isCrafted        = mt_rand(1,100) % 2 == 0;
        $this->isHQ             = mt_rand(1,100) % 5 == 0;
        $this->craftSignature   = mt_rand(1,99999);
        $this->retainerName     = mt_rand(1,99999);
        $this->itemID           = mt_rand(2000,23000);
        $this->stainID          = mt_rand(4500,8000);
        $this->townID           = mt_rand(1,4);
        
        if (mt_rand(1,100) % 10 === 0) {
            foreach(range(1, mt_rand(1,5)) as $rand) {
                $this->materia[] = mt_rand(4500,8000);
            }
        }
        
        return $this;
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
    
    public function getPricePerUnit(): int
    {
        return $this->pricePerUnit;
    }
    
    public function setPricePerUnit(int $pricePerUnit)
    {
        $this->pricePerUnit = $pricePerUnit;
        
        return $this;
    }
    
    public function getPriceTotal(): int
    {
        return $this->priceTotal;
    }
    
    public function setPriceTotal(int $priceTotal)
    {
        $this->priceTotal = $priceTotal;
        
        return $this;
    }
    
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
        
        return $this;
    }
    
    public function isCrafted(): bool
    {
        return $this->isCrafted;
    }
    
    public function setIsCrafted(bool $isCrafted)
    {
        $this->isCrafted = $isCrafted;
        
        return $this;
    }
    
    public function isHQ(): bool
    {
        return $this->isHQ;
    }
    
    public function setIsHQ(bool $isHQ)
    {
        $this->isHQ = $isHQ;
        
        return $this;
    }
    
    public function getCraftSignature(): int
    {
        return $this->craftSignature;
    }
    
    public function setCraftSignature(int $craftSignature)
    {
        $this->craftSignature = $craftSignature;
        
        return $this;
    }
    
    public function getRetainerName(): int
    {
        return $this->retainerName;
    }
    
    public function setRetainerName(int $retainerName)
    {
        $this->retainerName = $retainerName;
        
        return $this;
    }
    
    public function getItemID(): int
    {
        return $this->itemID;
    }
    
    public function setItemID(int $itemID)
    {
        $this->itemID = $itemID;
        
        return $this;
    }
    
    public function getStainID(): int
    {
        return $this->stainID;
    }
    
    public function setStainID(int $stainID)
    {
        $this->stainID = $stainID;
        
        return $this;
    }
    
    public function getTownID(): int
    {
        return $this->townID;
    }
    
    public function setTownID(int $townID)
    {
        $this->townID = $townID;
        
        return $this;
    }
    
    public function getMateria(): array
    {
        return $this->materia;
    }
    
    public function setMateria(array $materia)
    {
        $this->materia = $materia;
        
        return $this;
    }
}
