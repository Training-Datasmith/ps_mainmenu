<?php

class Group
{
    private static $featureActive = false;

    public static function reset()
    {
        self::$featureActive = false;
    }

    public static function isFeatureActive()
    {
        return self::$featureActive;
    }

    public static function setFeatureActive($active)
    {
        self::$featureActive = (bool) $active;
    }
}
