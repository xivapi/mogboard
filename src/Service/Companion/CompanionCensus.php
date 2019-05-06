<?php

namespace App\Service\Companion;
use App\Constants\CompanionConstants;
use App\Service\Common\Arrays;
use App\Service\GameData\GameServers;
use MathPHP\Statistics\Average;

/**
 * Generate all stats about items, including cross-world summaries, chart data,
 * hq/nq sale amounts, the lot!
 */
class CompanionCensus
{
    const BUBBLE_MIN_PX = 3;
    const BUBBLE_MAX_PX = [ 6, 12, 20 ];
    const JUNK_PRICE_FACTOR = 4;
    
    /** @var \stdClass */
    private $item;
    /** @var \stdClass */
    private $census = [];
    /** @var string */
    private $homeServer = null;
    
    public function __construct()
    {
        $this->census = (Object)$this->census;
    }
    
    /**
     * Generate Mark Census!
     */
    public function generate($item, $market): self
    {
        # file_put_contents(__DIR__.'/market.json', json_encode($market, JSON_PRETTY_PRINT));
        
        $this->item = $item;
        
        // server users home world
        $this->homeServer = GameServers::getServer();
        
        // add for global statistics
        $this->census->_Global = (Object)[];
    
        /**
         * This removes items from servers not updating
         */
        $this->removeNonUpdatedItems($market);
    
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
            $this->buildChartBubble($server, $marketData);
            $this->calculateAverageSaleDuration($server, $marketData);
            $this->calculateNumericStatistics($server, $marketData);
        }
    
        /**
         * Global Statistics
         */
        $this->buildCrossWorldPricesAndHistory($market);
        
        $this->buildAverage('PricePerUnit', '_Global', $this->census->_Global);
        $this->buildAverage('PriceTotal', '_Global', $this->census->_Global);
        $this->buildAverage('Quantity', '_Global', $this->census->_Global);
        $this->buildChart('PricePerUnit', '_Global', $this->census->_Global);
        $this->buildChartBubble('_Global', $this->census->_Global);
        $this->calculateAverageSaleDuration('_Global', $this->census->_Global);
        $this->calculateNumericStatistics('_Global', $this->census->_Global);
        
        # file_put_contents(__DIR__.'/market_census.json', json_encode($this->census, JSON_PRETTY_PRINT));
        
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
    
