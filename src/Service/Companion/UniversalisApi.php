<?php

namespace App\Service\Companion;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class UniversalisApi
{
    const PROD    = 'https://universalis.app';
    #const STAGING = 'https://staging.xivapi.com';
    #const DEV     = 'http://xivapi.local';

    const TIMEOUT = 10.0;
    const VERIFY = false;

    /** @var Client */
    private $client = null;

    public function __construct(string $environment = self::PROD)
    {
        $this->client = new Client([
            'base_uri'  => $environment,
            'timeout'   => self::TIMEOUT,
            'verify'    => self::VERIFY,
        ]);
    }

    public function getItem(int $worldId, int $itemId)
    {
        return $this->query("GET", "api/{$worldId}/{$itemId}", [
            RequestOptions::QUERY => [
                'src'   => 'universalis_front'
            ]
        ]);
    }

    public function getExtendedHistory(int $worldId, int $itemId, int $numEntries = 200)
    {
        return $this->query("GET", "api/{$worldId}/{$itemId}", [
            RequestOptions::QUERY => [
                'src'   => 'universalis_front'
            ]
        ]);
    }

    public function getRecentlyUpdated()
    {
        return $this->query("GET", "api/extra/stats/recently-updated", [
            RequestOptions::QUERY => [
                'src'   => 'universalis_front'
            ]
        ]);
    }

    public function getUploadHistory()
    {
        return $this->query("GET", "api/extra/stats/upload-history", [
            RequestOptions::QUERY => [
                'src'   => 'universalis_front'
            ]
        ]);
    }

    private function query($method, $apiEndpoint, $options = [])
    {
        // set XIVAPI key
        /*
        if ($key = getenv(Environment::XIVAPI_KEY)) {
            $options[RequestOptions::QUERY]['private_key'] = $key;
        }
        */

        return \GuzzleHttp\json_decode(
            $this->client->request($method, $apiEndpoint, $options)->getBody()
        );
    }
}
