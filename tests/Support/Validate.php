<?php

class Validate
{
    public static function isLoadedObject($object)
    {
        return is_object($object) && !empty($object->id);
    }

    public static function isInt($value)
    {
        return (string) (int) $value === (string) $value;
    }

    public static function isBool($value)
    {
        return $value === true || $value === false || $value === 0 || $value === 1 || $value === '0' || $value === '1';
    }
}
