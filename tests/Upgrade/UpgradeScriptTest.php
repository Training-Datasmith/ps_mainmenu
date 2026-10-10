<?php

class UpgradeScriptTest extends PsMainmenuIntegrationTestCase
{
    public function testUpgrade231ReplacesCategoryUpdateHook()
    {
        $this->module->registerHook('categoryUpdate');
        $this->assertTrue(upgrade_module_2_3_1($this->module));
        $hooks = Module::getRegisteredHooks('ps_mainmenu');
        $this->assertArrayNotHasKey('categoryUpdate', $hooks);
        $this->assertArrayHasKey('actionCategoryUpdate', $hooks);
    }

    public function testModuleAndUpgradeFilesExitWhenPsVersionIsUndefined()
    {
        $repo = dirname(dirname(__DIR__));
        $targets = array(
            $repo . '/ps_mainmenu.php',
            $repo . '/upgrade/upgrade-2.3.1.php',
            $repo . '/upgrade/upgrade-2.3.2.php',
            $repo . '/upgrade/upgrade-2.3.5.php',
        );
        foreach ($targets as $target) {
            $result = PsMainmenuDirectAccessRunner::run($target);
            PsMainmenuDirectAccessRunner::assertCleanChildOutput($result);
            $this->assertNotContains('Ps_MainMenu', $result['stdout']);
        }
    }

    public function testUpgrade232SetsUtf8mb4Collation()
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $db->execute("CREATE TABLE `{$p}linksmenutop` (
            `id_linksmenutop` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            `new_window` TINYINT(1) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $db->execute("CREATE TABLE `{$p}linksmenutop_lang` (
            `id_linksmenutop` INT(11) UNSIGNED NOT NULL,
            `id_lang` INT(11) UNSIGNED NOT NULL,
            `id_shop` INT(11) UNSIGNED NOT NULL,
            `label` VARCHAR(128) NOT NULL,
            `link` VARCHAR(128) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->assertTrue(upgrade_module_2_3_2($this->module));
        $charset = $db->getValue(
            "SELECT CCSA.character_set_name FROM information_schema.TABLES T
            JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
            ON CCSA.collation_name = T.table_collation
            WHERE T.table_schema = DATABASE() AND T.table_name = '{$p}linksmenutop'"
        );
        $this->assertSame('utf8mb4', $charset);
    }

    public function testUpgrade235InsertsModifierHookAndRegistersMetaSave()
    {
        $this->assertTrue(upgrade_module_2_3_5($this->module));
        $name = Db::getInstance()->getValue(
            'SELECT name FROM `' . _DB_PREFIX_ . 'hook` WHERE name = "actionMainMenuModifier"'
        );
        $this->assertSame('actionMainMenuModifier', $name);
        $this->assertArrayHasKey('actionMetaPageSave', Module::getRegisteredHooks('ps_mainmenu'));
    }
}

require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-2.3.1.php';
require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-2.3.2.php';
require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-2.3.5.php';
