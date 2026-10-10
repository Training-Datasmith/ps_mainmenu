<?php

class MenuBuilderTest extends PsMainmenuIntegrationTestCase
{
    public function testMakeNodeMergesDefaults()
    {
        $node = $this->module->exposeMakeNode(array('label' => 'X'));
        $this->assertSame('X', $node['label']);
        $this->assertSame('', $node['type']);
        $this->assertSame(array(), $node['children']);
        $this->assertFalse($node['open_in_new_window']);
    }

    public function testMapTreeWritesIncreasingDepth()
    {
        $tree = $this->module->exposeMapTree(function ($node, $depth) {
            $node['label'] = 'd' . $depth;

            return $node;
        }, array(
            'children' => array(
                array('children' => array(array('children' => array()))),
            ),
        ));
        $this->assertSame('d0', $tree['label']);
        $this->assertSame('d1', $tree['children'][0]['label']);
        $this->assertSame('d2', $tree['children'][0]['children'][0]['label']);
    }

    public function testPostedItemsOverrideConfiguration()
    {
        Tools::setValue('items', array('CAT9'));
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        $this->assertSame(array('CAT9'), $this->module->exposeGetMenuItems());
    }

    public function testEmptyPostedItemsFallBackToSingleShopConfiguration()
    {
        Tools::setValue('items', array());
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3,CAT6', false, 1, 1);
        $this->assertSame(array('CAT3', 'CAT6'), $this->module->exposeGetMenuItems());
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', '', false, 1, 1);
        $this->assertSame(array(), $this->module->exposeGetMenuItems());
    }

    public function testMultiShopItemsStaySeparatedInNameOrder()
    {
        Shop::seedShop(2, 'Alpha');
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT2', false, 1, 2);
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT1', false, 1, 1);
        Shop::setContext(Shop::CONTEXT_ALL);
        $items = $this->module->exposeGetMenuItems();
        $filtered = array_values(array_filter($items));
        sort($filtered);
        $this->assertSame(array('CAT1', 'CAT2'), $filtered);
    }

    public function testGenerateCategoriesMenuSkipsInactiveAndUnauthorized()
    {
        Context::getContext()->customer->id = 1;
        Category::setNestedCategories(3, array(
            array(
                'id_category' => 5,
                'level_depth' => 2,
                'active' => 0,
                'name' => 'Inactive',
                'children' => array(),
            ),
            array(
                'id_category' => 6,
                'level_depth' => 2,
                'active' => 1,
                'name' => 'Denied',
                'children' => array(),
            ),
            array(
                'id_category' => 7,
                'level_depth' => 2,
                'active' => 1,
                'name' => 'Allowed',
                'children' => array(),
            ),
        ), array(3, 5));
        Category::setAccess(6, false);
        Category::setAccess(7, true);
        $nodes = $this->module->exposeGenerateCategoriesMenu(Category::getNestedCategories(3, 1, false, array(3, 5)));
        $ids = array_map(function ($n) {
            return $n['page_identifier'];
        }, $nodes);
        $this->assertContains('category-7', $ids);
        $this->assertNotContains('category-5', $ids);
        $this->assertNotContains('category-6', $ids);
    }

    public function testRootCategoryUsesIndexUrlWithoutAccessCheck()
    {
        Category::setAccess(2, false);
        $nodes = $this->module->exposeGenerateCategoriesMenu(array(
            array(
                'id_category' => 2,
                'level_depth' => 1,
                'active' => 1,
                'name' => 'Root',
                'children' => array(),
            ),
        ));
        $this->assertCount(1, $nodes);
        $this->assertSame('https://shop.example.test/index', $nodes[0]['url']);
    }

