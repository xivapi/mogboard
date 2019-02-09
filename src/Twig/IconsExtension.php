<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class IconsExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('icon', [$this, 'getIcon']),
            new TwigFilter('icon2x', [$this, 'getIcon2x']),
            new TwigFilter('icon3x', [$this, 'getIcon3x']),
        ];
    }
    
    /**
     * Handle xivapi icons
     */
    public function getIcon($icon)
    {
        return "https://xivapi.com{$icon}";
    }
    
    /**
     * Handle xivapi icons
     */
    public function getIcon2x($icon)
    {
        return "https://xivapi.com/i2/ls/{$icon}.png";
    }
    
    /**
     * Handle xivapi icons
     */
    public function getIcon3x($icon)
    {
        return "https://xivapi.com/i2/ls2/{$icon}.png";
    }
}
