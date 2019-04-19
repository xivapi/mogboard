<?php

namespace App\Twig;

use App\Service\Common\Language;
use App\Service\Common\SiteVersion;
use App\Service\Common\Time;
use App\Service\Redis\Redis;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('date', [$this, 'getDate']),
            new TwigFilter('dateSimple', [$this, 'getDateSimple']),
            new TwigFilter('bool', [$this, 'getBoolVisual']),
            new TwigFilter('max', [$this, 'getMaxValue']),
            new TwigFilter('min', [$this, 'getMinValue']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('siteVersion', [$this, 'getApiVersion']),
            new \Twig_SimpleFunction('favIcon', [$this, 'getFavIcon']),
            new \Twig_SimpleFunction('cache', [$this, 'getCached']),
            new \Twig_SimpleFunction('timezone', [$this, 'getTimezone']),
            new \Twig_SimpleFunction('timezones', [$this, 'getTimezones']),
        ];
    }
    
    /**
     * Get date in a nice format.
     */
    public function getDate($unix)
    {
        $unix       = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = abs(time() - $unix);
        $carbon     = $unix > time()
            ? Carbon::now()->addSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()))
            : Carbon::now()->subSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()));
        
        if ($difference > (60 * 60 * 10)) {
            return $carbon->format('jS M, H:i:s');
        }
        
        return $carbon->diffForHumans();
    }
    
    /**
     * Get date in a nice format.
     */
    public function getDateSimple($unix)
    {
        $unix       = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = time() - $unix;
        $carbon     = Carbon::now()->subSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()));
        
        if ($difference > (60 * 180)) {
            return $carbon->format('j M, H:i');
        }
        
        return $carbon->diffForHumans();
    }
    
    /**
     * Get Users Timezone
     */
    public function getTimezone()
    {
        return Time::timezone();
    }
    
    /**
     * Get supported timezones
     */
    public function getTimezones()
    {
        return Time::timezones();
    }
    
    /**
     * Renders a tick or cross for bool visuals
     */
    public function getBoolVisual($bool)
    {
        return $bool ? '✔' : '✘';
    }
    
    /**
     * Return max value in an array
     */
    public function getMaxValue($array)
    {
        return $array ? max($array) : 0;
    }
    
    /**
     * Return min value in an array
     */
    public function getMinValue($array)
    {
        return $array ? min($array) : 0;
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
