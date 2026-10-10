<?php

class Hook
{
    private static $listeners = array();
    private static $failNextRegistration = false;

    public static function reset()
    {
        self::$listeners = array();
        self::$failNextRegistration = false;
    }

    public static function setFailNextRegistration($fail)
    {
        self::$failNextRegistration = (bool) $fail;
    }

    public static function shouldFailNextRegistration()
    {
        if (self::$failNextRegistration) {
            self::$failNextRegistration = false;

            return true;
        }

        return false;
    }

    public static function addListener($hookName, $callback)
    {
        if (!isset(self::$listeners[$hookName])) {
            self::$listeners[$hookName] = array();
        }
        self::$listeners[$hookName][] = $callback;
    }

    public static function exec($hookName, $params = array())
    {
        if (!isset(self::$listeners[$hookName])) {
            return null;
        }
        foreach (self::$listeners[$hookName] as $callback) {
            call_user_func($callback, $params);
        }

        return null;
    }
}
