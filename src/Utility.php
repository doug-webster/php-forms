<?php
/**
 * a collection of static utility methods used by Form package
 */

namespace GZMP;

class Utility
{
    /**
     * implode a potentially multi-demensional array
     * @param string $separator - string to use to concatenate array elements
     * @param array $var - the array to implode
     * @return string - the given array transformed into a string
     */
    public static function implode_recursive(string $separator, array $var)
    {
        $return = '';
        foreach ($var as $k => $val) {
            if (is_array($val))
                $return .= self::implode_recursive($separator, $val);
            else
                $return .= (string)$val;
            $return .= $separator;
        }
        return trim($return, $separator);
    }
    
    /**
     * remove slashes if magic quotes is on, do nothing if null
     * @param mixed (string or null) &$string - variable to modify
     */
    public static function reverse_magic_quotes(?string &$string)
    {
        if (is_null($string))
            return;
        if (get_magic_quotes_runtime() || get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
    }
    
    /**
     * apply trim directly to the submitted variable, do nothing if null
     * @param mixed (string or null) &$string - variable to modify
     */
    public static function trimd(?string &$string)
    {
        if (is_null($string))
            return;
        $string = trim($string);
    }
    
    /**
     * apply strtolower directly to submitted variable, do nothing if null
     * @param mixed (string or null) &$string - variable to modify
     */
    public static function strtolowerd(?string &$string)
    {
        if (is_null($string))
            return;
        $string = strtolower($string);
    }

}
