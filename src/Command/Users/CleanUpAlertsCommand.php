<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\User\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpAlertsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'CleanUpAlertsCommand',
        'desc' => 'Clean up triggers and delete old ones.',
    ];

    /** @var Users */
    private $users;

    public function __construct(Users $users, $name = null)
    {
        $this->users = $users;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->users->clearAlertTracking();
    }
}
