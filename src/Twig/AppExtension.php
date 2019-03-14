<?php

namespace App\Twig;

use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\Common\SiteVersion;
use App\Service\Redis\Redis;
use Carbon\Carbon;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('date', [$this, 'getDate']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('siteVersion', [$this, 'getApiVersion']),
            new \Twig_SimpleFunction('favIcon', [$this, 'getFavIcon']),
            new \Twig_SimpleFunction('cache', [$this, 'getCached']),
        ];
    }
    
    /**
     * Get date in a nice format.
     */
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
     * Get static cache
     */
    public function getCached($key)
    {
        $obj = Redis::Cache()->get($key);
        $obj = Language::handle($obj);
        return $obj;
    }
}
