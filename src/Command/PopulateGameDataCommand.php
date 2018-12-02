<?php

namespace App\Command;

use App\Services\GameData\GameData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PopulateGameDataCommand extends Command
{
    protected function configure()
    {
        $this->setName(str_ireplace('App\\Command\\', null, __CLASS__));
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gamedata = new GameData(new SymfonyStyle($input, $output));
        $gamedata->populate();
    }
}