<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\UserAlerts\UserAlertsTriggers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TriggerAlertsCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'TriggerAlertsCommand',
        'desc' => 'Trigger user alerts',
        'args' => [
            [ 'offset', InputArgument::OPTIONAL, 'Query offset' ],
            [ 'patrons', InputArgument::OPTIONAL, 'Filter for patron users?' ]
        ]
    ];

    /** @var UserAlertsTriggers */
    private $alertTriggers;

    public function __construct(UserAlertsTriggers $alertTriggers, $name = null)
    {
        $this->alertTriggers = $alertTriggers;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->alertTriggers->trigger(
            $input->getArgument('offset'),
            $input->getArgument('patrons')
        );
    }
}
