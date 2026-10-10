<?php

class MenuTopLinksTest extends PsMainmenuIntegrationTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->installLinkTables();
    }

    public function testAddRejectsNonArrays()
    {
        $this->assertFalse(Ps_MenuTopLinks::add('x', array(1 => 'a'), 0, 1));
        $this->assertFalse(Ps_MenuTopLinks::add(array(1 => 'a'), 'x', 0, 1));
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'linksmenutop_lang` WHERE label = "Café"');
        $this->assertSame(array(), $rows);
    }

    public function testAddGetsRoundTripIncludingMultibyteLabel()
    {
        Ps_MenuTopLinks::add(
            array(1 => 'https://example.test/a?b=1&c=2', 2 => 'https://example.test/fr'),
            array(1 => "Café 😀", 2 => "O'Reilly"),
            1,
            1
        );
        $rows = Ps_MenuTopLinks::gets(1, null, 1);
        $this->assertCount(1, $rows);
        $this->assertSame("Café 😀", $rows[0]['label']);
        $this->assertSame('https://example.test/a?b=1&c=2', $rows[0]['link']);
        $this->assertSame(1, (int) $rows[0]['new_window']);
        $rowsFr = Ps_MenuTopLinks::gets(2, null, 1);
        $this->assertContains('Reilly', $rowsFr[0]['label']);
    }

    public function testGlobalShopZeroLinkIsVisibleToAShop()
    {
        Db::getInstance()->insert('linksmenutop', array('new_window' => 0, 'id_shop' => 0));
        $id = Db::getInstance()->Insert_ID();
        Db::getInstance()->insert('linksmenutop_lang', array(
            'id_linksmenutop' => $id,
            'id_lang' => 1,
            'id_shop' => 0,
            'label' => 'Global',
            'link' => 'https://global.test',
        ));
        $rows = Ps_MenuTopLinks::gets(1, null, 1);
        $this->assertCount(1, $rows);
    }

    public function testGetReturnsOnlyTheRequestedId()
    {
        Ps_MenuTopLinks::add(array(1 => 'https://a.test'), array(1 => 'A'), 0, 1);
        Ps_MenuTopLinks::add(array(1 => 'https://b.test'), array(1 => 'B'), 0, 1);
        $second = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        $rows = Ps_MenuTopLinks::get($second, 1, 1);
        $this->assertCount(1, $rows);
        $this->assertSame('B', $rows[0]['label']);
    }

    public function testGetLinkLangAndUpdateRewritesStoredText()
    {
        Ps_MenuTopLinks::add(array(1 => 'https://t.test'), array(1 => 'Tom & Jerry'), 0, 1);
        $id = (int) Db::getInstance()->getValue('SELECT MAX(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        $lang = Ps_MenuTopLinks::getLinkLang($id, 1);
        $this->assertSame('Tom &amp; Jerry', $lang['label'][1]);
        Ps_MenuTopLinks::update(
            array(1 => 'https://new.test'),
            array(1 => 'Updated'),
            1,
            1,
            $id
        );
        $row = Db::getInstance()->executeS(
            'SELECT label, link, new_window FROM `' . _DB_PREFIX_ . 'linksmenutop_lang` l
            JOIN `' . _DB_PREFIX_ . 'linksmenutop` t ON t.id_linksmenutop = l.id_linksmenutop
            WHERE l.id_linksmenutop = ' . $id . ' AND l.id_lang = 1'
        );
        $this->assertSame('Updated', $row[0]['label']);
        $this->assertSame('https://new.test', $row[0]['link']);
        $this->assertSame(1, (int) Db::getInstance()->getValue(
            'SELECT new_window FROM `' . _DB_PREFIX_ . 'linksmenutop` WHERE id_linksmenutop = ' . $id
        ));
    }

    public function testRemoveDeletesThatIdOnly()
    {
        Ps_MenuTopLinks::add(array(1 => 'https://a.test'), array(1 => 'A'), 0, 1);
        Ps_MenuTopLinks::add(array(1 => 'https://b.test'), array(1 => 'B'), 0, 1);
        $first = (int) Db::getInstance()->getValue('SELECT MIN(id_linksmenutop) FROM `' . _DB_PREFIX_ . 'linksmenutop`');
        $this->assertNotFalse(Ps_MenuTopLinks::remove($first, 1));
        $this->assertSame(0, (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'linksmenutop` WHERE id_linksmenutop = ' . $first
        ));
        $this->assertSame(1, (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'linksmenutop`'
        ));
    }

    public function testShopDuplicationPreservesLabelsLinksAndNewWindow()
    {
        Ps_MenuTopLinks::add(
            array(1 => 'https://example.test/summer', 2 => 'https://example.test/ete'),
            array(1 => "O'Reilly", 2 => 'Café'),
            1,
            1
        );
        $this->module->hookActionShopDataDuplication(array('old_id_shop' => 1, 'new_id_shop' => 2));
        $newId = (int) Db::getInstance()->getValue(
            'SELECT id_linksmenutop FROM `' . _DB_PREFIX_ . 'linksmenutop` WHERE id_shop = 2'
        );
        $this->assertGreaterThan(0, $newId);
        $label = Db::getInstance()->getValue(
            'SELECT label FROM `' . _DB_PREFIX_ . 'linksmenutop_lang`
            WHERE id_linksmenutop = ' . $newId . ' AND id_lang = 1 AND id_shop = 2'
        );
        $link = Db::getInstance()->getValue(
            'SELECT link FROM `' . _DB_PREFIX_ . 'linksmenutop_lang`
            WHERE id_linksmenutop = ' . $newId . ' AND id_lang = 1 AND id_shop = 2'
        );
        $this->assertContains('Reilly', $label);
        $this->assertSame('https://example.test/summer', $link);
        $this->assertSame(1, (int) Db::getInstance()->getValue(
            'SELECT new_window FROM `' . _DB_PREFIX_ . 'linksmenutop` WHERE id_shop = 2'
        ));
    }
}
