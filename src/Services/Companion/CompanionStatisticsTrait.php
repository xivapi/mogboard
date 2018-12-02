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
            'General' => (Object)[
                'Stock'     => 0,
                'Listings'  => 0,
                'TotalCost' => 0,
            ],
            'PricePerUnit' => (Object)[
                'Min'       => 0,
                'Max'       => 0,
                'Avg'       => 0,
                'Values'    => [],
                'Chart'     => [],
            ],
            'PriceTotal' => (Object)[
                'Min'       => 0,
                'Max'       => 0,
                'Avg'       => 0,
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
                // ignore prices that are 2x the average
                $threshold = ($stats->PricePerUnit->Avg * 2);
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
    
            $stats->General->Stock += $price->Quantity;
            $stats->General->Listings++;
            $stats->General->TotalCost += $price->PriceTotal;
        }
        
        //
        // Bubble Chart!
        //
        foreach ($prices as $i => $price) {
            $stats->PricePerUnit->Chart[] = [
                'x' => ($i + 1),
                'y' => $price->PricePerUnit,
                'r' => 4,
            ];
        }
        
        return $stats;
    }
    
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
            ? 'over a day' : gmdate("H \h\\r i \m\i\\n s \s\\e\c", $stats->General->AvgSaleWait);
        
        //
        // Bubble Chart!
        //
        foreach ($history as $i => $purchase) {
            $stats->PricePerUnit->Chart->Labels[] = date('jS M, H:i:s', $purchase->PurchaseDate);
            $stats->PricePerUnit->Chart->Values[] = $purchase->PricePerUnit;
        }
    
        return $stats;
    }
}
