<?php
/**
 * Plugin Name: Gymnastics Pattern Generator
 * Plugin URI:  https://example.com/gymnastics-patterns
 * Description: Professional pattern generator for rhythmic gymnastics leotards with optional skirt.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * Text Domain: gymnastics-patterns
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * WC requires at least: 7.0
 */

defined('ABSPATH') || exit;

define('GYMPAT_VERSION', '1.0.0');
define('GYMPAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GYMPAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GYMPAT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('GYMPAT_LOG_DIR', GYMPAT_PLUGIN_DIR . 'logs/');
define('GYMPAT_PDF_DIR', WP_CONTENT_DIR . '/uploads/gymnastics-patterns/');

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $prefix = 'GymPat\\';
    $base_dir = GYMPAT_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('_', '-', strtolower($relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Инициализация плагина
require_once GYMPAT_PLUGIN_DIR . 'includes/class-activator.php';
require_once GYMPAT_PLUGIN_DIR . 'includes/class-deactivator.php';

register_activation_hook(__FILE__, ['GymPat\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['GymPat\\Deactivator', 'deactivate']);

// Запуск плагина после загрузки всех плагинов
add_action('plugins_loaded', function () {
    // Загрузка текстового домена
    load_plugin_textdomain('gymnastics-patterns', false, dirname(GYMPAT_PLUGIN_BASENAME) . '/languages');

    // Инициализация компонентов
    if (is_admin()) {
        new GymPat\Admin();
    }

    new GymPat\Shortcodes();
    new GymPat\AjaxHandler();

    // WooCommerce интеграция
    if (class_exists('WooCommerce') && get_option('gympat_woo_enabled', false)) {
        new GymPat\IntegrationWoo();
    }
});
