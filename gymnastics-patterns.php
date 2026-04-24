<?php
/**
 * Plugin Name:  Gymnastics Pattern Generator
 * Plugin URI:   https://github.com/pol-metra1/gymnastics-patterns
 * Description:  Профессиональный генератор выкроек купальников для художественной гимнастики с возможностью добавления юбки.
 * Version:      1.0.0
 * Author:       pol-metra1
 * Author URI:   https://github.com/pol-metra1
 * License:      GPL-2.0+
 * Text Domain:  gymnastics-patterns
 * Domain Path:  /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

// ==========================================
// Константы плагина
// ==========================================
define( 'GYMPAT_VERSION', '1.0.0' );
define( 'GYMPAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GYMPAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GYMPAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'GYMPAT_LOG_DIR', GYMPAT_PLUGIN_DIR . 'logs/' );
define( 'GYMPAT_PDF_DIR', WP_CONTENT_DIR . '/uploads/gymnastics-patterns/' );

// ==========================================
// Временное включение логирования ошибок (удалить после отладки)
// ==========================================
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', true );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
    define( 'WP_DEBUG_DISPLAY', false );
}
@ini_set( 'display_errors', 1 );
@ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// ==========================================
// Автозагрузчик классов (CamelCase + Underscores → class-kebab-case.php)
// ==========================================
spl_autoload_register( function ( $class ) {
    $prefix   = 'GymPat\\';
    $base_dir = GYMPAT_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );

    // Заменяем подчёркивания на дефисы, затем расставляем дефисы перед заглавными буквами
    $file_name = str_replace( '_', '-', $relative_class );
    $file_name = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $file_name );
    $file_name = preg_replace( '/([A-Z])([A-Z][a-z])/', '$1-$2', $file_name );
    $file_name = 'class-' . strtolower( $file_name ) . '.php';

    $file = $base_dir . $file_name;

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ==========================================
// Обязательные файлы активации и деактивации
// ==========================================
require_once GYMPAT_PLUGIN_DIR . 'includes/class-activator.php';
require_once GYMPAT_PLUGIN_DIR . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, [ 'GymPat\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'GymPat\\Deactivator', 'deactivate' ] );

// ==========================================
// Отключение jQuery Migrate на публичной части (исправление ошибки CSP)
// ==========================================
add_action( 'wp_default_scripts', function ( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $scripts->registered['jquery']->deps = array_diff(
            $scripts->registered['jquery']->deps,
            [ 'jquery-migrate' ]
        );
    }
} );

// ==========================================
// Инициализация плагина после загрузки всех плагинов
// ==========================================
add_action( 'plugins_loaded', function () {
    // Загружаем переводы
    load_plugin_textdomain(
        'gymnastics-patterns',
        false,
        dirname( GYMPAT_PLUGIN_BASENAME ) . '/languages'
    );

    // Компоненты, создаваемые только при существовании классов
    if ( is_admin() && class_exists( 'GymPat\\Admin' ) ) {
        new GymPat\Admin();
    }

    if ( class_exists( 'GymPat\\Shortcodes' ) ) {
        new GymPat\Shortcodes();
    }

    if ( class_exists( 'GymPat\\AjaxHandler' ) ) {
        new GymPat\AjaxHandler();
    }

    // WooCommerce интеграция (опционально)
    if (
        class_exists( 'WooCommerce' ) &&
        get_option( 'gympat_woo_enabled', false ) &&
        class_exists( 'GymPat\\IntegrationWoo' )
    ) {
        new GymPat\IntegrationWoo();
    }
} );
