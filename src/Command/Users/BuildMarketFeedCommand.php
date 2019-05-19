<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionMarketActivity;
use App\Service\UserCharacters\UserCharacters;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildMarketFeedCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'BuildMarketFeedCommand',
        'desc' => 'Build users homepage market feed',
    ];

    /** @var CompanionMarketActivity */
    private $cma;

    public function __construct(CompanionMarketActivity $cma, $name = null)
    {
        $this->cma = $cma;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cma->buildUserMarketFeeds();
    }
}
