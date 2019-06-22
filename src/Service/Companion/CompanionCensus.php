<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Common\Service\Redis\Redis;
use App\Common\Utils\Arrays;
use MathPHP\Statistics\Average;

/**
 * Generate all stats about items, including cross-world summaries, chart data,
 * hq/nq sale amounts, the lot!
 */
class CompanionCensus
{
    const BUBBLE_MIN_PX = 3;
    const BUBBLE_MAX_PX = [ 6, 12, 20 ];

    /** @var \array */
    private $census = [];
    /** @var string */
    private $homeServer = null;
    
    /**
     * Generate Mark Census!
     */
    public function generate(string $dc, int $itemId, $market)
    {
        $key = "mbv4_market_census_{$itemId}_{$dc}";
    
        if ($data = Redis::cache()->get($key)) {
            return json_decode(json_encode($data), true);
        }
        
        // server users home world
        $this->homeServer = GameServers::getServer();
        
        // add for global statistics
        $this->census['Global'] = [];
    
        /**
         * First we remove all "silly" prices, these are the ones
         * that are 3x the price of the cheapest 5
         */
        $market = $this->removeJunkPrices($market);
    
        /**
         * Per server statistics
         */
        foreach ($market as $server => $marketData) {
            $this->buildAverage('PricePerUnit', $server, $marketData);
            $this->buildAverage('PriceTotal', $server, $marketData);
            $this->calculateNumericStatistics($server, $marketData);
            
            // high charts
            $this->buildHighChartHistory($server, $marketData);
    
            // deprecated
            $this->buildChartBubble($server, $marketData);
        }
    
        /**
         * Global Statistics
         */
        $this->buildCrossWorldPricesAndHistory($market);
    
        // high charts
        $this->buildHighChartHistory('All', $this->census['Global']);
        
        $this->buildAverage('PricePerUnit', 'Global', $this->census['Global']);
        $this->buildAverage('PriceTotal', 'Global', $this->census['Global']);
        $this->calculateNumericStatistics('Global', $this->census['Global']);
    
        // deprecated
        $this->buildChartBubble('Global', $this->census['Global']);
    
        # Redis::cache()->set($key, $this->census, 60);
    
        return $this->census;
    }
    
