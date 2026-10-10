<?php

class PsMainmenuSmartyRenderer
{
    public static function render(array $menu)
    {
        $smartyRoot = getenv('PS_MAINMENU_SMARTY_ROOT');
        if (!$smartyRoot || !is_dir($smartyRoot)) {
            throw new RuntimeException('Smarty root not configured');
        }
        require_once $smartyRoot . '/libs/Smarty.class.php';
        $smarty = new Smarty();
        $compileDir = getenv('PS_MAINMENU_SMARTY_COMPILE');
        if (!$compileDir) {
            $compileDir = sys_get_temp_dir() . '/ps_mainmenu_smarty_compile';
        }
        if (!is_dir($compileDir)) {
            mkdir($compileDir, 0775, true);
        }
        $smarty->setCompileDir($compileDir);
        $smarty->setCacheDir($compileDir);
        $smarty->assign('menu', $menu);

        return $smarty->fetch(dirname(__DIR__) . '/../ps_mainmenu.tpl');
    }
}
