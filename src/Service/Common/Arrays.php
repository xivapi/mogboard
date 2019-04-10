<?php

namespace App\Service\Common;

class Arrays
{
    /**
     * Converts a stdClass to an array, including all nested objects and removing private/functions,
     * this is not performance since it uses JSON methods.
     */
    public static function stdClassToArray($stdClass)
    {
        return json_decode(json_encode($stdClass), true);
    }
    
    /**
     * Sort an array by a specific sub key
     */
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
