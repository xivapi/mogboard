<?php

namespace App\Twig;

use App\Entity\Alert;
use Twig\Extension\AbstractExtension;

class AppProductExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getAlertTriggers', [$this, 'getAlertTriggers']),
        ];
    }
    
    public function getAlertTriggers()
    {
        return Alert::getTriggers();
    }
}
