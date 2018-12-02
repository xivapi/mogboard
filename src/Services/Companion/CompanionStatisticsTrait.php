<?php

namespace App\Services\Companion;

use Carbon\Carbon;

trait CompanionStatisticsTrait
{
    /**
     * Build stats for the current item prices
     */
    public function getItemPriceStats($prices, $filtered = false)
    {
        if (empty($prices)) {
            return false;
        }
        
        $stats = (Object)[
            'General'      => (Object)[
                'Stock'       => 0,
                'Listings'    => 0,
                'TotalCost'   => 0,
                'Quantities'  => [],
                'QuantityMax' => 0,
            ],
            'PricePerUnit' => (Object)[
                'Min'       => 0,
                'Max'       => 0,
                'Avg'       => 0,
                'MinHQ'     => 0,
                'MinNQ'     => 0,
                'MaxHQ'     => 0,
                'MaxNQ'     => 0,
                'Values'    => [],
                'Chart'     => [],
                'Quantity'  => [],
            ],
            'PriceTotal'   => (Object)[
                'Min'       => 0,
                'Max'       => 0,
                'Avg'       => 0,
                'MinHQ'     => 0,
                'MinNQ'     => 0,
                'MaxHQ'     => 0,
                'MaxNQ'     => 0,
                'Values'    => [],
            ],
        ];
        
        //
        // Work out min and max
        //
        foreach ($prices as $i => $price) {
            $stats->PricePerUnit->Values[$i] = $price->PricePerUnit;
            $stats->PriceTotal->Values[$i] = $price->PriceTotal;
        }
    
        $stats->PricePerUnit->Avg = round(array_sum($stats->PricePerUnit->Values) / count($stats->PricePerUnit->Values));
        $stats->PriceTotal->Avg   = round(array_sum($stats->PriceTotal->Values) / count($stats->PriceTotal->Values));
        
        // if filtered false, silly prices still exist
        if ($filtered === false) {
            foreach ($prices as $i => $price) {
                // ignore prices that are 3x the average
                $threshold = ($stats->PricePerUnit->Avg * 3);
                if ($price->PricePerUnit > $threshold) {
                    unset($prices[$i]);
                }
            }
            
            return $this->getItemPriceStats($prices, true);
        }
        
        foreach ($prices as $i => $price) {
            $stats->PricePerUnit->Min = ($stats->PricePerUnit->Min === 0 || $price->PricePerUnit < $stats->PricePerUnit->Min) ? $price->PricePerUnit : $stats->PricePerUnit->Min;
            $stats->PriceTotal->Min   = ($stats->PriceTotal->Min === 0 || $price->PriceTotal < $stats->PriceTotal->Min) ? $price->PriceTotal : $stats->PriceTotal->Min;
            $stats->PricePerUnit->Max = ($stats->PricePerUnit->Max === 0 || $price->PricePerUnit > $stats->PricePerUnit->Max) ? $price->PricePerUnit : $stats->PricePerUnit->Max;
            $stats->PriceTotal->Max   = ($stats->PriceTotal->Max === 0 || $price->PriceTotal > $stats->PriceTotal->Max) ? $price->PriceTotal : $stats->PriceTotal->Max;
            
            if ($price->IsHQ) {
                $stats->PricePerUnit->MinHQ = ($stats->PricePerUnit->MinHQ === 0 || $price->PricePerUnit < $stats->PricePerUnit->MinHQ) ? $price->PricePerUnit : $stats->PricePerUnit->MinHQ;
                $stats->PriceTotal->MinHQ   = ($stats->PriceTotal->MinHQ === 0 || $price->PriceTotal < $stats->PriceTotal->MinHQ) ? $price->PriceTotal : $stats->PriceTotal->MinHQ;
                $stats->PricePerUnit->MaxHQ = ($stats->PricePerUnit->MaxHQ === 0 || $price->PricePerUnit > $stats->PricePerUnit->MaxHQ) ? $price->PricePerUnit : $stats->PricePerUnit->MaxHQ;
                $stats->PriceTotal->MaxHQ   = ($stats->PriceTotal->MaxHQ === 0 || $price->PriceTotal > $stats->PriceTotal->MaxHQ) ? $price->PriceTotal : $stats->PriceTotal->MaxHQ;
            } else {
                $stats->PricePerUnit->MinNQ = ($stats->PricePerUnit->MinNQ === 0 || $price->PricePerUnit < $stats->PricePerUnit->MinNQ) ? $price->PricePerUnit : $stats->PricePerUnit->MinNQ;
                $stats->PriceTotal->MinNQ   = ($stats->PriceTotal->MinNQ === 0 || $price->PriceTotal < $stats->PriceTotal->MinNQ) ? $price->PriceTotal : $stats->PriceTotal->MinNQ;
                $stats->PricePerUnit->MaxNQ = ($stats->PricePerUnit->MaxNQ === 0 || $price->PricePerUnit > $stats->PricePerUnit->MaxNQ) ? $price->PricePerUnit : $stats->PricePerUnit->MaxNQ;
                $stats->PriceTotal->MaxNQ   = ($stats->PriceTotal->MaxNQ === 0 || $price->PriceTotal > $stats->PriceTotal->MaxNQ) ? $price->PriceTotal : $stats->PriceTotal->MaxNQ;
            }
            
            $stats->General->Quantities[] = $price->Quantity;
            
            $stats->General->Stock += $price->Quantity;
            $stats->General->Listings++;
            $stats->General->TotalCost += $price->PriceTotal;
        }
    
        $stats->General->QuantityMax = max($stats->General->Quantities);
        
        //
        // Bubble Chart!
        //
        foreach ($prices as $i => $price) {
            // size radius based on quantity, minimum of 3px
            $radius = round(8 * ($price->Quantity / $stats->General->QuantityMax));
            $radius = $radius > 2 ? $radius : 2;
            
            $stats->PricePerUnit->Chart[] = [
                'x' => ($i + 1),
                'y' => $price->PricePerUnit,
                'r' => $radius,
            ];
        }
        
        return $stats;
    }
    
