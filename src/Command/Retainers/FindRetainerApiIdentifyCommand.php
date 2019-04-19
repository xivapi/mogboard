<?php

namespace App\Command\Retainers;

use App\Command\CommandConfigureTrait;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindRetainerApiIdentifyCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'FindRetainerApiIdentifyCommand',
        'desc' => 'Attempts to update retainers on MogBoard with their companion API ID',
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
        $this->userRetainers->linkCompanionApiIdentities();
        $output->writeln('Done');
    }
}
