<?php

namespace App\Command\Retainers;

use App\Command\CommandConfigureTrait;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveLurkingRetainersCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'RemoveLurkingRetainersCommand',
        'desc' => 'Removes retainers which have not been confirmed after 2 hours.',
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
        $this->userRetainers->removeLurkingRetainers();
    }
}
