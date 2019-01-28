<?php

namespace App\Twig;

use App\Services\Common\Environment;
use App\Services\Common\SiteVersion;
use Carbon\Carbon;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('date', [$this, 'getDate']),
            new TwigFilter('icon', [$this, 'getIcon']),
            new TwigFilter('xivicon', [$this, 'getSearchIcons']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('env', [$this, 'getEnvVar']),
            new \Twig_SimpleFunction('environment', [$this, 'getEnvironment']),
            new \Twig_SimpleFunction('siteVersion', [$this, 'getApiVersion']),
            new \Twig_SimpleFunction('favIcon', [$this, 'getFavIcon']),
        ];
    }
    
    public function getDate($unix)
    {
        $unix = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = time() - $unix;
        
        // if over 24hrs, show date
        if ($difference > (60 * 60)) {
            return date('jS M, H:i:s', $unix);
        }
        
        return Carbon::now()->subSeconds($difference)->diffForHumans();
    }

    /**
     * Handle xivapi icons
     */
    public function getIcon($icon)
    {
        return 'https://xivapi.com'. $icon;
    }
    
    /**
     * Get an environment variable
     */
    public function getEnvVar($var)
    {
        return getenv($var);
    }

    /**
     * Get the current site environment
     */
    public function getEnvironment()
    {
        return constant(Environment::CONSTANT);
    }

    /**
     * Get API version information
     */
    public function getApiVersion()
    {
        return SiteVersion::get();
    }

    /**
     * Get Fav icon based on if the site is in dev or prod mode
     */
    public function getFavIcon()
    {
        return getenv('APP_ENV') == 'dev' ? '/favicon_dev.png' : '/favicon.png';
    }
    
    /**
     * get the search icons you see on the Market Board
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
            16 => 'ARC',
            84 => 'RDM',
            15 => 'CNJ',
            85 => 'SCH',
            78 => 'AST',
            19 => 'CRP',
            20 => 'BLM',
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
