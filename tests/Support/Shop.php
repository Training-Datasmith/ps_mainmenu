<?php

class Shop
{
    const CONTEXT_SHOP = 1;
    const CONTEXT_GROUP = 2;
    const CONTEXT_ALL = 4;

    public $id;
    public $name;

    private static $context = 1;
    private static $contextShopId = 1;
    private static $contextShopGroupId = 1;
    private static $featureActive = false;
    private static $shops = array();

    public function __construct($id = null)
    {
        if ($id !== null) {
            $this->id = (int) $id;
            $data = self::getShop($this->id);
            $this->name = $data ? $data['name'] : '';
        }
    }

    public static function reset()
    {
        self::$context = self::CONTEXT_SHOP;
        self::$contextShopId = 1;
        self::$contextShopGroupId = 1;
        self::$featureActive = false;
        self::$shops = array();
    }

    public static function seedShop($id, $name, $active = 1)
    {
        self::$shops[(int) $id] = array(
            'id_shop' => (int) $id,
            'name' => $name,
            'active' => (int) $active,
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'shop` (`id_shop`, `name`, `active`) VALUES (' .
            (int) $id . ", '" . pSQL($name) . "', " . (int) $active . ')'
        );
    }

    public static function setContext($context, $id = null)
    {
        self::$context = (int) $context;
        if ($context === self::CONTEXT_SHOP) {
            self::$contextShopId = (int) $id;
        } elseif ($context === self::CONTEXT_GROUP) {
            self::$contextShopGroupId = (int) $id;
        }
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function getContextShopID($null = false)
    {
        return self::$contextShopId;
    }

    public static function getContextShopGroupID()
    {
        return self::$contextShopGroupId;
    }

    public static function getGroupFromShop($shopId, $returnGroup = true)
    {
        return 1;
    }

    public static function getContextListShopID($share = false)
    {
        if (self::$context === self::CONTEXT_SHOP) {
            return array((int) self::$contextShopId);
        }
        if (self::$context === self::CONTEXT_GROUP) {
            return self::getShops(true, self::$contextShopGroupId, true);
        }

        return self::getShops(true, null, true);
    }

    public static function getShops($active = true, $idShopGroup = null, $getAsListId = false)
    {
        $shops = self::$shops;
        uasort($shops, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $results = array();
        foreach ($shops as $id => $shop) {
            if ($active && !$shop['active']) {
                continue;
            }
            if ($getAsListId) {
                $results[$id] = $id;
            } else {
                $results[$id] = $shop;
            }
        }

        return $results;
    }

    public static function isFeatureActive()
    {
        return self::$featureActive;
    }

    public static function setFeatureActive($active)
    {
        self::$featureActive = (bool) $active;
    }

    public static function getShop($id)
    {
        return isset(self::$shops[$id]) ? self::$shops[$id] : null;
    }

    public static function getShopsCollection($active = true)
    {
        $collection = array();
        foreach (self::$shops as $id => $shop) {
            if ($active && !$shop['active']) {
                continue;
            }
            $collection[] = new Shop((int) $id);
        }

        return $collection;
    }

    public function setUrl()
    {
        return true;
    }

    public function getBaseURL()
    {
        return 'https://shop' . (int) $this->id . '.example.test/';
    }
}