        $this->census->{$server}->{"PricesQuantity_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"PricesQuantity_Chart_{$field}_NQ"} = [];
        $this->census->{$server}->{"HistoryQuantity_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"HistoryQuantity_Chart_{$field}_NQ"} = [];
    
        $this->census->{$server}->{"PricesServer_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"PricesServer_Chart_{$field}_NQ"} = [];
        $this->census->{$server}->{"HistoryServer_Chart_{$field}_HQ"} = [];
        $this->census->{$server}->{"HistoryServer_Chart_{$field}_NQ"} = [];
        
        foreach ($marketData->Prices as $row) {
            if ($row->IsHQ) {
                $this->census->{$server}->{"Prices_Chart_{$field}_HQ"}[] = $row->PricePerUnit;
                $this->census->{$server}->{"PricesQuantity_Chart_{$field}_HQ"}[] = $row->Quantity;
                $this->census->{$server}->{"PricesServer_Chart_{$field}_HQ"}[] = $row->_Server ?? $server;
            } else {
                $this->census->{$server}->{"Prices_Chart_{$field}_NQ"}[] = $row->PricePerUnit;
                $this->census->{$server}->{"PricesQuantity_Chart_{$field}_NQ"}[] = $row->Quantity;
                $this->census->{$server}->{"PricesServer_Chart_{$field}_NQ"}[] = $row->_Server ?? $server;
            }
        }
    
        foreach ($marketData->History as $row) {
            if ($row->IsHQ) {
                $this->census->{$server}->{"History_Chart_{$field}_HQ"}[] = $row->PricePerUnit;
                $this->census->{$server}->{"HistoryQuantity_Chart_{$field}_HQ"}[] = $row->Quantity;
                $this->census->{$server}->{"HistoryServer_Chart_{$field}_HQ"}[] = $row->_Server ?? $server;
            } else {
                $this->census->{$server}->{"History_Chart_{$field}_NQ"}[] = $row->PricePerUnit;
                $this->census->{$server}->{"HistoryQuantity_Chart_{$field}_NQ"}[] = $row->Quantity;
                $this->census->{$server}->{"HistoryServer_Chart_{$field}_NQ"}[] = $row->_Server ?? $server;
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
        $crossWorldPricesHQ = [];
        $crossWorldPricesNQ = [];
        $crossWorldHistoryHQ = [];
        $crossWorldHistoryNQ = [];
        
        foreach ($market as $server => $marketData) {
            foreach ($marketData->Prices as $row) {
                $row->_Server = $server;
                $crossWorldPrices[] = (Array)$row;
                
                if ($row->IsHQ) {
                    $crossWorldPricesHQ[] = (Array)$row;
                } else {
                    $crossWorldPricesNQ[] = (Array)$row;
                }
            }
            
            foreach ($marketData->History as $row) {
                $row->_Server = $server;
                $crossWorldHistory[] = (Array)$row;
    
                if ($row->IsHQ) {
                    $crossWorldHistoryHQ[] = (Array)$row;
                } else {
                    $crossWorldHistoryNQ[] = (Array)$row;
                }
            }
        }
    
        Arrays::sortBySubKey($crossWorldPrices, 'PricePerUnit', true);
        Arrays::sortBySubKey($crossWorldPricesHQ, 'PricePerUnit', true);
        Arrays::sortBySubKey($crossWorldPricesNQ, 'PricePerUnit', true);
        Arrays::sortBySubKey($crossWorldHistory, 'PurchaseDate');
        Arrays::sortBySubKey($crossWorldHistoryHQ, 'PurchaseDate');
        Arrays::sortBySubKey($crossWorldHistoryNQ, 'PurchaseDate');
    
        $this->census->_Global->Prices = json_decode(json_encode($crossWorldPrices));
        $this->census->_Global->History = json_decode(json_encode($crossWorldHistory));
        $this->census->_Global->PricesHQ = json_decode(json_encode($crossWorldPricesHQ));
        $this->census->_Global->PricesNQ = json_decode(json_encode($crossWorldPricesNQ));
        $this->census->_Global->HistoryHQ = json_decode(json_encode($crossWorldHistoryHQ));
        $this->census->_Global->HistoryNQ = json_decode(json_encode($crossWorldHistoryNQ));
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
                    'Server'       => $row->_Server ?? $server,
                    'PricePerUnit' => $row->PricePerUnit,
                    'PriceTotal'   => $row->PriceTotal,
                    'Quantity'     => $row->Quantity
                ];
            }
    
            if ($cheapestPriceNQ === null && $row->IsHQ == false) {
                $cheapestPriceNQ = [
                    'Server'       => $row->_Server ?? $server,
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
    private function buildChartBubble($server, $marketData)
    {
        $this->buildChartBubbleHandler($server, $marketData->Prices, 'Prices');
        $this->buildChartBubbleHandler($server, $marketData->History, 'History');
    }
    
    private function buildChartBubbleHandler($server, $tableData, $type)
    {
        // the max scale in pixels
        $bubbleScaleFactorHQ = 0;
        $bubbleScaleFactorNQ = 0;
        
        $variations = [];
        $chartDataHQ = [];
        $chartDataNQ = [];
        
        $colors = [];
        
     
        /**
         * Calculate the max for scaling factors
         */
        foreach ($tableData as $row) {
            $variations[$row->Quantity] = 1;
            $colors[$row->_Server ?? $server] = sprintf("rgb(%s,%s,%s)", mt_rand(100,255),mt_rand(100,255),mt_rand(100,255));
            
            if ($row->IsHQ && $row->Quantity > $bubbleScaleFactorHQ) {
                $bubbleScaleFactorHQ = $row->Quantity;
            } else if ($row->Quantity > $bubbleScaleFactorNQ) {
                $bubbleScaleFactorNQ = $row->Quantity;
            }
        }
        
        // this will increase the max bubble size depending how many data variations there are.
        $variationValue = 0;
        $variationValue = count($variations) > 2 ? 1 : $variationValue;
        $variationValue = count($variations) > 4 ? 2 : $variationValue;
        $maxBubblePix = self::BUBBLE_MAX_PX[$variationValue];

    
        foreach ($tableData as $row) {
            // calculate quantity radius
            $radiusFactor = $row->Quantity / ($row->IsHQ ? $bubbleScaleFactorHQ : $bubbleScaleFactorNQ);
            $radius = $maxBubblePix * $radiusFactor;
            
            // limits
            $radius = $radius < self::BUBBLE_MIN_PX ? self::BUBBLE_MIN_PX : $radius;
            $radius = $radius > $maxBubblePix ? $maxBubblePix : $radius;
        
            if ($row->IsHQ) {
                $chartDataHQ[] = [
                    'x' => count($chartDataHQ),
                    'y' => $row->PricePerUnit,
                    'r' => $radius,
                    'server' => $row->_Server ?? $server,
                    'backgroundColor' => $colors[$row->_Server ?? $server],
                    'borderColor' => $colors[$row->_Server ?? $server],
                ];
            } else {
                $chartDataNQ[] = [
                    'x' => count($chartDataNQ),
                    'y' => $row->PricePerUnit,
                    'r' => $radius,
                    'server' => $row->_Server ?? $server,
                ];
            }
        }
    
        $this->census->{$server}->{"{$type}_BubbleChart_HQ"} = $chartDataHQ;
        $this->census->{$server}->{"{$type}_BubbleChart_NQ"} = $chartDataNQ;
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
             * Calculate averages
             */
            $averagePerHQ = [];
            $averagePerNQ = [];
            foreach ($marketData->Prices as $i => $price) {
                if ($price->IsHQ) {
                    $averagePerHQ[$i] = $price->PricePerUnit;
                }
    
                if ($price->IsHQ == false) {
                    $averagePerNQ[$i] = $price->PricePerUnit;
                }
            }

            $averagePerHQ = ceil(Average::mean($averagePerHQ));
            $averagePerNQ = ceil(Average::mean($averagePerNQ));

            /**
             * Now go through again and remove if its X above the average
             */
            foreach ($marketData->Prices as $i => $price) {
                $maxValue = ($price->IsHQ ? $averagePerHQ : $averagePerNQ) * self::JUNK_PRICE_FACTOR;
                $maxValueHQ = $averagePerHQ * self::JUNK_PRICE_FACTOR;

                // remove if price is above max, or if it's NQ, it also checks price against max HQ.
                if ($price->PricePerUnit > $maxValue) {
                    unset($marketData->Prices[$i]);
                } else if ($averagePerHQ > 0 && $price->IsHQ === false && $price->PricePerUnit > $maxValueHQ) {
                    unset($marketData->Prices[$i]);
                }
            }
        }
    }
    
    private function removeNonUpdatedItems($market)
    {
        foreach ($market as $server => $marketData)
        {
            if (!isset($marketData->UpdatePriority)) {
                continue;
            }
            
            if (in_array($marketData->UpdatePriority, CompanionConstants::QUEUES) == false) {
                $marketData->Prices = [];
                $marketData->History = [];
            }
        }
    }
}
