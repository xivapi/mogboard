<?php

namespace App\Twig;

use App\Common\Entity\Maintenance;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MaintenanceExtension extends AbstractExtension
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function getFilters()
    {
        return [];
    }
    
    public function getFunctions()
    {
        return [
            new TwigFunction('maintenance', [$this, 'getMaintenance']),
        ];
    }
    
    public function getMaintenance()
    {
        return $this->em->getRepository(Maintenance::class)->findOneBy(['id' => 1 ]) ?: new Maintenance();
    }
}