    private function buildHighChartHistory($server, $marketData)
    {
        $this->census[$server]["HC_History_HQ"] = [];
        $this->census[$server]["HC_History_NQ"] = [];
    
        $this->census[$server]["HC_History_HQ_volume"] = [];
        $this->census[$server]["HC_History_NQ_volume"] = [];
    
        foreach ($marketData['History'] as $i => $row) {
            $key   = ($row['IsHQ'] ? "HC_History_HQ" : "HC_History_NQ");
            
            $date  = (int)$row['PurchaseDateMS'];
            $value = (int)$row['PricePerUnit'];
            $qty   = (int)$row['Quantity'];
            
            if (isset($this->census[$server][$key][$date])) {
                $this->census[$server][$key][$date][1] += $value;
                $this->census[$server][$key . "_volume"][$date][1] += $qty;
                continue;
            }
            
            // set date and value
            $this->census[$server][$key][$date] = [
                $date,
                $value,
                //'server' => ($row['_Server'] ?? $server)
            ];
            
            $this->census[$server][$key . "_volume"][$date] = [
                $date,
                $qty,
                //'server' => ($row['_Server'] ?? $server )
            ];
        }
    
        Arrays::sortBySubKey($this->census[$server]["HC_History_HQ"], 0, true);
        Arrays::sortBySubKey($this->census[$server]["HC_History_NQ"], 0, true);
        Arrays::sortBySubKey($this->census[$server]["HC_History_HQ_volume"], 0, true);
        Arrays::sortBySubKey($this->census[$server]["HC_History_NQ_volume"], 0, true);
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
        
        foreach ($marketData['Prices'] as $row) {
            
            if (!isset($row['IsHQ'])) {
                print_r($marketData);
                die;
            }
            
            if ($row['IsHQ']) {
                $pricesHQ[] = $row[$field];
            } else {
                $pricesNQ[] = $row[$field];
            }
        }
    
        foreach ($marketData['History'] as $row) {
            if ($row['IsHQ']) {
                $historyHQ[] = $row[$field];
            } else {
                $historyNQ[] = $row[$field];
            }
        }

        asort($pricesHQ);
        asort($pricesNQ);
        asort($historyHQ);
        asort($historyNQ);

        array_splice($pricesHQ, 150);
        array_splice($pricesNQ, 150);
        array_splice($historyHQ, 150);
        array_splice($historyNQ, 150);

        $this->census[$server]["Prices_Average_{$field}_HQ"]  = round(Average::mean($pricesHQ));
        $this->census[$server]["Prices_Average_{$field}_NQ"]  = round(Average::mean($pricesNQ));
        $this->census[$server]["History_Average_{$field}_HQ"] = round(Average::mean($historyHQ));
        $this->census[$server]["History_Average_{$field}_NQ"] = round(Average::mean($historyNQ));
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
            foreach ($marketData['Prices'] as $row) {
                $row['_Server'] = $server;
                $crossWorldPrices[] = (Array)$row;
                
                if ($row['IsHQ']) {
                    $crossWorldPricesHQ[] = (Array)$row;
                } else {
                    $crossWorldPricesNQ[] = (Array)$row;
                }
            }
            
            foreach ($marketData['History'] as $row) {
                $row['_Server'] = $server;
                $crossWorldHistory[] = (Array)$row;
    
                if ($row['IsHQ']) {
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
    
        $this->census['Global']['Prices']       = $crossWorldPrices;
        $this->census['Global']['History']      = $crossWorldHistory;
        $this->census['Global']['PricesHQ']     = $crossWorldPricesHQ;
        $this->census['Global']['PricesNQ']     = $crossWorldPricesNQ;
        $this->census['Global']['HistoryHQ']    = $crossWorldHistoryHQ;
        $this->census['Global']['HistoryNQ']    = $crossWorldHistoryNQ;
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
        
        foreach ($marketData['Prices'] as $row) {
            if ($cheapestPriceHQ === null && $row['IsHQ']) {
                $cheapestPriceHQ = [
                    'Server'       => $row['_Server'] ?? $server,
                    'PricePerUnit' => $row['PricePerUnit'],
                    'PriceTotal'   => $row['PriceTotal'],
                    'Quantity'     => $row['Quantity']
                ];
            }
    
            if ($cheapestPriceNQ === null && $row['IsHQ'] == false) {
                $cheapestPriceNQ = [
                    'Server'       => $row['_Server'] ?? $server,
                    'PricePerUnit' => $row['PricePerUnit'],
                    'PriceTotal'   => $row['PriceTotal'],
                    'Quantity'     => $row['Quantity']
                ];
            }
            
            if ($row['IsHQ']) {
                $totalGilHQ += $row['PriceTotal'];
                $totalStockHQ += $row['Quantity'];
            } else {
                $totalGilNQ += $row['PriceTotal'];
                $totalStockNQ += $row['Quantity'];
            }
        }
    
        $this->census[$server]["Prices_Numeric_TotalGilHQ"] = $totalGilHQ;
        $this->census[$server]["Prices_Numeric_TotalGilNQ"] = $totalGilNQ;
        $this->census[$server]["Prices_Numeric_TotalStockHQ"] = $totalStockHQ;
        $this->census[$server]["Prices_Numeric_TotalStockNQ"] = $totalStockNQ;
    
        $this->census[$server]["Prices_CheapestHQ"] = $cheapestPriceHQ;
        $this->census[$server]["Prices_CheapestNQ"] = $cheapestPriceNQ;
    }
    
    /**
     * @deprecated
     * Simple chart with quantity and price, this is useful for bubble charts, this
     * uses the average quantity to know the "scale" of the bubble radius
     */
    private function buildChartBubble($server, $marketData)
    {
        $this->buildChartBubbleHandler($server, $marketData['Prices'], 'Prices');
        $this->buildChartBubbleHandler($server, $marketData['History'], 'History');
    }
    
    /**
     * @deprecated
     * @param $server
     * @param $tableData
     * @param $type
     */
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
            $variations[$row['Quantity']] = 1;
            $colors[$row['_Server'] ?? $server] = sprintf("rgb(%s,%s,%s)", mt_rand(100,255),mt_rand(100,255),mt_rand(100,255));
            
            if ($row['IsHQ'] && $row['Quantity'] > $bubbleScaleFactorHQ) {
                $bubbleScaleFactorHQ = $row['Quantity'];
            } else if ($row['Quantity'] > $bubbleScaleFactorNQ) {
                $bubbleScaleFactorNQ = $row['Quantity'];
            }
        }
        
        // this will increase the max bubble size depending how many data variations there are.
        $variationValue = 0;
        $variationValue = count($variations) > 2 ? 1 : $variationValue;
        $variationValue = count($variations) > 4 ? 2 : $variationValue;
        $maxBubblePix = self::BUBBLE_MAX_PX[$variationValue];

    
        foreach ($tableData as $row) {
            // calculate quantity radius
            $radiusFactor = $row['Quantity'] / ($row['IsHQ'] ? $bubbleScaleFactorHQ : $bubbleScaleFactorNQ);
            $radius = $maxBubblePix * $radiusFactor;
            
            // limits
            $radius = $radius < self::BUBBLE_MIN_PX ? self::BUBBLE_MIN_PX : $radius;
            $radius = $radius > $maxBubblePix ? $maxBubblePix : $radius;
        
            if ($row['IsHQ']) {
                $chartDataHQ[] = [
                    'x' => count($chartDataHQ),
                    'y' => $row['PricePerUnit'],
                    'r' => $radius,
                    'server' => $row['_Server'] ?? $server,
                    'backgroundColor' => $colors[$row['_Server'] ?? $server],
                    'borderColor' => $colors[$row['_Server'] ?? $server],
                ];
            } else {
                $chartDataNQ[] = [
                    'x' => count($chartDataNQ),
                    'y' => $row['PricePerUnit'],
                    'r' => $radius,
                    'server' => $row['_Server'] ?? $server,
                ];
            }
        }
    
        $this->census[$server]["{$type}_BubbleChart_HQ"] = $chartDataHQ;
        $this->census[$server]["{$type}_BubbleChart_NQ"] = $chartDataNQ;
    }
    
