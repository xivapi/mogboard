<?php

namespace App\Resources;

/**
 * Very basic class to load resources within this folder, good
 * for a bunch of cached json from XIVAPI
 */
class Resources
{
    public static function load($file)
    {
        return file_get_contents(__DIR__ .'/'. $file);
    }
    
    public static function save($file, $data)
    {
        file_put_contents(__DIR__ .'/'. $file, $data);
    }
    
    public static function json($file)
    {
        return json_decode(self::load($file));
    }
}
