<?php

class Cache
{
    private static $store = array();

    public static function reset()
    {
        self::$store = array();
    }

    public static function isStored($id)
    {
        return isset(self::$store[$id]);
    }

    public static function store($id, $value)
    {
        self::$store[$id] = $value;
    }

    public static function retrieve($id)
    {
        return self::$store[$id];
    }
}
