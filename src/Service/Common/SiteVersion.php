<?php

namespace App\Service\Common;

use Carbon\Carbon;

/**
 * Provide information for the site
 */
class SiteVersion
{
    const VERSION = 1;
    
    public static function get()
    {
        [$version, $hash, $time] = explode("\n", file_get_contents(__DIR__.'/../../../git_version.txt'));

 
        $time = Carbon::createFromTimestamp($time)->fromNow();

        return (Object)[
            'version'   => self::VERSION .'.'. $version,
            'hash'      => $hash,
            'time'      => $time,
        ];
    }
}
