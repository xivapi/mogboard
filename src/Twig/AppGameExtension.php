<?php

namespace App\Twig;

use App\Service\GameData\GameDataSource;
use App\Service\GameData\GameServers;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
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
        return [
            new TwigFilter('server', [$this, 'getServerName']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new TwigFunction('gamedata', [$this, 'getGameDataSource']),
            new TwigFunction('getServer', [$this, 'getServer']),
            new TwigFunction('getGameServers', [$this, 'getGameServers']),
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
    
    public function getServer()
    {
        return GameServers::getServer();
    }
    
    public function getServerName(int $id)
    {
        return GameServers::LIST[$id];
    }
}
