<?php

namespace App\Services\Cache;

use App\Services\Common\Language;

class Cache
{
    /** @var Redis[] */
    private static $instances = [];
    
    /**
     * Get a static cache for an environment
     */
    public static function instance(string $environment = Redis::LOCAL): Redis
    {
        if (!isset(self::$instances[$environment])) {
            self::$instances[$environment] = (new Redis())->connect($environment);
        }
        
        return self::$instances[$environment];
    }
    
    /**
     * Get something from cache and convert it for multi-language
     */
    public static function get(string $key, string $environment = Redis::LOCAL)
    {
        $data = self::instance($environment)->get($key);
        $data = Language::handle($data);
        return $data;
    }
}
