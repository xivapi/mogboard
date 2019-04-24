<?php

namespace App\Twig;

use App\Entity\UserAlert;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppProductExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('xivicon', [$this, 'getSearchIcons']),
        ];
    }
    
    /**
     * Get the search icons you see on the Market Board
     */
    public function getSearchIcons($id)
    {
        return [
            10 => 'GLA',
            11 => 'MRD',
            76 => 'DRK',
            13 => 'LNC',
            9  => 'PGL',
            83 => 'SAM',
            73 => 'ROG',
            12 => 'ARC',
            77 => 'MCH',
            14 => 'THM',
            16 => 'ACN',
            84 => 'RDM',
            15 => 'CNJ',
            85 => 'SCH',
            78 => 'AST',
            19 => 'CRP',
            20 => 'BSM',
            21 => 'ARM',
            22 => 'GSM',
            23 => 'LTW',
            24 => 'WVR',
            25 => 'ALC',
            26 => 'CUL',
            27 => 'MIN',
            28 => 'BTN',
            29 => 'FSH',
            30 => 'ItemCategory_Fishing_Tackle',
            
            17 => 'ItemCategory_Shield',
            31 => 'Armoury_Head',
            33 => 'Armoury_Body',
            36 => 'Armoury_Hands',
            38 => 'Armoury_Waist',
            35 => 'Armoury_Legs',
            37 => 'Armoury_Feet',
            40 => 'Armoury_Earrings',
            39 => 'Armoury_Necklace',
            41 => 'Armoury_Bracelets',
            42 => 'Armoury_Ring',
            
            43 => 'ItemCategory_Medicine',
            44 => 'CUL',
            45 => 'ItemCategory_Meal',
            46 => 'FSH',
            47 => 'MIN',
            48 => 'ARM',
            49 => 'CRP',
            50 => 'WVR',
            51 => 'LTW',
            52 => 'ItemCategory_Bone',
            53 => 'ALC',
            54 => 'ItemCategory_Dye',
            55 => 'ItemCategory_Part',
            57 => 'ItemCategory_Materia',
            58 => 'ItemCategory_Crystal',
            59 => 'ItemCategory_Catalyst',
            60 => 'ItemCategory_Miscellany',
            74 => 'ItemCategory_Seasonal_Miscellany',
            75 => 'ItemCategory_Minion',
            79 => 'ItemCategory_Airship',
            80 => 'ItemCategory_Orchestrion_Roll',
            
            65 => 'ItemCategory_Exterior_Fixtures',
            66 => 'ItemCategory_Interior_Fixtures',
            67 => 'ItemCategory_Outdoor_Furnishings',
            56 => 'ItemCategory_Furnishings',
            68 => 'ItemCategory_Chairs_and_Beds',
            69 => 'ItemCategory_Tables',
            70 => 'ItemCategory_Tabletop',
            71 => 'ItemCategory_Wallmounted',
            72 => 'ItemCategory_Rug',
            81 => 'ItemCategory_Gardening',
            82 => 'ItemCategory_Painting'
        ][$id];
    }
}
