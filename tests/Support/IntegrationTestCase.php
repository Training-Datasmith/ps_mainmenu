<?php

abstract class PsMainmenuIntegrationTestCase extends PHPUnit_Framework_TestCase
{
    /** @var PsMainMenuHarness */
    protected $module;

    protected function setUp()
    {
        Db::resetInstance();
        PsMainmenuSchema::truncateAll();
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'linksmenutop_lang`');
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'linksmenutop`');
        Configuration::reset();
        Shop::reset();
        Tools::reset();
        Cache::reset();
        Hook::reset();
        Module::resetState();
        Group::reset();
        Category::reset();
        CMS::reset();
        Manufacturer::reset();
        Supplier::reset();
        Customer::reset();
        Dispatcher::reset();
        Context::reset();

        Shop::seedShop(1, 'Beta');
        Shop::setContext(Shop::CONTEXT_SHOP, 1);

        $this->clearCacheDir();

        $this->module = new PsMainMenuHarness();
    }

    protected function clearCacheDir()
    {
        $this->removeDir(_PS_CACHE_DIR_);
        mkdir(_PS_CACHE_DIR_, 0775, true);
    }

    protected function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . $entry;
            if (is_dir($path)) {
                $this->removeDir($path . '/');
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    protected function installLinkTables()
    {
        $module = new Ps_MainMenu();
        $this->assertTrue($module->installDb());
    }

    protected function findChildByIdentifier(array $node, $identifier)
    {
        if (isset($node['page_identifier']) && $node['page_identifier'] === $identifier) {
            return $node;
        }
        if (!isset($node['children'])) {
            return null;
        }
        foreach ($node['children'] as $child) {
            $found = $this->findChildByIdentifier($child, $identifier);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    protected function collectIdentifiers(array $node)
    {
        $ids = array();
        if (isset($node['page_identifier']) && $node['page_identifier'] !== null) {
            $ids[] = $node['page_identifier'];
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $ids = array_merge($ids, $this->collectIdentifiers($child));
            }
        }

        return $ids;
    }
}
