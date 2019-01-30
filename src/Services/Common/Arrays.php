<?php

namespace App\Services\Common;

class Arrays
{
    public static function sortBySubKey(&$array, $subkey, $sort_ascending = false)
    {
        if ($array) {
            if (count($array)) {
                $temp_array[key($array)] = array_shift($array);
            }
            
            foreach($array as $key => $val) {
                $offset = 0;
                $found = false;
                foreach($temp_array as $tmp_key => $tmp_val) {
                    if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
                        $temp_array = array_merge((array)array_slice($temp_array,0,$offset),
                            [$key => $val],
                            array_slice($temp_array,$offset)
                        );
                        $found = true;
                    }
                    
                    $offset++;
                }
                
                if(!$found) {
                    $temp_array = array_merge($temp_array, [$key => $val]);
                }
            }
            
            if ($sort_ascending) {
                $array = array_reverse($temp_array);
            } else {
                $array = $temp_array;
            }
            
            $array = array_values($array);
        }
    }
}
