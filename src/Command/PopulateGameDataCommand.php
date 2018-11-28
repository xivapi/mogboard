<?php

namespace App\Command;

use App\Resources\Resources;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PopulateGameDataCommand extends Command
{
    /** @var SymfonyStyle */
    private $io;
    
    protected function configure()
    {
        $this->setName(str_ireplace('App\\Command\\', null, __CLASS__));
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title(__CLASS__);
        
        // todo - move this to the xivapi-php library
        
        $this->downloadItemSearchCategories();
    }
    
    /**
     * Download everything to do with the item search categories
     */
    private function downloadItemSearchCategories()
    {
        $this->io->section(__METHOD__);
        
        $itemSearchCategoryNames = [
            1 => 'weapons',
            2 => 'armor',
            3 => 'misc',
            4 => 'housing'
        ];
        
        // only 1 page of this, so fine to grab and save
        $urlItemSearchCategories = 'https://xivapi.com/ItemSearchCategory?columns=ID,Icon,Name_*,Order,Category,ClassJob.ClassJobCategory.Name_*&limit=500&key='. getenv('XIVAPI_PRIVATE_KEY');
        $response = json_decode(file_get_contents($urlItemSearchCategories));
        
        // sort results
        $itemSearchCategories = [];
        $itemSearchCategoriesByID = [];
        foreach($response->Results as $i => $res) {
            // ignore empty ones
            if (!in_array($res->Category, array_keys($itemSearchCategoryNames))) {
                continue;
            }
            
            // store category
            $catName = $itemSearchCategoryNames[$res->Category];
            $itemSearchCategories[$catName][$res->Order] = $res;
            $itemSearchCategoriesByID[$res->ID] = $res;
            
            // download icons within that cat
            $this->io->text("- Downloading item data for category: {$res->Name_en}");
            $urlItemsWithinSearchCategory = "https://xivapi.com/search?indexes=item&filters=ItemSearchCategory.ID={$res->ID}&limit=500&columns=ID,Icon,Rarity,LevelItem,Name_*&sort_field=LevelItem&sort_order=desc&key=". getenv('XIVAPI_PRIVATE_KEY');
            $items = json_decode(file_get_contents($urlItemsWithinSearchCategory));
            Resources::save("ItemsWithinSearchCategory_{$res->ID}", $items->Results);
            
            if ($items->Pagination->ResultsTotal > 1000) {
                throw new \Exception("The total results was above 1000, this means the code will not work and you need to implement a page handler");
            }
        }
        
        Resources::save('ItemSearchCategories', $itemSearchCategories);
        Resources::save('ItemSearchCategoriesByID', $itemSearchCategoriesByID);
    }
}
