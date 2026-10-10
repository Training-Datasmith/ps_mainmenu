<?php

class Db
{
    const INSERT = 1;
    const INSERT_IGNORE = 2;
    const REPLACE = 3;
    const ON_DUPLICATE_KEY = 4;

    /** @var Db */
    private static $instance;

    /** @var mysqli */
    private $link;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetInstance()
    {
        if (self::$instance) {
            self::$instance->disconnect();
        }
        self::$instance = null;
    }

    private function __construct()
    {
        $host = getenv('PS_MAINMENU_DB_HOST') ? getenv('PS_MAINMENU_DB_HOST') : '127.0.0.1';
        $port = (int) (getenv('PS_MAINMENU_DB_PORT') ? getenv('PS_MAINMENU_DB_PORT') : 3306);
        $user = getenv('PS_MAINMENU_DB_USER') ? getenv('PS_MAINMENU_DB_USER') : 'ps';
        $pass = getenv('PS_MAINMENU_DB_PASS') ? getenv('PS_MAINMENU_DB_PASS') : 'ps_test';
        $db = getenv('PS_MAINMENU_DB_NAME') ? getenv('PS_MAINMENU_DB_NAME') : 'ps_mainmenu_test';

        $this->link = new mysqli($host, $user, $pass, $db, $port);
        if ($this->link->connect_error) {
            throw new PrestaShopDatabaseException('connect: ' . $this->link->connect_error);
        }
        if (!$this->link->query('SET NAMES utf8mb4')) {
            throw new PrestaShopDatabaseException('SET NAMES utf8mb4 failed');
        }
        if (!$this->link->query("SET SESSION sql_mode = ''")) {
            throw new PrestaShopDatabaseException('SET SESSION sql_mode failed');
        }
        $mode = $this->getValue('SELECT @@SESSION.sql_mode');
        if ($mode !== '') {
            throw new PrestaShopDatabaseException('sql_mode not empty: ' . $mode);
        }
    }

    public function disconnect()
    {
        if ($this->link) {
            $this->link->close();
            $this->link = null;
        }
    }

    public function execute($sql)
    {
        return (bool) $this->link->query($sql);
    }

    public function executeS($sql)
    {
        $result = $this->link->query($sql);
        if ($result === false) {
            return false;
        }
        if ($result === true) {
            return array();
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();

        return $rows;
    }

    public function insert($table, $data, $null_values = false, $use_cache = true, $type = Db::INSERT, $add_prefix = true)
    {
        if ($add_prefix) {
            $table = _DB_PREFIX_ . $table;
        }

        if ($type == Db::INSERT) {
            $insertKeyword = 'INSERT';
        } elseif ($type == Db::INSERT_IGNORE) {
            $insertKeyword = 'INSERT IGNORE';
        } else {
            $insertKeyword = 'INSERT';
        }

        $current = current($data);
        if (!is_array($current)) {
            $data = array($data);
        }

        $valuesStringified = array();
        $keys = array();
        $firstRow = true;
        foreach ($data as $rowData) {
            $values = array();
            foreach ($rowData as $key => $value) {
                if ($firstRow) {
                    $keys[] = '`' . $key . '`';
                }
                // PrestaShop Db::insert() does not escape; callers must pSQL() strings first.
                $values[] = "'" . $value . "'";
            }
            $firstRow = false;
            $valuesStringified[] = '(' . implode(', ', $values) . ')';
        }

        $sql = $insertKeyword . ' INTO `' . $table . '` (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $valuesStringified);

        return $this->execute($sql);
    }

    public function update($table, $data, $where = '', $limit = 0, $null_values = false, $use_cache = true, $add_prefix = true)
    {
        if ($add_prefix) {
            $table = _DB_PREFIX_ . $table;
        }
        $sets = array();
        foreach ($data as $key => $value) {
            // PrestaShop Db::update() does not escape; callers must pSQL() strings first.
            $sets[] = '`' . $key . "` = '" . $value . "'";
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets);
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->execute($sql);
    }

    public function delete($table, $where = '', $limit = 0, $use_cache = true, $add_prefix = true)
    {
        if ($add_prefix) {
            $table = _DB_PREFIX_ . $table;
        }
        $sql = 'DELETE FROM `' . $table . '`' . ($where ? ' WHERE ' . $where : '');

        return $this->execute($sql);
    }

    public function Insert_ID()
    {
        return $this->link->insert_id;
    }

    public function escape($string, $html_ok = false, $bq_sql = false)
    {
        if (is_numeric($string)) {
            return $string;
        }

        return $this->link->real_escape_string((string) $string);
    }

    public function getValue($sql)
    {
        $rows = $this->executeS($sql);
        if (!$rows || !isset($rows[0])) {
            return null;
        }
        $row = $rows[0];

        return reset($row);
    }
}

function pSQL($string, $html_ok = false, $bq_sql = false)
{
    return Db::getInstance()->escape($string, $html_ok, $bq_sql);
}

function bqSQL($string)
{
    return str_replace('`', '', $string);
}
