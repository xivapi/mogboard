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
            new TwigFilter('icon2x', [$this, 'getIcon2x']),

            new TwigFilter('base64encode', [$this, 'getBase64encode']),
            new TwigFilter('base64decode', [$this, 'getBase64decode']),
        ];
    }
    
    public function getBase64encode($string)
    {
        return base64_encode($string);
    }
    
    public function getBase64decode($string)
    {
        return base64_decode($string);
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
     * Handle xivapi icons
     */
    public function getIcon($icon)
    {
        return "https://xivapi.com{$icon}";
    }
    
    /**
     * Handle xivapi icons
     */
    public function getIcon2x($icon)
    {
        return "https://xivapi.com/i2/ls/{$icon}.png";
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
    
    
}
