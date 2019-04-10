<?php

namespace App\Service\Companion;
use App\Service\Common\Arrays;
use App\Service\GameData\GameServers;
use MathPHP\Statistics\Average;

/**
 * Generate all stats about items, including cross-world summaries, chart data,
 * hq/nq sale amounts, the lot!
 */
class CompanionCensus
{
    /** @var \stdClass */
    private $census = [];
    /** @var string */
    private $homeServer = null;
    
    public function __construct()
    {
        $this->census = (Object)$this->census;
    }
    
    public function generate($market): self
    {
        file_put_contents(__DIR__.'/market.json', json_encode($market, JSON_PRETTY_PRINT));
        
        // server users home world
        $this->homeServer = GameServers::getServer();
        
        // add for global statistics
        $this->census->_Global = (Object)[];
    
        /**
         * First we remove all "silly" prices, these are the ones
         * that are 3x the price of the cheapest 5
         */
        $this->removeJunkPrices($market);
    
        /**
         * Per server statistics
         */
        foreach ($market as $server => $marketData) {
            $this->buildAverage('PricePerUnit', $server, $marketData);
            $this->buildAverage('PriceTotal', $server, $marketData);
            $this->buildAverage('Quantity', $server, $marketData);
            $this->buildChart('PricePerUnit', $server, $marketData);
            $this->buildPricePerQuantityChart($server, $marketData);
            $this->calculateAverageSaleDuration($server, $marketData);
            $this->calculateNumericStatistics($server, $marketData);
        }
    
        /**
         * Global Statistics
         */
        $this->buildCrossWorldPricesAndHistory($market);
        
        file_put_contents(__DIR__.'/market_census.json', json_encode($this->census, JSON_PRETTY_PRINT));
        
        return $this;
    }
    
    /**
     * @return object|\stdClass
     */
    public function getCensus()
    {
        return $this->census;
    }
    
    /**
     * Calculate the average for a specific field, separates HQ and NQ
     */
    private function buildAverage($field, $server, $marketData)
    {
        $pricesHQ = [];
        $pricesNQ = [];
        $historyHQ = [];
        $historyNQ = [];
        
        foreach ($marketData->Prices as $row) {
            if ($row->IsHQ) {
                $pricesHQ[] = $row->{$field};
            } else {
                $pricesNQ[] = $row->{$field};
            }
        }
    
        foreach ($marketData->History as $row) {
            if ($row->IsHQ) {
                $historyHQ[] = $row->{$field};
            } else {
                $historyNQ[] = $row->{$field};
            }
        }
    
        $this->census->{$server}->{"Prices_Average_{$field}_HQ"}  = round(Average::mean($pricesHQ));
        $this->census->{$server}->{"Prices_Average_{$field}_NQ"}  = round(Average::mean($pricesNQ));
        $this->census->{$server}->{"History_Average_{$field}_HQ"} = round(Average::mean($historyHQ));
        $this->census->{$server}->{"History_Average_{$field}_NQ"} = round(Average::mean($historyNQ));
    }
    
