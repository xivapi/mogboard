<?php

namespace App\Twig;

use App\Services\GameData\GameDataSource;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppGameExtension extends AbstractExtension
{
    /** @var GameDataSource */
    private $gameDataSource;
    
    public function __construct(GameDataSource $gameDataSource)
    {
        $this->gameDataSource = $gameDataSource;
    }
    
    public function getFilters()
    {
        return [];
    }
    
    public function getFunctions()
    {
        return [
            new TwigFunction('gamedata', [$this, 'getGameDataSource']),
        ];
    }
    
    public function getGameDataSource(): GameDataSource
    {
        return $this->gameDataSource;
    }
}
