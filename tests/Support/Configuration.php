<?php

class Configuration
{
    private static $global = array();
    private static $shop = array();

    public static function reset()
    {
        self::$global = array();
        self::$shop = array();
        self::updateGlobalValue('PS_LANG_DEFAULT', '1');
    }

    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        if ($idShop !== null || $idShopGroup !== null) {
            $shopKey = self::shopKey($key, $idShopGroup, $idShop);
            if (isset(self::$shop[$shopKey])) {
                return self::$shop[$shopKey];
            }
        }

        $ctxShop = Shop::getContextShopID();
        $ctxGroup = Shop::getContextShopGroupID();
        $ctxKey = self::shopKey($key, $ctxGroup, $ctxShop);
        if (isset(self::$shop[$ctxKey])) {
            return self::$shop[$ctxKey];
        }

        if (isset(self::$global[$key])) {
            return self::$global[$key];
        }

        return null;
    }

    public static function updateValue($key, $value, $html = false, $idShopGroup = null, $idShop = null)
    {
        if ($idShop === null && $idShopGroup === null) {
            $idShop = Shop::getContextShopID();
            $idShopGroup = Shop::getContextShopGroupID();
        }
        self::$shop[self::shopKey($key, $idShopGroup, $idShop)] = $value;

        return true;
    }

    public static function updateGlobalValue($key, $value)
    {
        self::$global[$key] = $value;

        return true;
    }

    public static function deleteByName($key)
    {
        unset(self::$global[$key]);
        foreach (array_keys(self::$shop) as $shopKey) {
            if (strpos($shopKey, $key . '|') === 0) {
                unset(self::$shop[$shopKey]);
            }
        }

        return true;
    }

    private static function shopKey($key, $idShopGroup, $idShop)
    {
        return $key . '|' . (int) $idShopGroup . '|' . (int) $idShop;
    }
}
