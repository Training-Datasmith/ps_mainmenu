<?php

class Tools
{
    private static $values = array();
    private static $submitted = array();

    public static function reset()
    {
        self::$values = array();
        self::$submitted = array();
    }

    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }

    public static function setSubmit($key)
    {
        self::$submitted[$key] = true;
    }

    public static function getValue($key, $default = false)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function isSubmit($key)
    {
        return isset(self::$submitted[$key]);
    }

    public static function safeOutput($string)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
    }

    public static function htmlentitiesDecodeUTF8($string)
    {
        return html_entity_decode((string) $string, ENT_QUOTES, 'UTF-8');
    }

    public static function str2url($string)
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);

        return trim($string, '-');
    }

    public static function displayError($message = '')
    {
        return (string) $message;
    }

    public static function getAdminTokenLite($tab)
    {
        return 'token';
    }
}
