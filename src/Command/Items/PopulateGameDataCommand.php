<?php

namespace App\Command\Items;

use App\Command\CommandConfigureTrait;
use App\Service\GameData\GameDataCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateGameDataCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'PopulateGameDataCommand',
        'desc' => 'Populate cached game data',
    ];

    /**
     * @var GameDataCache
     */
    private $gdc;

    public function __construct(GameDataCache $gdc, string $name = null)
    {
        $this->gdc = $gdc;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->gdc->populate();
    }
}
