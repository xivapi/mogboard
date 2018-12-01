<?php

namespace App\Services\Cache;

use App\Services\Common\Language;

class Cache
{
    /** @var Redis */
    private $redis;
    
    /**
     * Access the redis
     */
    public function get($key)
    {
        if ($this->redis === null) {
            $this->redis = new Redis();
            $this->redis->connect();
        }
        
        $data = $this->redis->get($key);
        $data = Language::handle($data);
        
        return $data;
    }
}
