<?php

namespace App\Services\GameData;

use Symfony\Component\Console\Style\SymfonyStyle;
use XIVAPI\XIVAPI;

abstract class GameDataAbstract
{
    /** @var XIVAPI */
    protected $xivapi;
    /** @var SymfonyStyle */
    protected $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->xivapi = new XIVAPI();
    }

    public abstract function populate();
}
