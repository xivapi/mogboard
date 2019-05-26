<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Common\User\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitorBenefitFriendsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'MonitorBenefitFriendsCommand',
        'desc' => 'Checks the friends list of those who have granted friend benefits',
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
        $this->users->checkBenefitHandouts();
    }
}
