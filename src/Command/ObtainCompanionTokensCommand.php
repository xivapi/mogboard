<?php

namespace App\Command;

use App\Services\Companion\Companion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ObtainCompanionTokensCommand extends Command
{
    protected function configure()
    {
        $this->setName(str_ireplace('App\\Command\\', null, __CLASS__));
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $companion = new Companion();
        $companion->refreshTokens();
        $output->writeln('Complete');
    }
}
