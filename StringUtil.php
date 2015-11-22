<?php
/**
 * Class StringUtil
 * A simple utility class with some string related function
 */
abstract class StringUtil
{
    /**
     * Check if a string has a certain suffix
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
}
