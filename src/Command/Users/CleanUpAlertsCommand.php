<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\UserAlerts\UserAlerts;
use App\Service\UserAlerts\UserAlertsTriggers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpAlertsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'CleanUpAlertsCommand',
        'desc' => 'Clean up triggers and delete old ones.',
    ];

    /** @var UserAlerts */
    private $userAlerts;

    public function __construct(UserAlerts $userAlerts, $name = null)
    {
        $this->userAlerts = $userAlerts;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->userAlerts->clear();
    }
}
