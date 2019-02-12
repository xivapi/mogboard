<?php

namespace App\Command;

use App\Service\GameData\GameDataCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateGameDataCommand extends Command
{
    protected function configure()
    {
        $this->setName('PopulateGameDataCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        (new GameDataCache())->populate();
    }
}
