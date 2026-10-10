<?php

class FrontTemplateTest extends PsMainmenuIntegrationTestCase
{
    public function testDesktopMenuRendersLinkTargetImageAndCurrentClass()
    {
        $menu = array(
            'type' => 'root',
            'children' => array(
                array(
                    'type' => 'link',
                    'page_identifier' => 'lnk-home',
                    'label' => 'Home',
                    'url' => 'https://shop.example.test/',
                    'open_in_new_window' => true,
                    'current' => true,
                    'depth' => 1,
                    'image_urls' => array('https://shop.example.test/img/cat.jpg'),
                    'children' => array(
                        array(
                            'type' => 'link',
                            'page_identifier' => 'lnk-child',
                            'label' => 'Child',
                            'url' => 'https://shop.example.test/child',
                            'open_in_new_window' => false,
                            'current' => false,
                            'depth' => 2,
                            'image_urls' => array(),
                            'children' => array(),
                        ),
                    ),
                ),
            ),
        );
        $html = PsMainmenuSmartyRenderer::render($menu);
        $this->assertContains('id="_desktop_top_menu"', $html);
        $this->assertContains('id="top-menu"', $html);
        $this->assertContains('data-depth="0"', $html);
        $this->assertContains('id="lnk-home"', $html);
        $this->assertContains('current', $html);
        $this->assertContains('target="_blank"', $html);
        $this->assertContains('https://shop.example.test/img/cat.jpg', $html);
        $this->assertContains('Child', $html);
        $this->assertContains('top_sub_menu_', $html);
    }

    public function testEmptyChildrenRenderNoList()
    {
        $menu = array('type' => 'root', 'children' => array());
        $html = PsMainmenuSmartyRenderer::render($menu);
        $this->assertContains('_desktop_top_menu', $html);
        $this->assertNotContains('<ul', $html);
    }
}