    /**
     * Remove junk prices from the market data
     */
    private function removeJunkPrices($market)
    {
        foreach ($market as $server => $marketData) {
            // add this, will need for later
            $this->census[$server] = [];

            /**
             * Calculate averages
             */
            $averagePerHQ = [];
            $averagePerNQ = [];
            
            foreach ($marketData['Prices'] as $i => $price) {
                if ($price['IsHQ']) {
                    $averagePerHQ[$i] = $price['PricePerUnit'];
                }
    
                if ($price['IsHQ'] == false) {
                    $averagePerNQ[$i] = $price['PricePerUnit'];
                }
            }

            $maxPerHQ = ceil(Average::median($averagePerHQ)) * 3;
            $maxPerNQ = ceil(Average::median($averagePerNQ)) * 3;

            /**
             * Now go through again and remove if its X above the average
             */
            foreach ($marketData['Prices'] as $i => $price) {
                if (
                    // if above NQ median * x
                    ($price['IsHQ'] && (int)$price['PricePerUnit'] > $maxPerHQ) ||

                    // if above HQ median * x
                    (!$price['IsHQ']  && (int)$price['PricePerUnit'] > $maxPerNQ)
                ) {
                    unset($marketData['Prices'][$i]);
                }
            }
            
            // do same for history
            foreach ($marketData['History'] as $i => $history) {
                if (
                    // if above NQ median * x
                    ($history['IsHQ'] && (int)$history['PricePerUnit'] > $maxPerHQ) ||
        
                    // if above HQ median * x
                    (!$history['IsHQ'] && (int)$history['PricePerUnit'] > $maxPerNQ)
                ) {
                    unset($marketData['History'][$i]);
                }
            }
            
            $market[$server] = $marketData;
        }
        
        return $market;
    }
}
