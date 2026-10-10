<?php

class Category
{
    public $id;
    public $name;

    private static $nested = array();
    private static $access = array();

    public function __construct($id = null, $idLang = null)
    {
        $this->id = (int) $id;
        $this->name = 'Category ' . $this->id;
    }

    public static function reset()
    {
        self::$nested = array();
        self::$access = array();
    }

    public static function setNestedCategories($rootId, $tree, $groups = null)
    {
        self::$nested[(int) $rootId] = array('tree' => $tree, 'groups' => $groups);
    }

    public static function setAccess($categoryId, $allowed)
    {
        self::$access[(int) $categoryId] = (bool) $allowed;
    }

    public static function getNestedCategories($rootId, $idLang = false, $active = true, $groups = null)
    {
        if (!isset(self::$nested[(int) $rootId])) {
            return array();
        }
        $entry = self::$nested[(int) $rootId];
        if ($entry['groups'] !== null && $groups !== $entry['groups']) {
            return array();
        }

        return $entry['tree'];
    }

    public function getLink()
    {
        return 'https://shop.example.test/category/' . (int) $this->id;
    }

    public function checkAccess($customerId)
    {
        if (!isset(self::$access[$this->id])) {
            return true;
        }

        return self::$access[$this->id];
    }
}

class Product
{
    public $id;
    public $name;

    public function __construct($id = null, $full = true, $idLang = null)
    {
        $this->id = (int) $id;
        $this->name = 'Product ' . $this->id;
    }

    public function getLink()
    {
        return 'https://shop.example.test/product/' . (int) $this->id;
    }
}

class CMS
{
    public $id;

    private static $links = array();

    public function __construct($id = null, $idLang = null)
    {
        $this->id = (int) $id;
    }

    public static function reset()
    {
        self::$links = array();
    }

    public static function setLinks($idLang, $map)
    {
        self::$links[(int) $idLang] = $map;
    }

    public static function getLinks($idLang, $ids)
    {
        $idLang = (int) $idLang;
        if (!isset(self::$links[$idLang])) {
            return array();
        }
        $out = array();
        foreach ($ids as $id) {
            if (isset(self::$links[$idLang][(int) $id])) {
                $out[] = self::$links[$idLang][(int) $id];
            }
        }

        return $out;
    }
}

class CMSCategory
{
    public $id;
    public $name;

    public function __construct($id = null, $idLang = null)
    {
        $this->id = (int) $id;
        $this->name = 'CMS Category ' . $this->id;
    }

    public function getLink()
    {
        return 'https://shop.example.test/cms-category/' . (int) $this->id;
    }
}

class Manufacturer
{
    public $id;
    public $name;

    private static $list = array();

    public function __construct($id = null, $idLang = null)
    {
        $this->id = (int) $id;
        $this->name = 'Brand ' . $this->id;
    }

    public static function reset()
    {
        self::$list = array();
    }

    public static function setManufacturers(array $rows)
    {
        self::$list = $rows;
    }

    public static function getManufacturers()
    {
        return self::$list;
    }
}

class Supplier
{
    public $id;
    public $name;

    private static $list = array();

    public function __construct($id = null, $idLang = null)
    {
        $this->id = (int) $id;
        $this->name = 'Supplier ' . $this->id;
    }

    public static function reset()
    {
        self::$list = array();
    }

    public static function setSuppliers(array $rows)
    {
        self::$list = $rows;
    }

    public static function getSuppliers($active = false, $idLang = null)
    {
        return self::$list;
    }
}

class Customer
{
    private static $groups = array();

    public function __construct()
    {
    }

    public static function reset()
    {
        self::$groups = array();
    }

    public static function setGroupsStatic($customerId, array $groups)
    {
        self::$groups[(int) $customerId] = $groups;
    }

    public static function getGroupsStatic($customerId)
    {
        return isset(self::$groups[(int) $customerId]) ? self::$groups[(int) $customerId] : array();
    }

    public function getGroups()
    {
        return self::getGroupsStatic(Context::getContext()->customer->id);
    }

    public $id = 0;
}

class Dispatcher
{
    private static $controller = 'index';

    public static function reset()
    {
        self::$controller = 'index';
    }

    public static function setController($name)
    {
        self::$controller = $name;
    }

    public static function getInstance()
    {
        return new self();
    }

    public function getController()
    {
        return self::$controller;
    }
}

class HelperForm
{
    public function generateForm($forms)
    {
        return '<form>';
    }
}

class HelperList
{
    public function generateList($links, $fields)
    {
        return '<list>';
    }
}

class PrestaShopCollection
{
    public function where()
    {
        return $this;
    }
}
