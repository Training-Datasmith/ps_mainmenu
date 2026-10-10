<?php

class InstallResetTest extends PsMainmenuIntegrationTestCase
{
    public function testConstructorMatchesPackageIdentity()
    {
        $xml = simplexml_load_file(dirname(dirname(__DIR__)) . '/config.xml');
        $this->assertSame('ps_mainmenu', $this->module->name);
        $this->assertSame('2.3.6', $this->module->version);
        $this->assertSame((string) $xml->version, $this->module->version);
        $this->assertSame('1.7.1.0', $this->module->ps_versions_compliancy['min']);
    }

    public function testInstallRegistersHooksCreatesSchemaAndDefaultItems()
    {
        $this->assertTrue($this->module->install());
        $hooks = Module::getRegisteredHooks('ps_mainmenu');
        $this->assertArrayHasKey('displayTop', $hooks);
        $this->assertArrayHasKey('actionCategoryUpdate', $hooks);
        $this->assertNotNull(Db::getInstance()->getValue('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'linksmenutop"'));
        $this->assertSame('CAT3,CAT6,CAT9', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
        $hookName = Db::getInstance()->getValue(
            'SELECT name FROM `' . _DB_PREFIX_ . 'hook` WHERE name = "actionMainMenuModifier"'
        );
        $this->assertSame('actionMainMenuModifier', $hookName);
    }

    public function testInstallWhenSixteenModuleExistsUninstallsItAndSkipsSchema()
    {
        $legacy = new BlocktopmenuStub();
        Module::registerInstance('blocktopmenu', $legacy);
        Module::markInstalled('blocktopmenu');
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'module` (`id_module`, `name`) VALUES (99, "blocktopmenu")'
        );
        Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_SEARCH', '1');

        $this->assertTrue($this->module->install());
        $this->assertNull(Configuration::get('MOD_BLOCKTOPMENU_SEARCH'));
        $this->assertFalse(Module::isInstalled('blocktopmenu'));
        $this->assertSame(0, (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = "' . _DB_PREFIX_ . 'linksmenutop"'
        ));
    }

    public function testInstallReturnsFalseAndSkipsSchemaWhenHookRegistrationFails()
    {
        Hook::setFailNextRegistration(true);
        $this->assertFalse($this->module->install());
        $this->assertSame(0, (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = "' . _DB_PREFIX_ . 'linksmenutop"'
        ));
    }

    public function testUninstallDropsSchemaAndConfiguration()
    {
        $this->module->install();
        mkdir(_PS_CACHE_DIR_ . 'ps_mainmenu', 0775, true);
        file_put_contents(_PS_CACHE_DIR_ . 'ps_mainmenu/test.json', '{}');
        $this->assertTrue($this->module->uninstall());
        $this->assertSame(0, (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = "' . _DB_PREFIX_ . 'linksmenutop"'
        ));
        $this->assertNull(Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
        $this->assertFalse(file_exists(_PS_CACHE_DIR_ . 'ps_mainmenu/test.json'));
    }

    public function testUninstallFalseKeepsSchemaAndConfiguration()
    {
        $this->module->install();
        Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT9');
        $this->assertTrue($this->module->uninstall(false));
        $this->assertNotNull(Db::getInstance()->getValue('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'linksmenutop"'));
        $this->assertSame('CAT9', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
    }

    public function testResetKeepsCustomLinksAndItems()
    {
        $this->module->install();
        Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT9');
        Ps_MenuTopLinks::add(array(1 => 'https://x.test'), array(1 => 'Keep'), 0, 1);
        $this->assertTrue($this->module->reset());
        $this->assertSame('CAT9', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
        $rows = Db::getInstance()->executeS('SELECT label FROM `' . _DB_PREFIX_ . 'linksmenutop_lang`');
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('displayTop', Module::getRegisteredHooks('ps_mainmenu'));
    }
}

class BlocktopmenuStub extends Module
{
    public $name = 'blocktopmenu';

    public function uninstall()
    {
        return parent::uninstall();
    }
}
