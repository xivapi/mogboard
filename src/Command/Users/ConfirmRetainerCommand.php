<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfirmRetainerCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'ConfirmRetainerCommand',
        'desc' => 'Confirms retainer ownership',
    ];

    /** @var UserRetainers */
    private $userRetainers;

    public function __construct(UserRetainers $userRetainers, $name = null)
    {
        $this->userRetainers = $userRetainers;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->userRetainers->confirmOwnership();
    }
}
