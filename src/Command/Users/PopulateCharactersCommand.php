<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Service\UserCharacters\UserCharacters;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateCharactersCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'PopulateCharactersCommand',
        'desc' => 'Maintain character name, server and avatar by getting data from XIVAPI',
    ];

    /** @var UserCharacters */
    private $characters;

    public function __construct(UserCharacters $characters, $name = null)
    {
        $this->characters = $characters;
        parent::__construct($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->characters->autoUpdateCharacterDetails();
    }
}
