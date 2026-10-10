<?php

class GetContentTest extends PsMainmenuIntegrationTestCase
{
    public function testSingleShopSaveWritesCommaSeparatedItems()
    {
        $this->module->install();
        Tools::setSubmit('submitBlocktopmenu');
        Tools::setValue('items', array('CAT3', 'PRD4'));
        $this->module->getContent();
        $this->assertSame('CAT3,PRD4', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
    }

    public function testSingleShopSaveWritesEmptyStringWhenNothingSelected()
    {
        $this->module->install();
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3', false, 1, 1);
        Tools::setSubmit('submitBlocktopmenu');
        Tools::setValue('items', array());
        $this->module->getContent();
        $this->assertSame('', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
    }

    public function testAddingALinkStoresItAndConfirms()
    {
        $this->module->install();
        Tools::setSubmit('submitBlocktopmenuLinks');
        Tools::setValue('link_1', 'https://added.test');
        Tools::setValue('label_1', 'Added');
        Tools::setValue('link_2', 'https://added-fr.test');
        Tools::setValue('label_2', 'Ajout');
        Tools::setValue('new_window', 1);
        $this->module->getContent();
        $row = Db::getInstance()->executeS(
            'SELECT label, link FROM `' . _DB_PREFIX_ . 'linksmenutop_lang` WHERE id_lang = 1'
        );
        $this->assertSame('Added', $row[0]['label']);
        $this->assertSame('https://added.test', $row[0]['link']);
    }

    public function testDeletingLinkOneDoesNotCorruptLinkTen()
    {
        $this->module->install();
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3,LNK1,LNK10,LNK2', false, 1, 1);
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'linksmenutop` (`id_linksmenutop`,`id_shop`,`new_window`) VALUES (1,1,0),(10,1,0)'
        );
        Tools::setSubmit('deletelinksmenutop');
        Tools::setValue('id_linksmenutop', 1);
        $this->module->getContent();
        $this->assertSame('CAT3,LNK10,LNK2', Configuration::get('MOD_BLOCKTOPMENU_ITEMS'));
    }

    public function testEditingALinkUpdatesLabelUrlAndWindow()
    {
        $this->module->install();
        Ps_MenuTopLinks::add(array(1 => 'https://old.test'), array(1 => 'Old'), 0, 1);
        $id = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        Tools::setSubmit('updatelinksmenutop');
        Tools::setSubmit('updatelink');
        Tools::setValue('id_linksmenutop', $id);
        Tools::setValue('link_1', 'https://new.test');
        Tools::setValue('label_1', 'New');
        Tools::setValue('new_window', 1);
        $this->module->getContent();
        $this->assertSame('New', Db::getInstance()->getValue(
            'SELECT label FROM `' . _DB_PREFIX_ . 'linksmenutop_lang` WHERE id_linksmenutop = ' . $id
        ));
        $this->assertSame(1, (int) Db::getInstance()->getValue(
            'SELECT new_window FROM `' . _DB_PREFIX_ . 'linksmenutop` WHERE id_linksmenutop = ' . $id
        ));
    }

    public function testAllShopsContextWarnsAndDoesNotSaveItems()
    {
        $this->module->install();
        Shop::seedShop(2, 'Second');
        Shop::setFeatureActive(true);
        Shop::setContext(Shop::CONTEXT_ALL);
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT1', false, 1, 1);
        Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT2', false, 1, 2);
        Tools::setSubmit('submitBlocktopmenu');
        Tools::setValue('items', array('CAT9'));
        $html = $this->module->getContent();
        $this->assertSame('CAT1', Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, 1, 1));
        $this->assertSame('CAT2', Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, 1, 2));
        $this->assertContains('alert alert-warning', $html);
        $this->assertContains('alert alert-info', $html);
    }
}
