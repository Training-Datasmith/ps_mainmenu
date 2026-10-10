<?php

class Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $table = 'module';
    public $identifier = 'id_module';
    public $smarty;

    private static $installed = array();
    private static $hooks = array();
    private static $instances = array();

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->smarty = new PsMainmenuSmartyAssignStub();
    }

    public static function resetState()
    {
        self::$installed = array();
        self::$hooks = array();
        self::$instances = array();
    }

    public function install()
    {
        self::$installed[$this->name] = true;

        return true;
    }

    public function uninstall()
    {
        unset(self::$installed[$this->name]);

        return true;
    }

    public function registerHook($hookName)
    {
        if (Hook::shouldFailNextRegistration()) {
            return false;
        }
        if (!isset(self::$hooks[$this->name])) {
            self::$hooks[$this->name] = array();
        }
        self::$hooks[$this->name][$hookName] = true;

        return true;
    }

    public function unregisterHook($hookName)
    {
        if (isset(self::$hooks[$this->name][$hookName])) {
            unset(self::$hooks[$this->name][$hookName]);
        }

        return true;
    }

    public static function isInstalled($name)
    {
        return isset(self::$installed[$name]);
    }

    public static function markInstalled($name)
    {
        self::$installed[$name] = true;
    }

    public static function getInstanceByName($name)
    {
        return isset(self::$instances[$name]) ? self::$instances[$name] : false;
    }

    public static function registerInstance($name, $module)
    {
        self::$instances[$name] = $module;
    }

    public static function getRegisteredHooks($name)
    {
        return isset(self::$hooks[$name]) ? self::$hooks[$name] : array();
    }

    public function trans($id, $parameters = array(), $domain = null, $locale = null)
    {
        if (is_array($parameters) && count($parameters)) {
            return vsprintf($id, $parameters);
        }

        return $id;
    }

    public function displayConfirmation($text)
    {
        return $text;
    }

    public function displayError($text)
    {
        return $text;
    }

    public function fetch($template)
    {
        return '';
    }

    protected function getCacheId($suffix = null)
    {
        return 'ps_mainmenu';
    }
}

class PsMainmenuSmartyAssignStub
{
    public $vars = array();

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->vars = array_merge($this->vars, $key);
        } else {
            $this->vars[$key] = $value;
        }
    }
}
