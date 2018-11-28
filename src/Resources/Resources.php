<?php

namespace App\Resources;

use App\Services\Common\Language;

/**
 * Quickly load and save json files
 * todo - this could be stored in redis
 */
class Resources
{
    public static function load($file)
    {
        $resource = json_decode(file_get_contents(__DIR__ .'/'. $file));
        $resource = Language::handle($resource);
        return $resource;
    }
    
    public static function save($file, $data)
    {
        file_put_contents(__DIR__ .'/'. $file, json_encode($data));
    }
}
