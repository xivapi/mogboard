<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Base64Extension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('base64encode', [$this, 'getBase64encode']),
            new TwigFilter('base64decode', [$this, 'getBase64decode']),
        ];
    }
    
    public function getBase64encode($string)
    {
        return base64_encode($string);
    }
    
    public function getBase64decode($string)
    {
        return base64_decode($string);
    }
}