    /**
     * Build stats for the current purchase history
     */
    public function getItemHistoryStats($history)
    {
        if (empty($history)) {
            return false;
        }
    
        $stats = (Object)[
            'General' => (Object)[
                'LastSold'      => 0,
                'LastSoldDates' => [],
                'AvgSaleWait'   => null,
            ],
            'PricePerUnit' => (Object)[
                'Min'    => 0,
                'Max'    => 0,
                'Avg'    => 0,
                'Values' => [],
                'Chart'  => (Object)[
                    'Labels' => [],
                    'Values' => [],
                ],
            ],
            'PriceTotal' => (Object)[
                'Min'    => 0,
                'Max'    => 0,
                'Avg'    => 0,
                'Values' => [],
            ],
        ];
    
        //
        // Work out min and max
        //
        foreach ($history as $i => $purchase) {
            $stats->PricePerUnit->Values[] = $purchase->PricePerUnit;
            $stats->PriceTotal->Values[] = $purchase->PriceTotal;
        
            $stats->PricePerUnit->Min = ($stats->PricePerUnit->Min === 0 || $purchase->PricePerUnit < $stats->PricePerUnit->Min) ? $purchase->PricePerUnit : $stats->PricePerUnit->Min;
            $stats->PriceTotal->Min   = ($stats->PriceTotal->Min === 0 || $purchase->PriceTotal < $stats->PriceTotal->Min) ? $purchase->PriceTotal : $stats->PriceTotal->Min;
            $stats->PricePerUnit->Max = ($stats->PricePerUnit->Max === 0 || $purchase->PricePerUnit > $stats->PricePerUnit->Max) ? $purchase->PricePerUnit : $stats->PricePerUnit->Max;
            $stats->PriceTotal->Max   = ($stats->PriceTotal->Max === 0 || $purchase->PriceTotal > $stats->PriceTotal->Max) ? $purchase->PriceTotal : $stats->PricePerUnit->Max;
        
            if ($stats->General->LastSold === 0) {
                $stats->General->LastSold = $purchase->PurchaseDate;
            } else {
                $stats->General->LastSoldDates[] = $stats->General->LastSold - $purchase->PurchaseDate;
                $stats->General->LastSold = $purchase->PurchaseDate;
            }
        }

    
        $stats->PricePerUnit->Avg    = round(array_sum($stats->PricePerUnit->Values) / count($stats->PricePerUnit->Values));
        $stats->PriceTotal->Avg      = round(array_sum($stats->PriceTotal->Values) / count($stats->PriceTotal->Values));
        $stats->General->AvgSaleWait = round(array_sum($stats->General->LastSoldDates) / count($stats->General->LastSoldDates));
    
        // Average Sale Wait
        /** @var Carbon $asw */
        $stats->General->AvgSaleWait = ($stats->General->AvgSaleWait > 86400)
            ? 'over a day' : gmdate("H \h\\r\s i \m\i\\n s \s\\e\c", $stats->General->AvgSaleWait);
        
        //
        // Bubble Chart!
        //
        foreach ($history as $i => $purchase) {
            $stats->PricePerUnit->Chart->Labels[] = date('jS M, H:i:s', $purchase->PurchaseDate);
            $stats->PricePerUnit->Chart->Values[] = $purchase->PricePerUnit;
        }
    
        return $stats;
    }
    
    /**
     * (mega) Build stats for all servers!
     *  - this can be fed to the two other methods
     */
    public function getItemPricesCrossWorldStats($servers, $prices)
    {
        $stats = [];
        $statsOverall = (Object)[
            'CheapestNq' => 0,
            'CheapestNqServer' => 0,
            'CheapestHq' => 0,
            'CheapestHqServer' => 0,
            'LastSold' => []
        ];
        
        foreach ($servers as $server) {
            $serverPrices   = $prices->{$server . '_current'}->Prices;
            $serverHistory  = $prices->{$server . '_history'}->History;
            $lastSold       = $serverHistory[0];
            
            // build stats
            $priceStats   = $this->getItemPriceStats($serverPrices);
            $historyStats = $this->getItemHistoryStats($serverHistory);
            
            // save to server
            $stats[$server] = [
                'Prices'  => $priceStats,
                'History' => $historyStats
            ];
            
            // work out cheapests
            if ($statsOverall->CheapestNq === 0 || $priceStats->PricePerUnit->MinNQ < $statsOverall->CheapestNq) {
                $statsOverall->CheapestNq = $priceStats->PricePerUnit->MinNQ;
                $statsOverall->CheapestNqServer = $server;
            }
    
            if ($statsOverall->CheapestHq === 0 || $priceStats->PricePerUnit->MinHQ < $statsOverall->CheapestHq) {
                $statsOverall->CheapestHq = $priceStats->PricePerUnit->MinHQ;
                $statsOverall->CheapestHqServer = $server;
            }
            
            // record last sold
            $statsOverall->LastSold[$server] = $lastSold;
        }
        
        return [ $stats, $statsOverall ];
    }
}