    public function testProductCmsBrandSupplierAndShopLeaves()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'PRD5,CMS3,MAN4,SUP6,SHOP1', false, 1, 1);
        CMS::setLinks(1, array(3 => array('meta_title' => 'About', 'link' => 'https://shop.example.test/cms/3')));
        Manufacturer::setManufacturers(array());
        Supplier::setSuppliers(array());
        $menu = $this->module->exposeMakeMenu();
        $this->assertNotNull($this->findChildByIdentifier($menu, 'product-5'));
        $this->assertNotNull($this->findChildByIdentifier($menu, 'cms-page-3'));
        $this->assertNotNull($this->findChildByIdentifier($menu, 'manufacturer-4'));
        $this->assertNotNull($this->findChildByIdentifier($menu, 'supplier-6'));
        $this->assertNotNull($this->findChildByIdentifier($menu, 'shop-1'));
    }

    public function testAllBrandsAndAllSuppliersIncludeChildren()
    {
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'ALLMAN0,ALLSUP0', false, 1, 1);
        Manufacturer::setManufacturers(array(array('id_manufacturer' => 4, 'name' => 'Brand 4')));
        Supplier::setSuppliers(array(array('id_supplier' => 6, 'name' => 'Supplier 6')));
        $menu = $this->module->exposeMakeMenu();
        $brands = $this->findChildByIdentifier($menu, 'manufacturers');
        $suppliers = $this->findChildByIdentifier($menu, 'suppliers');
        $this->assertNotNull($brands);
        $this->assertNotNull($suppliers);
        $this->assertNotNull($this->findChildByIdentifier($brands, 'manufacturer-4'));
        $this->assertNotNull($this->findChildByIdentifier($suppliers, 'supplier-6'));
        $this->assertSame('https://shop.example.test/manufacturer', $brands['url']);
        $this->assertSame('https://shop.example.test/supplier', $suppliers['url']);
    }

    public function testEmptyLinkLabelFallsBackToDefaultLanguage()
    {
        $this->installLinkTables();
        Ps_MenuTopLinks::add(array(1 => 'https://summer.test', 2 => ''), array(1 => 'Summer Sale', 2 => ''), 1, 1);
        $id = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        Configuration::updateGlobalValue('PS_LANG_DEFAULT', '1');
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'LNK' . $id, false, 1, 1);
        $menu = $this->module->exposeMakeMenu();
        $node = $this->findChildByIdentifier($menu, 'lnk-summer-sale');
        $this->assertNotNull($node);
        $this->assertSame('Summer Sale', $node['label']);
        $this->assertSame(1, (int) $node['open_in_new_window']);
    }

    public function testKnownThumbnailFileIsAttached()
    {
        file_put_contents(_PS_CAT_IMG_DIR_ . '7-1_thumb.jpg', 'x');
        $nodes = $this->module->exposeGenerateCategoriesMenu(array(
            array('id_category' => 7, 'level_depth' => 2, 'active' => 1, 'name' => 'Seven', 'children' => array()),
        ));
        $this->assertNotEmpty($nodes[0]['image_urls']);
        $this->assertContains('https://shop.example.test/themes/classic/assets/img/c/7-1_thumb.jpg', $nodes[0]['image_urls']);
    }

    public function testGetCacheIdUsesWhitelistedControllersOnly()
    {
        $this->module->setPageName('product');
        $_GET['id_product'] = 9;
        $this->assertSame('ps_mainmenu|product|9', $this->module->exposeGetCacheId());
        $this->module->setPageName('cart');
        $this->assertSame('ps_mainmenu|index', $this->module->exposeGetCacheId());
        $this->assertNotContains('cart', $this->module->exposeGetCacheId());
    }

    public function testMakeMenuOptionRendersEachSelectedType()
    {
        $this->installLinkTables();
        Ps_MenuTopLinks::add(array(1 => 'https://lnk.test'), array(1 => 'My Link'), 0, 1);
        $id = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3,LNK' . $id, false, 1, 1);
        $html = $this->module->exposeMakeMenuOption();
        $this->assertContains('value="CAT3"', $html);
        $this->assertContains('value="LNK' . $id . '"', $html);
    }

    public function testRenderChoicesOmitsSelectedSuppliersBrandsAndLinks()
    {
        $this->installLinkTables();
        Supplier::setSuppliers(array(
            array('id_supplier' => 1, 'name' => 'Sup One'),
            array('id_supplier' => 2, 'name' => 'Sup Two'),
        ));
        Manufacturer::setManufacturers(array(
            array('id_manufacturer' => 2, 'name' => 'Man Two'),
        ));
        Ps_MenuTopLinks::add(array(1 => 'https://a.test'), array(1 => 'A'), 0, 1);
        Ps_MenuTopLinks::add(array(1 => 'https://b.test'), array(1 => 'B'), 0, 1);
        $linkId = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'SUP1,MAN2,LNK' . $linkId, false, 1, 1);
        $html = $this->module->renderChoicesSelect();
        $this->assertNotContains('value="SUP1"', $html);
        $this->assertNotContains('value="MAN2"', $html);
        $this->assertNotContains('value="LNK' . $linkId . '"', $html);
        $this->assertContains('value="SUP2"', $html);
        $this->assertContains('value="ALLSUP0"', $html);
        $this->assertContains('value="ALLMAN0"', $html);
        $this->assertContains('value="PRODUCT"', $html);
    }
}
