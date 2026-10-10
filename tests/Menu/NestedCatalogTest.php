<?php

class NestedCatalogTest extends PsMainmenuIntegrationTestCase
{
    public function testCustomNestedCategoriesBuildATreeForTheRequestedShopAndLanguage()
    {
        $this->seedCategory(10, 0, 1, 1, 'Root EN', 1, 1, 0);
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'category_lang` (`id_category`,`id_lang`,`id_shop`,`name`)
            VALUES (10,2,1,\'Root FR\')'
        );
        $this->seedCategory(11, 10, 2, 2, 'Child FR', 1);
        $this->seedCategory(20, 0, 1, 2, 'Other shop', 2);
        $tree = $this->module->customGetNestedCategories(1, null, 2, false);
        $this->assertArrayHasKey(10, $tree);
        $this->assertSame('Root FR', $tree[10]['name']);
        $this->assertArrayHasKey(11, $tree[10]['children']);
        $this->assertArrayNotHasKey(20, $tree);
    }

    public function testCustomNestedCategoriesServeTheCachedTree()
    {
        $this->seedCategory(10, 0, 1, 1, 'Cached', 1);
        $first = $this->module->customGetNestedCategories(1, null, 1, false);
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'category` WHERE id_category = 10');
        $second = $this->module->customGetNestedCategories(1, null, 1, false);
        $this->assertSame($first, $second);
        Cache::reset();
        $third = $this->module->customGetNestedCategories(1, null, 1, false);
        $this->assertSame(array(), $third);
    }

    public function testCustomNestedCategoriesHonorTheActiveFlag()
    {
        $this->seedCategory(10, 0, 1, 1, 'Active', 1, 1, 0);
        $this->seedCategory(11, 0, 1, 1, 'Inactive', 1, 0, 0);
        $this->seedCategory(12, 0, 1, 1, 'Root inactive', 1, 0, 1);
        $active = $this->module->customGetNestedCategories(1, null, 1, true);
        $this->assertArrayHasKey(10, $active);
        $this->assertArrayHasKey(12, $active);
        $this->assertArrayNotHasKey(11, $active);
        $all = $this->module->customGetNestedCategories(1, null, 1, false);
        $this->assertArrayHasKey(11, $all);
    }

    public function testCustomNestedCategoriesFilterByCustomerGroup()
    {
        Group::setFeatureActive(true);
        $this->seedCategory(10, 0, 1, 1, 'G3', 1);
        $this->seedCategory(11, 0, 1, 1, 'G4', 1);
        Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'category_group` VALUES (10, 3), (11, 4)');
        $only3 = $this->module->customGetNestedCategories(1, null, 1, false, array(3));
        $this->assertArrayHasKey(10, $only3);
        $this->assertArrayNotHasKey(11, $only3);
        Group::setFeatureActive(false);
        $both = $this->module->customGetNestedCategories(1, null, 1, false, array(3));
        $this->assertArrayHasKey(10, $both);
        $this->assertArrayHasKey(11, $both);
    }

    public function testCustomNestedCategoriesExitOnInvalidArguments()
    {
        $repo = dirname(dirname(__DIR__));
        $marker = tempnam(sys_get_temp_dir(), 'psmm_nested_marker_');
        putenv('PS_MAINMENU_MARKER_FILE=' . $marker);
        foreach (array('abc', 'nope') as $i => $arg) {
            $script = tempnam(sys_get_temp_dir(), 'psmm_inv_');
            $content = $i === 0
                ? '<?php if (!defined("_PS_VERSION_")) { define("_PS_VERSION_","1.7.8.7"); } require "' . $repo . '/tests/bootstrap.php"; $m=new PsMainMenuHarness(); $m->customGetNestedCategories(1,"abc",1,false);'
                : '<?php if (!defined("_PS_VERSION_")) { define("_PS_VERSION_","1.7.8.7"); } require "' . $repo . '/tests/bootstrap.php"; $m=new PsMainMenuHarness(); $m->customGetNestedCategories(1,null,1,"nope");';
            file_put_contents($script, $content);
            if (file_exists($marker)) {
                unlink($marker);
            }
            $result = PsMainmenuDirectAccessRunner::run($script);
            PsMainmenuDirectAccessRunner::assertCleanChildOutput($result);
            $this->assertFalse(file_exists($marker));
            unlink($script);
        }
        putenv('PS_MAINMENU_MARKER_FILE');
        if (file_exists($marker)) {
            unlink($marker);
        }
    }

    public function testCmsMenusComeFromCmsTables()
    {
        $this->seedCmsCategory(5, 1, 'Parent');
        $this->seedCmsCategory(6, 5, 'Child');
        $this->seedCmsPage(7, 5, 'Active page', 1);
        $this->seedCmsPage(8, 5, 'Inactive', 0);
        $node = $this->module->exposeGenerateCMSCategoriesMenu(5, 1);
        $this->assertSame('cms-category-5', $node['page_identifier']);
        $ids = $this->collectIdentifiers($node);
        $this->assertContains('cms-category-6', $ids);
        $this->assertContains('cms-page-7', $ids);
        $this->assertNotContains('cms-page-8', $ids);
    }

    public function testCmsOptionsSkipSelectedPagesAndCategories()
    {
        $this->seedCmsCategory(5, 1, 'Parent');
        $this->seedCmsCategory(6, 5, 'Child');
        $this->seedCmsPage(7, 5, 'Page', 1);
        $this->seedCmsPage(9, 6, 'Other', 1);
        $html = $this->module->exposeGetCMSOptions(1, 1, 1, array('CMS7', 'CMS_CAT5'), 1);
        $this->assertNotContains('value="CMS7"', $html);
        $this->assertNotContains('value="CMS_CAT5"', $html);
        $this->assertContains('value="CMS_CAT6"', $html);
        $this->assertContains('value="CMS9"', $html);
    }

    private function seedCategory($id, $parent, $depth, $lang, $name, $shop, $active = 1, $isRoot = 0)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'category` (`id_category`,`id_parent`,`level_depth`,`active`,`is_root_category`,`id_shop`)
            VALUES (' . (int) $id . ',' . (int) $parent . ',' . (int) $depth . ',' . (int) $active . ',' . (int) $isRoot . ',' . (int) $shop . ')'
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'category_lang` (`id_category`,`id_lang`,`id_shop`,`name`)
            VALUES (' . (int) $id . ',' . (int) $lang . ',' . (int) $shop . ",'" . pSQL($name) . "')"
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'category_shop` (`id_category`,`id_shop`,`position`) VALUES (' . (int) $id . ',' . (int) $shop . ',0)'
        );
    }

    private function seedCmsCategory($id, $parent, $name)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms_category` (`id_cms_category`,`id_parent`,`level_depth`,`active`,`position`)
            VALUES (' . (int) $id . ',' . (int) $parent . ',1,1,0)'
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms_category_lang` (`id_cms_category`,`id_lang`,`id_shop`,`name`,`link_rewrite`)
            VALUES (' . (int) $id . ',1,1,"' . pSQL($name) . '","' . pSQL(Tools::str2url($name)) . '")'
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms_category_shop` (`id_cms_category`,`id_shop`) VALUES (' . (int) $id . ',1)'
        );
    }

    private function seedCmsPage($id, $cat, $title, $active)
    {
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms` (`id_cms`,`id_cms_category`,`active`,`position`) VALUES (' . (int) $id . ',' . (int) $cat . ',' . (int) $active . ',0)'
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms_lang` (`id_cms`,`id_lang`,`id_shop`,`meta_title`,`link_rewrite`)
            VALUES (' . (int) $id . ',1,1,"' . pSQL($title) . '","' . pSQL(Tools::str2url($title)) . '")'
        );
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cms_shop` (`id_cms`,`id_shop`) VALUES (' . (int) $id . ',1)'
        );
    }
}
