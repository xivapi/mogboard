<?php

namespace App\Command\Ratchet;

use App\Command\CommandConfigureTrait;
use App\Service\Ratchet\RatchetService;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;
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
    
        $loop   = Factory::create();
        $webSock = new SecureServer(
            new Server('0.0.0.0:8090', $loop),
            $loop,
            array(
                'local_cert'        => '/etc/letsencrypt/archive/mogboard.com/cert1.pem', // path to your cert
                'local_pk'          => '/etc/letsencrypt/archive/mogboard.com/privkey1.pem', // path to your server private key
                'allow_self_signed' => TRUE, // Allow self signed certs (should be false in production)
                'verify_peer' => FALSE
            )
        );
        
        // Ratchet magic
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new RatchetService()
                )
            ),
            $webSock
        );
        
        $loop->run();
    }
}
