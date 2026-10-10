<?php

class CacheAndWidgetTest extends PsMainmenuIntegrationTestCase
{
    public function testCacheDirectoryAppendsCustomerGroupIds()
    {
        $dir = $this->module->exposeGetCacheDirectory();
        $this->assertTrue(is_dir($dir));
        $this->assertSame(rtrim(_PS_CACHE_DIR_, '/') . '/ps_mainmenu', $dir);
        $customer = Context::getContext()->customer;
        $customer->id = 1;
        Customer::setGroupsStatic(1, array(3, 5));
        $dir2 = $this->module->exposeGetCacheDirectory();
        $this->assertContains('/3_5', $dir2);
    }

    public function testClearMenuCacheDeletesJsonOnly()
    {
        $base = _PS_CACHE_DIR_ . 'ps_mainmenu/';
        mkdir($base . '3_5', 0775, true);
        file_put_contents($base . 'a.json', '{}');
        file_put_contents($base . '3_5/b.json', '{}');
        file_put_contents($base . 'keep.txt', 'x');
        file_put_contents($base . '3_5/note.txt', 'y');
        $this->module->exposeClearMenuCache();
        $this->assertFileExists($base . 'keep.txt');
        $this->assertFileExists($base . '3_5/note.txt');
        $this->assertFileNotExists($base . 'a.json');
        $this->assertFileNotExists($base . '3_5/b.json');
    }

    public function testWidgetReusesAValidCacheFile()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'First', 'children' => array()),
        ), null);
        $first = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('First', $first['children'][0]['label']);
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Second', 'children' => array()),
        ), null);
        $second = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('First', $second['children'][0]['label']);
        $this->clearCacheDir();
        $third = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('Second', $third['children'][0]['label']);
    }

    public function testWidgetRebuildsInvalidJson()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Fresh', 'children' => array()),
        ), null);
        $dir = $this->module->exposeGetCacheDirectory();
        $file = $dir . '/' . Ps_MainMenu::MENU_JSON_CACHE_KEY . '_1_1.json';
        file_put_contents($file, '{');
        $menu = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('Fresh', $menu['children'][0]['label']);
        $decoded = json_decode(file_get_contents($file), true);
        $this->assertTrue(is_array($decoded));
    }

    public function testWidgetCacheIsSplitByLanguageAndGroup()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Context::getContext()->language->id = 1;
        Context::getContext()->customer->id = 1;
        Customer::setGroupsStatic(1, array(1));
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Alpha', 'children' => array()),
        ), null);
        $this->module->getWidgetVariables('displayTop', array());
        Context::getContext()->language->id = 2;
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Beta', 'children' => array()),
        ), null);
        $beta = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('Beta', $beta['children'][0]['label']);
        Context::getContext()->language->id = 1;
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Gamma', 'children' => array()),
        ), null);
        $again = $this->module->getWidgetVariables('displayTop', array());
        $this->assertSame('Alpha', $again['children'][0]['label']);
    }

    public function testRenderWidgetLetsTheModifierHookChangeTheLabel()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Before', 'children' => array()),
        ), null);
        Hook::addListener('actionMainMenuModifier', function ($params) {
            $params['menu']['children'][0]['label'] = 'Hooked';
        });
        $html = $this->module->renderWidget('displayTop', array());
        $this->assertContains('Hooked', $html);
        $this->assertNotContains('Before', $html);
    }

    public function testObjectHooksClearTheMenuCache()
    {
        $hooks = array(
            'hookActionObjectCategoryAddAfter',
            'hookActionObjectCategoryUpdateAfter',
            'hookActionObjectCategoryDeleteAfter',
            'hookActionObjectCmsUpdateAfter',
            'hookActionObjectCmsDeleteAfter',
            'hookActionObjectCmsAddAfter',
            'hookActionObjectSupplierUpdateAfter',
            'hookActionObjectSupplierDeleteAfter',
            'hookActionObjectSupplierAddAfter',
            'hookActionObjectManufacturerUpdateAfter',
            'hookActionObjectManufacturerDeleteAfter',
            'hookActionObjectManufacturerAddAfter',
            'hookActionObjectProductUpdateAfter',
            'hookActionObjectProductDeleteAfter',
            'hookActionObjectProductAddAfter',
            'hookActionCategoryUpdate',
            'hookActionMetaPageSave',
        );
        foreach ($hooks as $hook) {
            $path = _PS_CACHE_DIR_ . 'ps_mainmenu/x.json';
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, '{}');
            call_user_func(array($this->module, $hook), array());
            $this->assertFileNotExists($path);
        }
    }

    public function testCurrentFlagsFollowTheControllerAndTheRequestUri()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Category::setNestedCategories(3, array(
            array('id_category' => 3, 'level_depth' => 2, 'active' => 1, 'name' => 'Cat', 'children' => array()),
        ), null);
        $this->module->getWidgetVariables('displayTop', array());
        Dispatcher::setController('category');
        Tools::setValue('id_category', 3);
        $menu = $this->module->getWidgetVariables('displayTop', array());
        $cat = $this->findChildByIdentifier($menu, 'category-3');
        $this->assertTrue($cat['current']);
        $this->assertCount(1, $menu['children']);
        Dispatcher::setController('cart');
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['REQUEST_URI'] = '/en/page';
        $this->assertSame('https://shop.example/en/page', $this->module->exposeCurrentPageIdentifier());
        unset($_SERVER['REQUEST_SCHEME']);
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['REQUEST_URI'] = '/en/page';
        $this->assertSame('http://shop.example/en/page', $this->module->exposeCurrentPageIdentifier());
    }
}
