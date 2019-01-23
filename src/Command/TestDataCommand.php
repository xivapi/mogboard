<?php

namespace App\Command;

use App\Entity\MarketListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestDataCommand extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
        
        $this->em = $em;
        $this->em->getConfiguration()->setSQLLogger(null);
    }
    
    protected function configure()
    {
        $this->setName(str_ireplace('App\\Command\\', null, __CLASS__));
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $stacks   = 250;
        $quantity = 1000;

        $io->title('Adding 1,000,000 dummy data');
        
        foreach(range(1, $stacks) as $i => $stack) {
            $start = microtime(true);
            foreach(range(1,$quantity) as $j => $num) {
                $this->em->persist(
                    (new MarketListing())->randomize()
                );
            }
    
            $this->em->flush();
    
            $memory = round((memory_get_peak_usage(true)/1024/1024),2);
            $duration = round(microtime(true) - $start, 2);
            $eta = ($duration * ($stacks - ($i+1))) + time();
            $eta = date('Y-m-d H:i:s', $eta);
            $io->text("Stack: {$stack} -- ETA: <info>{$eta}</info> -- Memory: <info>{$memory} mb</info> -- Duration: <comment>{$duration}</comment>");

            $this->em->clear();
            gc_collect_cycles();
        }
    }
}
