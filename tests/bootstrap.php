<?php

error_reporting(-1);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');
ini_set('display_startup_errors', '1');

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.7');
}
define('_DB_PREFIX_', 'ps_');
define('_MYSQL_ENGINE_', 'InnoDB');
define('PS_INSTALLATION_IN_PROGRESS', false);

$runtimeRoot = getenv('PS_MAINMENU_RUNTIME');
if (!$runtimeRoot) {
    $runtimeRoot = sys_get_temp_dir() . '/ps_mainmenu_runtime_' . getmypid();
}
if (!is_dir($runtimeRoot)) {
    mkdir($runtimeRoot, 0775, true);
}
define('_PS_CACHE_DIR_', $runtimeRoot . '/cache/');
define('_PS_CAT_IMG_DIR_', $runtimeRoot . '/catimg/');
define('_THEME_CAT_DIR_', '/themes/classic/assets/img/c/');

if (!is_dir(_PS_CACHE_DIR_)) {
    mkdir(_PS_CACHE_DIR_, 0775, true);
}
if (!is_dir(_PS_CAT_IMG_DIR_)) {
    mkdir(_PS_CAT_IMG_DIR_, 0775, true);
}

require_once dirname(__FILE__) . '/Support/PrestaShopWidgetInterface.php';
require_once dirname(__FILE__) . '/Support/Autoload.php';
PsMainmenuTestAutoload::register();

require_once dirname(__FILE__) . '/Support/Schema.php';
PsMainmenuSchema::ensure();

require_once dirname(__DIR__) . '/ps_mainmenu.php';
