<?php

class PsMainmenuSchema
{
    public static function ensure()
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;

        $statements = array(
            "CREATE TABLE IF NOT EXISTS `{$p}hook` (
                `id_hook` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(128) NOT NULL,
                `title` VARCHAR(256) NOT NULL,
                `description` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}shop` (
                `id_shop` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(128) NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}module` (
                `id_module` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}category` (
                `id_category` INT UNSIGNED NOT NULL PRIMARY KEY,
                `id_parent` INT UNSIGNED NOT NULL DEFAULT 0,
                `level_depth` INT UNSIGNED NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `is_root_category` TINYINT(1) NOT NULL DEFAULT 0,
                `id_shop` INT UNSIGNED NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}category_lang` (
                `id_category` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`id_category`, `id_lang`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}category_shop` (
                `id_category` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `position` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_category`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}category_group` (
                `id_category` INT UNSIGNED NOT NULL,
                `id_group` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_category`, `id_group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms` (
                `id_cms` INT UNSIGNED NOT NULL PRIMARY KEY,
                `id_cms_category` INT UNSIGNED NOT NULL DEFAULT 1,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `position` INT UNSIGNED NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms_lang` (
                `id_cms` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `meta_title` VARCHAR(128) NOT NULL,
                `link_rewrite` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`id_cms`, `id_lang`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms_shop` (
                `id_cms` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_cms`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms_category` (
                `id_cms_category` INT UNSIGNED NOT NULL PRIMARY KEY,
                `id_parent` INT UNSIGNED NOT NULL DEFAULT 0,
                `level_depth` INT UNSIGNED NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `position` INT UNSIGNED NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms_category_lang` (
                `id_cms_category` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `link_rewrite` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`id_cms_category`, `id_lang`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}cms_category_shop` (
                `id_cms_category` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_cms_category`, `id_shop`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        );

        foreach ($statements as $sql) {
            if (!$db->execute($sql)) {
                throw new RuntimeException('Schema failed: ' . $sql);
            }
        }
    }

    public static function truncateAll()
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $tables = array(
            'linksmenutop_lang',
            'linksmenutop',
            'hook',
            'shop',
            'module',
            'category',
            'category_lang',
            'category_shop',
            'category_group',
            'cms',
            'cms_lang',
            'cms_shop',
            'cms_category',
            'cms_category_lang',
            'cms_category_shop',
        );
        $db->execute('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $db->execute('TRUNCATE TABLE `' . $p . $table . '`');
        }
        $db->execute('SET FOREIGN_KEY_CHECKS=1');
    }
}
