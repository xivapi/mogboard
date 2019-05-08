<?php

namespace App\Service\Ratchet;

use App\Service\Redis\RedisTracking;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class RatchetService implements MessageComponentInterface
{
    protected $clients;
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }
    
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $console = new ConsoleOutput();
        
        try {
            switch (trim($msg)) {
                case 'STATS':
                    $from->send(
                        'STATS|' . json_encode(RedisTracking::get())
                    );
                    break;
        
                case 'STATS_TEST':
                    RedisTracking::increment(RedisTracking::TEST);
                    break;
        
                case 'STATS_RESET':
                    RedisTracking::reset();
                    break;
            }
        } catch (\Exception $ex) {
            $console->writeln("Error: {$ex->getMessage()}");
        }
    }
    
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }
}
