<?php

namespace App\Twig;

use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
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
            new TwigFunction('getGameServers', [$this, 'getGameDataCenters']),
            new TwigFunction('getGameDataCenters', [$this, 'getGameDataCenters']),
        ];
    }
    
    public function getGameDataSource(): GameDataSource
    {
        return $this->gameDataSource;
    }
    
    public function getGameServers()
    {
        return GameServers::LIST;
    }
    
    public function getGameDataCenters()
    {
        return GameServers::LIST_DC;
    }
}
