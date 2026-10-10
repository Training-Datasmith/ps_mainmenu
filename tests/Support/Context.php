<?php

class Context
{
    /** @var Context */
    private static $instance;

    public $shop;
    public $language;
    public $customer;
    public $link;
    public $controller;
    public $smarty;

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = new self();
        self::$instance->shop = new stdClass();
        self::$instance->shop->id = 1;
        self::$instance->language = new stdClass();
        self::$instance->language->id = 1;
        self::$instance->customer = new Customer();
        self::$instance->link = new PsMainmenuLinkStub();
        self::$instance->controller = new PsMainmenuControllerStub();
        self::$instance->smarty = new stdClass();
    }
}

class PsMainmenuLinkStub
{
    public function getCMSLink($cms, $alias = null, $ssl = null, $idLang = null)
    {
        $id = is_object($cms) ? $cms->id : 0;

        return 'https://shop.example.test/cms/' . (int) $id;
    }

    public function getManufacturerLink($manufacturer, $alias = null, $idLang = null)
    {
        $id = is_object($manufacturer) ? $manufacturer->id : 0;

        return 'https://shop.example.test/brand/' . (int) $id;
    }

    public function getSupplierLink($supplier, $alias = null, $idLang = null)
    {
        $id = is_object($supplier) ? $supplier->id : 0;

        return 'https://shop.example.test/supplier/' . (int) $id;
    }

    public function getPageLink($page)
    {
        return 'https://shop.example.test/' . $page;
    }

    public function getMediaLink($path)
    {
        return 'https://shop.example.test' . $path;
    }

    public function getAdminLink($controller, $withToken = false, $params = array(), $params2 = array())
    {
        return 'https://shop.example.test/admin';
    }
}

class PsMainmenuControllerStub
{
    public function getLanguages()
    {
        return Language::getLanguages(false);
    }
}
