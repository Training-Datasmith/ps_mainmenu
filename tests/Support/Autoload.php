<?php

class PsMainmenuTestAutoload
{
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'load'));
    }

    public static function load($class)
    {
        $map = array(
            'Db' => 'Db.php',
            'Configuration' => 'Configuration.php',
            'Context' => 'Context.php',
            'Shop' => 'Shop.php',
            'Tools' => 'Tools.php',
            'Language' => 'Language.php',
            'Cache' => 'Cache.php',
            'Group' => 'Group.php',
            'Validate' => 'Validate.php',
            'Module' => 'Module.php',
            'Hook' => 'Hook.php',
            'HelperForm' => 'HelperForm.php',
            'HelperList' => 'HelperList.php',
            'Customer' => 'Customer.php',
            'Dispatcher' => 'Dispatcher.php',
            'Category' => 'CatalogStubs.php',
            'Product' => 'CatalogStubs.php',
            'CMS' => 'CatalogStubs.php',
            'CMSCategory' => 'CatalogStubs.php',
            'Manufacturer' => 'CatalogStubs.php',
            'Supplier' => 'CatalogStubs.php',
            'Customer' => 'CatalogStubs.php',
            'Dispatcher' => 'CatalogStubs.php',
            'HelperForm' => 'CatalogStubs.php',
            'HelperList' => 'CatalogStubs.php',
            'PrestaShopCollection' => 'CatalogStubs.php',
            'PrestaShopDatabaseException' => 'PrestaShopDatabaseException.php',
            'PsMainMenuHarness' => 'PsMainMenuHarness.php',
            'PsMainmenuIntegrationTestCase' => 'IntegrationTestCase.php',
            'PsMainmenuDirectAccessRunner' => 'DirectAccessRunner.php',
            'PsMainmenuSmartyRenderer' => 'SmartyRenderer.php',
        );

        if (!isset($map[$class])) {
            return;
        }

        require_once dirname(__FILE__) . '/' . $map[$class];
    }
}
