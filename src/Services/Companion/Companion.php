<?php

namespace App\Services\Companion;

use Companion\CompanionApi;
use XIVAPI\XIVAPI;

/**
 * Much of this is based on: https://github.com/xivapi/xivapi.com/blob/master/src/Service/Companion/Companion.php
 */
class Companion
{
    use CompanionEnrichTrait;

    const FILENAME = __DIR__ .'/accounts.json';

    /** @var CompanionApi */
    private $api;

    /**
     * Connect to the API, this should be done before any calls.
     */
    public function api($server): Companion
    {
        $this->api = new CompanionApi("xivapi_{$server}", self::FILENAME);
        return $this;
    }

    /**
     * Get prices for an item
     */
    public function getItemPrices($itemId): array
    {
        $prices = [];
        foreach ($this->api->Market()->getItemMarketListings($itemId)->entries as $row) {
            $prices[] = [
                'ID'             => $itemId,
                'Materia'        => $this->getEnrichedMateria($row->materia),
                'Town'           => $this->getEnrichedTown($row->registerTown),
                'Quantity'       => $row->stack,
                'CraftSignature' => $row->signatureName,
                'IsHQ'           => (bool)($row->hq ? true : false),
                'IsCrafted'      => (bool)($row->isCrafted ? true : false),
                'Stain'          => $row->stain,
                'PricePerUnit'   => $row->sellPrice,
                'PriceTotal'     => $row->sellPrice * $row->stack,
                'RetainerName'   => $row->sellRetainerName,
            ];
        }

        return $prices;
    }

    /**
     * Get item history
     */
    public function getItemHistory($itemId): array
    {
        // build history
        $history = [];
        foreach ($this->api->Market()->getTransactionHistory($itemId)->history as $row) {
            $history[] = [
                'Quantity'      => $row->stack,
                'PricePerUnit'  => $row->sellPrice,
                'PriceTotal'    => $row->sellPrice * $row->stack,
                'CharacterName' => $row->buyCharacterName,
                'PurchaseDate'  => $row->buyRealDate/1000,
                'IsHQ'          => $row->hq,
            ];
        }

        return $history;
    }

    /**
     * Refresh the current companion app tokens from XIVAPI
     */
    public function refreshTokens(): void
    {
        $tokens = (new XIVAPI())->market->tokens(getenv('COMPANION_TOKEN_PASS'));
        file_put_contents(self::FILENAME, \GuzzleHttp\json_encode($tokens));
    }
}
