<?php

namespace App\Service\Companion;

use App\Common\Exceptions\BasicException;
use App\Common\Service\Redis\Redis;
use GuzzleHttp\Exception\ClientException;
use XIVAPI\XIVAPI;

class Companion
{
    /**
     * Handle API Actions on the market endpoint
     */
    private function handle(string $method, $arguments, $queries = [])
    {
        $key = __METHOD__ . $method . sha1(json_encode($arguments));
        
        if ($data = Redis::cache()->get($key)) {
            return $data;
        }
        
        // init xivapi
        $api = new XIVAPI();
        $api->queries($queries);
        
        try {
            $data = call_user_func_array([$api->market, $method], $arguments);
        } catch (ClientException $ex) {
            $error = json_decode($ex->getResponse()->getBody()->getContents());
            throw new BasicException(
                "{$error->Subject} -- {$error->Message} -- {$error->Note}"
            );
        }
    
        Redis::cache()->set($key, $data, 60);
        
        return $data;
    }
    
    public function getByServer(string $server, int $itemId)
    {
        return $this->handle('item', [
            $itemId, [$server]
        ]);
    }
    
    public function getByServers(array $servers, int $itemId)
    {
        return $this->handle('item', [
            $itemId, $servers
        ]);
    }
    
    public function getByDataCenter(string $dataCenter, int $itemId)
    {
        return $this->handle('item', [
            $itemId, [], $dataCenter
        ]);
    }
    
    public function getItemsOnServer(array $items, string $server)
    {
        return $this->handle('items', [
            $items, [ $server ], null
        ], [
            'max_history' => 10,
            'max_prices'  => 10
        ]);
    }
    
    public function getItemsOnDataCenter(array $items, string $dc)
    {
        return $this->handle('items', [
            $items, [], $dc
        ], [
            'max_history' => 10,
            'max_prices'  => 10
        ]);
    }
}
