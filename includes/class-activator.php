<?php
namespace GymPat;

defined('ABSPATH') || exit;

class Activator {
    public static function activate() {
        self::create_tables();
        self::create_directories();
        self::set_options();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}gymnastics_patterns` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `pattern_name` VARCHAR(255) NOT NULL,
            `parameters` LONGTEXT NOT NULL COMMENT 'JSON',
            `pattern_data` LONGTEXT NULL COMMENT 'JSON',
            `pdf_url` VARCHAR(512) NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `created_at` (`created_at`)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Таблица для ошибок генерации
        $sql_log = "CREATE TABLE IF NOT EXISTS `{$prefix}gymnastics_generation_log` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NULL,
            `message` TEXT NOT NULL,
            `parameters` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
        ) $charset_collate;";
        dbDelta($sql_log);
    }

    private static function create_directories() {
        if (!file_exists(GYMPAT_PDF_DIR)) {
            wp_mkdir_p(GYMPAT_PDF_DIR);
            file_put_contents(GYMPAT_PDF_DIR . '.htaccess', "Deny from all\n");
        }
        if (!file_exists(GYMPAT_LOG_DIR)) {
            wp_mkdir_p(GYMPAT_LOG_DIR);
            file_put_contents(GYMPAT_LOG_DIR . '.htaccess', "Deny from all\n");
        }
    }

    private static function set_options() {
        add_option('gympat_woo_enabled', false);
        add_option('gympat_max_pages', 50);
        add_option('gympat_pdf_directory', GYMPAT_PDF_DIR);
        add_option('gympat_cache_ttl', 86400); // 24 часа
        add_option('gympat_license_key', '');
    }
}