    /**
     * Builds a chart for a specific field, separates HQ and NQ
     */
    private function buildChart($field, $server, $marketData)
    {
        $this->census->{$server}->{"Prices_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"Prices_Chart_{$field}_NQ"} = [];
        $this->census->{$server}->{"History_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"History_Chart_{$field}_NQ"} = [];
        
        foreach ($marketData->Prices as $row) {
            if ($row->IsHQ) {
                $this->census->{$server}->{"Prices_Chart_{$field}_HQ"}[] = $row->PricePerUnit;
            } else {
                $this->census->{$server}->{"Prices_Chart_{$field}_NQ"}[] = $row->PricePerUnit;
            }
        }
    
        foreach ($marketData->History as $row) {
            if ($row->IsHQ) {
                $this->census->{$server}->{"History_Chart_{$field}_HQ"}[] = $row->PricePerUnit;
            } else {
                $this->census->{$server}->{"History_Chart_{$field}_NQ"}[] = $row->PricePerUnit;
            }
        }
    }
    
    /**
     * Builds 1 big giant table of all market sales and orders
     * them by their time. Prices differences based on average is also included
     */
    private function buildCrossWorldPricesAndHistory($market)
    {
        $crossWorldPrices = [];
        $crossWorldHistory = [];
        
        foreach ($market as $server => $marketData) {
            foreach ($marketData->Prices as $row) {
                $row->_Server = $server;
                $crossWorldPrices[] = (Array)$row;
            }
            
            foreach ($marketData->History as $row) {
                $row->_Server = $server;
                $crossWorldHistory[] = (Array)$row;
            }
        }

        Arrays::sortBySubKey($crossWorldPrices, 'PricePerUnit', true);
        Arrays::sortBySubKey($crossWorldHistory, 'PurchaseDate');
    
        $this->census->_Global->CrossWorldPrices = $crossWorldPrices;
        $this->census->_Global->CrossWorldHistory = $crossWorldHistory;
    }
    
    /**
     * Calculate the average sale duration
     */
    private function calculateAverageSaleDuration($server, $marketData)
    {
        $saleTimestampHQ = [];
        $saleTimestampHQLast = 0;
        $saleTimestampNQ = [];
        $saleTimestampNQLast = 0;
        
        foreach ($marketData->History as $row) {
            if ($saleTimestampHQLast === 0 && $row->IsHQ) {
                $saleTimestampHQLast = $row->PurchaseDate;
                continue;
            }
    
            if ($saleTimestampNQLast === 0 && $row->IsHQ == false) {
                $saleTimestampNQLast = $row->PurchaseDate;
                continue;
            }
            
            // only process timestamps longer than 60 seconds, otherwise it's lots of purchases
            // in a single instance which doesn't help the average sale time.
            if ($row->IsHQ && ($saleTimestampHQLast - $row->PurchaseDate) > 60) {
                $saleTimestampHQ[]   = $saleTimestampHQLast - $row->PurchaseDate;
                $saleTimestampHQLast = $row->PurchaseDate;
            } else if (($saleTimestampNQLast - $row->PurchaseDate) > 60) {
                $saleTimestampNQ[]   = $saleTimestampNQLast - $row->PurchaseDate;
                $saleTimestampNQLast = $row->PurchaseDate;
            }
        }
    
        $this->census->{$server}->{"History_Average_SaleDuration_HQ"}  = round(Average::mean($saleTimestampHQ));
        $this->census->{$server}->{"History_Average_SaleDuration_NQ"}  = round(Average::mean($saleTimestampNQ));
    }
    
    /**
     * Calculate numeric statistics such as max stock, max gil, etc...
     */
    private function calculateNumericStatistics($server, $marketData)
    {
        $totalGilHQ = 0;
        $totalGilNQ = 0;
        $totalStockHQ = 0;
        $totalStockNQ = 0;
        $cheapestPriceHQ = null;
        $cheapestPriceNQ = null;
        
        foreach ($marketData->Prices as $row) {
            if ($cheapestPriceHQ === null && $row->IsHQ) {
                $cheapestPriceHQ = [
                    'PricePerUnit' => $row->PricePerUnit,
                    'PriceTotal'   => $row->PriceTotal,
                    'Quantity'     => $row->Quantity
                ];
            }
    
            if ($cheapestPriceNQ === null && $row->IsHQ == false) {
                $cheapestPriceNQ = [
                    'PricePerUnit' => $row->PricePerUnit,
                    'PriceTotal'   => $row->PriceTotal,
                    'Quantity'     => $row->Quantity
                ];
            }
            
            if ($row->IsHQ) {
                $totalGilHQ += $row->PriceTotal;
                $totalStockHQ += $row->Quantity;
            } else {
                $totalGilNQ += $row->PriceTotal;
                $totalStockNQ += $row->Quantity;
            }
        }
    
        $this->census->{$server}->{"Prices_Numeric_TotalGilHQ"} = $totalGilHQ;
        $this->census->{$server}->{"Prices_Numeric_TotalGilNQ"} = $totalGilNQ;
        $this->census->{$server}->{"Prices_Numeric_TotalStockHQ"} = $totalStockHQ;
        $this->census->{$server}->{"Prices_Numeric_TotalStockNQ"} = $totalStockNQ;
    
        $this->census->{$server}->{"Prices_CheapestHQ"} = $cheapestPriceHQ;
        $this->census->{$server}->{"Prices_CheapestNQ"} = $cheapestPriceNQ;
    }
    
    /**
     * Simple chart with quantity and price, this is useful for bubble charts, this
     * uses the average quantity to know the "scale" of the bubble radius
     */
    private function buildPricePerQuantityChart($server, $marketData)
    {
        // the max scale in pixels
        $bubbleScaleMaxPX = 30;
        $bubbleScaleMinPX = 5;
        $bubbleScaleFactorHQ = $this->census->{$server}->Prices_Average_Quantity_HQ;
        $bubbleScaleFactorNQ = $this->census->{$server}->Prices_Average_Quantity_NQ;
    
        $chartDataHQ = [];
        $chartDataNQ = [];
        
        foreach ($marketData->Prices as $row) {
            // calculate quantity radius
            $radiusFactor = $row->Quantity / ($row->IsHQ ? $bubbleScaleFactorHQ : $bubbleScaleFactorNQ);
            $radius = $bubbleScaleMaxPX * $radiusFactor;
            $radius = $radius < $bubbleScaleMinPX ? $radius : $bubbleScaleMinPX;
            $radius = $radius > $bubbleScaleMaxPX ? $radius : $bubbleScaleMinPX;

            if ($row->IsHQ) {
                $chartDataHQ[] = [
                    'x' => count($chartDataHQ),
                    'y' => $row->PricePerUnit,
                    'r' => $radius
                ];
            } else {
                $chartDataNQ[] = [
                    'x' => count($chartDataNQ),
                    'y' => $row->PricePerUnit,
                    'r' => $radius
                ];
            }
        }
    
        $this->census->{$server}->{"Prices_BubbleChart_Quantity_HQ"} = $chartDataHQ;
        $this->census->{$server}->{"Prices_BubbleChart_Quantity_NQ"} = $chartDataNQ;
    }
    
    /**
     * Remove junk prices from the market data
     */
    private function removeJunkPrices($market)
    {
        foreach ($market as $server => $marketData) {
            // add this, will need for later
            $this->census->{$server} = (Object)[];
    
            /**
             * Calculate average off the first 10 listings.
             */
            $pricesHQ = [];
            $pricesNQ = [];
    
            foreach ($marketData->Prices as $i => $price) {
                if ($price->IsHQ) {
                    $pricesHQ[$i] = $price->PricePerUnit;
                }
    
                if ($price->IsHQ == false) {
                    $pricesNQ[$i] = $price->PricePerUnit;
                }
            }
    
            // remove junk prices based on previous prices
            $this->removeJunkPricesBasedOnPreviousPrice($marketData, $pricesNQ);
            $this->removeJunkPricesBasedOnPreviousPrice($marketData, $pricesNQ);
        }
    }
    
    /**
     * This will remove junk prices based on previous prices in the market, since
     * market prices are ordered LOW > HIGH, we can judge based on big jumps
     * when prices are junk or should not be factored into census data.
     */
    private function removeJunkPricesBasedOnPreviousPrice($marketData, $averagePrices)
    {
        $previous = null;
        $remove   = false;
        foreach ($averagePrices as $i => $price) {
            // if previous price not set, continue
            if ($previous === null) {
                $previous = $price;
                continue;
            }
        
            // if the price is above X times the previous, it's likely a junk price
            // todo - this will need tweaking and examining if its the right amount
            if ($remove === false && $price > ($previous * 10)) {
                $remove = true;
            }
        
            // start removing
            if ($remove) {
                unset($marketData->Prices[$i]);
                continue;
            }
        
            $previous = $price;
        }
    }
}
