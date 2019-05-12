<?php

namespace App\Command\Items;

use App\Command\CommandConfigureTrait;
use App\Service\Items\Popularity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetTrendingItemsCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'ResetTrendingItemsCommand',
        'desc' => 'Resets the top trending items so new ones can be assigned',
    ];
    
    /** @var Popularity */
    private $itemPopularity;
    
    public function __construct(Popularity $itemPopularity, ?string $name = null)
    {
        $this->itemPopularity = $itemPopularity;
        
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->itemPopularity->reset();
    }
}
