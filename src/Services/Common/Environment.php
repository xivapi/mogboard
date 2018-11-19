<?php

namespace App\Services\Common;

use Symfony\Component\HttpFoundation\Request;

class Environment
{
    const CONSTANT = 'ENVIRONMENT';

    // set environment
    public static function set(Request $request)
    {
        $environment = 'prod';

        $host = $request->getHost();
        $host = explode('.', $host);

        if ($host[0] === 'staging') {
            $environment = 'staging';
        }

        if ($host[1] === 'local') {
            $environment = 'local';
        }

        if (!defined(self::CONSTANT)) {
            define(self::CONSTANT, $environment);
        }
    }
}
