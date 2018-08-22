<?php
namespace GZMP;

class HTML
{
    /**
     * make value safe for html output
     * @param string $value - string to be escaped
     * @return string - escaped string
     */
    public static function escape(?string $value)
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }

}
