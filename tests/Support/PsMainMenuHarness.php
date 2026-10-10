<?php

class PsMainMenuHarness extends Ps_MainMenu
{
    public function exposeMakeNode(array $fields)
    {
        return $this->makeNode($fields);
    }

    public function exposeMapTree(callable $cb, array $node, $depth = 0)
    {
        return $this->mapTree($cb, $node, $depth);
    }

    public function exposeGetMenuItems()
    {
        return $this->getMenuItems();
    }

    public function exposeMakeMenu()
    {
        return $this->makeMenu();
    }

    public function exposeGenerateCategoriesMenu($categories, $isChildren = 0)
    {
        return $this->generateCategoriesMenu($categories, $isChildren);
    }

    public function exposeGetCacheId($name = null)
    {
        return $this->getCacheId($name);
    }

    public function exposeMakeMenuOption()
    {
        return $this->makeMenuOption();
    }

    public function exposeGetCMSOptions($parent = 0, $depth = 1, $idLang = false, $itemsToSkip = null, $idShop = false)
    {
        return $this->getCMSOptions($parent, $depth, $idLang, $itemsToSkip, $idShop);
    }

    public function exposeGenerateCMSCategoriesMenu($id, $idLang)
    {
        return $this->generateCMSCategoriesMenu($id, $idLang);
    }

    public function exposeGetCacheDirectory()
    {
        return $this->getCacheDirectory();
    }

    public function setPageName($name)
    {
        $this->page_name = $name;
    }

    public function exposeClearMenuCache()
    {
        $this->clearMenuCache();
    }

    public function exposeCurrentPageIdentifier()
    {
        $ref = new ReflectionMethod($this, 'getCurrentPageIdentifier');
        $ref->setAccessible(true);

        return $ref->invoke($this);
    }
}
