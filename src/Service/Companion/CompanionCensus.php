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
            if ($marketData === null) {
                continue;
            }
            
            $this->buildAverage('pricePerUnit', $server, $marketData);
            $this->buildAverage('total', $server, $marketData);
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
        
        $this->buildAverage('pricePerUnit', 'Global', $this->census['Global']);
        $this->buildAverage('total', 'Global', $this->census['Global']);
        $this->calculateNumericStatistics('Global', $this->census['Global']);
    
        // deprecated
        $this->buildChartBubble('Global', $this->census['Global']);
    
        Redis::cache()->set($key, $this->census, 60);
    
        return $this->census;
    }
    
    private function buildHighChartHistory($server, $marketData)
    {
        $this->census[$server]["HC_History_HQ"] = [];
        $this->census[$server]["HC_History_NQ"] = [];
    
        $this->census[$server]["HC_History_HQ_volume"] = [];
        $this->census[$server]["HC_History_NQ_volume"] = [];

        if (!\array_key_exists('recentHistory', $marketData))
            return;
    
        foreach ($marketData['recentHistory'] as $i => $row) {
            $key   = ($row['hq'] ? "HC_History_HQ" : "HC_History_NQ");
            
            $date  = (int)$row['timestamp'] * 1000;
            $value = (int)$row['pricePerUnit'];
            $qty   = (int)$row['quantity'];
            
            if (isset($this->census[$server][$key][$date])) {
                $this->census[$server][$key][$date][1] += ceil($value);
                $this->census[$server][$key . "_volume"][$date][1] += ceil($qty);
                continue;
            }
            
            // set date and value
            $this->census[$server][$key][$date] = [
                $date,
                ceil($value),
                //'server' => ($row['_Server'] ?? $server)
            ];
            
            $this->census[$server][$key . "_volume"][$date] = [
                $date,
                ceil($qty),
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

        if (!\array_key_exists('listings', $marketData))
            $marketData['listings'] = [];

        if (!\array_key_exists('recentHistory', $marketData))
            $marketData['recentHistory'] = [];
        
        foreach ($marketData['listings'] as $row) {
            
            if (!isset($row['hq'])) {
                print_r($marketData);
                die;
            }
            
            if ($row['hq']) {
                $pricesHQ[] = $row[$field];
            } else {
                $pricesNQ[] = $row[$field];
            }
        }
    
        foreach ($marketData['recentHistory'] as $row) {
            if ($row['hq']) {
                $historyHQ[] = $row[$field];
            } else {
                $historyNQ[] = $row[$field];
            }
        }

        /*
        if ($server=="Global")
            echo \var_dump($marketData);
            */

        asort($pricesHQ);
        asort($pricesNQ);

        array_splice($pricesHQ, 20);
        array_splice($pricesNQ, 20);
        array_splice($historyHQ, 20);
        array_splice($historyNQ, 20);

        $this->census[$server]["Prices_Average_{$field}_HQ"]  = round(Average::mean($pricesHQ));
        $this->census[$server]["Prices_Average_{$field}_NQ"]  = round(Average::mean($pricesNQ));
        $this->census[$server]["History_Average_{$field}_HQ"] = round(Average::mean($historyHQ));
        $this->census[$server]["History_Average_{$field}_NQ"] = round(Average::mean($historyNQ));

        //echo $server.':'.$field.':'.$this->census[$server]["Prices_Average_{$field}_HQ"].'<br>';
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
            if ($marketData === null) {
                continue;
            }
            
            foreach ($marketData['listings'] as $row) {
                $row['_Server'] = $server;
                $crossWorldPrices[] = (Array)$row;
                
                if ($row['hq']) {
                    $crossWorldPricesHQ[] = (Array)$row;
                } else {
                    $crossWorldPricesNQ[] = (Array)$row;
                }
            }
            
            foreach ($marketData['recentHistory'] as $row) {
                $row['_Server'] = $server;
                $crossWorldHistory[] = (Array)$row;
    
                if ($row['hq']) {
                    $crossWorldHistoryHQ[] = (Array)$row;
                } else {
                    $crossWorldHistoryNQ[] = (Array)$row;
                }
            }
        }
    
        Arrays::sortBySubKey($crossWorldPrices, 'pricePerUnit', true);
        Arrays::sortBySubKey($crossWorldPricesHQ, 'pricePerUnit', true);
        Arrays::sortBySubKey($crossWorldPricesNQ, 'pricePerUnit', true);
        Arrays::sortBySubKey($crossWorldHistory, 'timestamp');
        Arrays::sortBySubKey($crossWorldHistoryHQ, 'timestamp');
        Arrays::sortBySubKey($crossWorldHistoryNQ, 'timestamp');
    
        $this->census['Global']['listings']       = $crossWorldPrices;
        $this->census['Global']['recentHistory']      = $crossWorldHistory;
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

        if (!\array_key_exists('listings', $marketData))
            $marketData['listings'] = [];
        
        foreach ($marketData['listings'] as $row) {
            if ($cheapestPriceHQ === null && $row['hq']) {
                $cheapestPriceHQ = [
                    'Server'       => $row['_Server'] ?? $server,
                    'PricePerUnit' => $row['pricePerUnit'],
                    'PriceTotal'   => $row['total'],
                    'Quantity'     => $row['quantity']
                ];
            }
    
            if ($cheapestPriceNQ === null && $row['hq'] == false) {
                $cheapestPriceNQ = [
                    'Server'       => $row['_Server'] ?? $server,
                    'PricePerUnit' => $row['pricePerUnit'],
                    'PriceTotal'   => $row['total'],
                    'Quantity'     => $row['quantity']
                ];
            }
            
            if ($row['hq']) {
                $totalGilHQ += $row['total'];
                $totalStockHQ += $row['quantity'];
            } else {
                $totalGilNQ += $row['total'];
                $totalStockNQ += $row['quantity'];
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
        if (\array_key_exists('listings', $marketData))
            $this->buildChartBubbleHandler($server, $marketData['listings'], 'Prices');;

        if (\array_key_exists('recentHistory', $marketData))
            $this->buildChartBubbleHandler($server, $marketData['recentHistory'], 'History');
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
            $variations[$row['quantity']] = 1;
            $colors[$row['_Server'] ?? $server] = sprintf("rgb(%s,%s,%s)", mt_rand(100,255),mt_rand(100,255),mt_rand(100,255));
            
            if ($row['hq'] && $row['quantity'] > $bubbleScaleFactorHQ) {
                $bubbleScaleFactorHQ = $row['quantity'];
            } else if ($row['quantity'] > $bubbleScaleFactorNQ) {
                $bubbleScaleFactorNQ = $row['quantity'];
            }
        }
        
        // this will increase the max bubble size depending how many data variations there are.
        $variationValue = 0;
        $variationValue = count($variations) > 2 ? 1 : $variationValue;
        $variationValue = count($variations) > 4 ? 2 : $variationValue;
        $maxBubblePix = self::BUBBLE_MAX_PX[$variationValue];

    
        foreach ($tableData as $row) {
            // calculate quantity radius
            $radiusFactor = $row['quantity'] / ($row['hq'] ? $bubbleScaleFactorHQ : $bubbleScaleFactorNQ);
            $radius = $maxBubblePix * $radiusFactor;
            
            // limits
            $radius = $radius < self::BUBBLE_MIN_PX ? self::BUBBLE_MIN_PX : $radius;
            $radius = $radius > $maxBubblePix ? $maxBubblePix : $radius;
        
            if ($row['hq']) {
                $chartDataHQ[] = [
                    'x' => count($chartDataHQ),
                    'y' => $row['pricePerUnit'],
                    'r' => $radius,
                    'server' => $row['_Server'] ?? $server,
                    'backgroundColor' => $colors[$row['_Server'] ?? $server],
                    'borderColor' => $colors[$row['_Server'] ?? $server],
                ];
            } else {
                $chartDataNQ[] = [
                    'x' => count($chartDataNQ),
                    'y' => $row['pricePerUnit'],
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
            if ($marketData === null) {
                continue;
            }
            
            // add this, will need for later
            $this->census[$server] = [];

            /**
             * Calculate averages
             */
            $averagePerHQ = [];
            $averagePerNQ = [];
            
            foreach ($marketData['listings'] as $i => $price) {
                if ($price['hq']) {
                    $averagePerHQ[$i] = $price['pricePerUnit'];
                }
    
                if ($price['hq'] == false) {
                    $averagePerNQ[$i] = $price['pricePerUnit'];
                }
            }

            $maxPerHQ = ceil(Average::median($averagePerHQ)) * 3;
            $maxPerNQ = ceil(Average::median($averagePerNQ)) * 3;

            /**
             * Now go through again and remove if its X above the average
             */
            foreach ($marketData['listings'] as $i => $price) {
                if (
                    // if above NQ median * x
                    ($price['hq'] && (int)$price['pricePerUnit'] > $maxPerHQ) ||

                    // if above HQ median * x
                    (!$price['hq']  && (int)$price['pricePerUnit'] > $maxPerNQ)
                ) {
                    unset($marketData['listings'][$i]);
                }
            }
            
            // do same for history
            foreach ($marketData['recentHistory'] as $i => $history) {
                if (
                    // if above NQ median * x
                    ($history['hq'] && (int)$history['pricePerUnit'] > $maxPerHQ) ||
        
                    // if above HQ median * x
                    (!$history['hq'] && (int)$history['pricePerUnit'] > $maxPerNQ)
                ) {
                    unset($marketData['recentHistory'][$i]);
                }
            }
            
            $market[$server] = $marketData;
        }
        
        return $market;
    }
}
