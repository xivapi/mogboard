<?php

namespace App\Twig;

use App\Resources\Resources;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GameExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('categories', [$this, 'getCategories']),
        ];
    }
    
    public function getCategories()
    {
        return Resources::json('categories.json');
    }
}
