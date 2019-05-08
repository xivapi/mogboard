<?php

namespace App\Command\Ratchet;

use App\Command\CommandConfigureTrait;
use App\Service\Ratchet\RatchetService;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RatchetCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'RatchetCommand',
        'desc' => 'Run Ratchet',
    ];
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Running ratchet client...");
    
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new RatchetService()
                )
            ),
            8080
        );
    
        $server->run();
    }
}
