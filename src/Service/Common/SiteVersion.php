<?php

namespace App\Service\Common;

use Carbon\Carbon;

/**
 * Provide information for the site
 */
class SiteVersion
{
    public static function get()
    {
        [$version, $hash, $time] = explode("\n", file_get_contents(__DIR__.'/../../../git_version.txt'));

        $version = $version + 600; // due to the move to GitHub
        $version = substr_replace($version, '.', 2, 0);
        $version = sprintf('%s.%s', getenv('VERSION'), $version);

        $time = Carbon::createFromTimestamp($time)->fromNow();

        return (Object)[
            'version'   => $version,
            'hash'      => $hash,
            'time'      => $time,
        ];
    }
}
