<?php

namespace App\Services\Common\Redis;

/**
 * Requires: https://github.com/phpredis/phpredis
 */
class Cache
{
    /** @var \Redis */
    private static $instance;
    /** @var \Redis */
    private static $pipeline;
    /** @var array */
    private static $options = [
        'timeout'       => 5,
        'compression'   => 5,
        'default_time'  => 3600,
        'serializer'    => \Redis::SERIALIZER_NONE,
        'read_timeout'  => -1,
    ];
    
    public static function setInstance(string $env = 'REDIS_LOCAL')
    {
        if (self::$instance) {
            return;
        }
        
        [$ip, $port, $auth] = explode(',', getenv($env));
    
        $redis = new \Redis();
        $redis->pconnect($ip, $port, self::$options['timeout']);
        $redis->auth($auth);
        $redis->setOption(\Redis::OPT_SERIALIZER, self::$options['serializer']);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, self::$options['read_timeout']);
        
        self::$instance = $redis;
    }
    
    public static function startPipeline()
    {
        self::setInstance();
        
        if (self::$pipeline) {
            throw new \Exception('Pipeline already initialized.');
        }
        
        self::$pipeline = self::$instance->multi(\Redis::PIPELINE);
    }
    
    public static function executePipeline()
    {
        self::setInstance();
        self::$pipeline->exec();
        self::$pipeline = null;
    }
    
    public static function increment(string $key, int $amount = 1)
    {
        self::setInstance();
        self::$instance->incrBy($key, $amount);
    }
    
    public static function decrement(string $key, int $amount = 1)
    {
        self::setInstance();
        self::$instance->decrBy($key, $amount);
    }
    
    public static function set(string $key, $data, int $ttl = 3600, bool $serialize = false)
    {
        self::setInstance();
        
        $data = (self::$options['serializer'] || $serialize)
            ? serialize($data)
            : gzcompress(json_encode($data), self::$options['compression']);
    
        if (json_last_error()) {
            throw new \Exception("COULD NOT SAVE TO REDIS, JSON ERROR: ". json_last_error_msg());
        }
    
        self::$pipeline ? self::$pipeline->set($key, $data, $ttl) : self::$instance->set($key, $data, $ttl);
    }
    
    public static function setTimeout(string $key, int $ttl)
    {
        self::setInstance();
        self::$instance->setTimeout($key, $ttl);
    }
    
    public static function get(string $key, bool $serialize = false)
    {
        self::setInstance();
        $data = self::$pipeline ? self::$pipeline->get($key) : self::$instance->get($key);
    
        if ($data) {
            $data = (self::$options['serializer'] || $serialize)
                ? unserialize($data)
                : json_decode(gzuncompress($data));
        }
        
        return $data;
    }
    
    public static function getCount(string $key)
    {
        self::setInstance();
        return self::$pipeline ? self::$pipeline->get($key) : self::$instance->get($key);
    }
    
    public static function delete(string $key)
    {
        self::setInstance();
        self::$instance->delete($key);
    }
    
    public static function keys(string $keys = '*')
    {
        self::setInstance();
        return self::$instance->keys($keys);
    }
}
