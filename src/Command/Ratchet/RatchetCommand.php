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
        
        $app = new HttpServer(
            new WsServer(
                new RatchetService()
            )
        );
    
        $loop = \React\EventLoop\Factory::create();
    
        $secure_websockets = new \React\Socket\Server('0.0.0.0:8081', $loop);
        $secure_websockets = new \React\Socket\SecureServer($secure_websockets, $loop, [
            'local_cert'  => '/etc/letsencrypt/live/mogboard.com/fullchain.pem;',
            'local_pk'    => '/etc/letsencrypt/live/mogboard.com/privkey.pem',
            'verify_peer' => false
        ]);
        
        $secure_websockets_server = new \Ratchet\Server\IoServer($app, $secure_websockets, $loop);
        $secure_websockets_server->run();
    }
}
