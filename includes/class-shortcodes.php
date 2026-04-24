<?php
namespace GymPat;

defined('ABSPATH') || exit;

class Shortcodes {
    public function __construct() {
        add_shortcode('gymnastics_pattern_form', [$this, 'render_form']);
        add_shortcode('gymnastics_my_patterns', [$this, 'render_my_patterns']);
    }

    public function render_form($atts) {
    if (!is_user_logged_in()) {
        return '<p>' . __('Please log in to create a pattern.', 'gymnastics-patterns') . '</p>';
    }

    // Стили
    wp_enqueue_style('gympat-style', GYMPAT_PLUGIN_URL . 'assets/css/pattern-style.css', [], GYMPAT_VERSION);

    // Регистрация и подключение скрипта
    wp_register_script(
        'gympat-pattern-form',
        GYMPAT_PLUGIN_URL . 'assets/js/pattern-form.js',
        ['jquery-core'],  // или 'jquery', если ошибки eval нет
        GYMPAT_VERSION,
        true
    );
    wp_enqueue_script('gympat-pattern-form');

    // Локализация переменных для JS
    wp_localize_script('gympat-pattern-form', 'gympat_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('gympat_nonce'),
        'i18n'     => [
            'required'             => __('обязательно.', 'gymnastics-patterns'),
            'generating'           => __('Генерация...', 'gymnastics-patterns'),
            'generate'             => __('Сгенерировать выкройку', 'gymnastics-patterns'),
            'download_pdf'         => __('Скачать PDF', 'gymnastics-patterns'),
            'cached'               => __('Загружено из кэша.', 'gymnastics-patterns'),
            'error'                => __('Ошибка.', 'gymnastics-patterns'),
            'ajax_error'           => __('Ошибка сервера.', 'gymnastics-patterns'),
            'pattern_name_prompt'  => __('Название выкройки:', 'gymnastics-patterns'),
            'saved'                => __('Сохранено.', 'gymnastics-patterns'),
            'save_error'           => __('Ошибка сохранения.', 'gymnastics-patterns'),
            'load_saved'           => __('Загрузить...', 'gymnastics-patterns'),
            'loaded'               => __('Выкройка загружена.', 'gymnastics-patterns'),
            'load_error'           => __('Ошибка загрузки.', 'gymnastics-patterns'),
            'years'                => __('лет', 'gymnastics-patterns'),
        ]
    ]);

    ob_start();
    include GYMPAT_PLUGIN_DIR . 'templates/pattern-form.php';
    return ob_get_clean();
	}

    public function render_my_patterns($atts) {
    if (!is_user_logged_in()) {
        return '<p>' . __('Please log in to view your patterns.', 'gymnastics-patterns') . '</p>';
    }

    wp_enqueue_style('gympat-style', GYMPAT_PLUGIN_URL . 'assets/css/pattern-style.css', [], GYMPAT_VERSION);

    // Подключаем внешний JS
    wp_register_script(
        'gympat-my-patterns',
        GYMPAT_PLUGIN_URL . 'assets/js/my-patterns.js',
        ['jquery-core'], // или 'jquery'
        GYMPAT_VERSION,
        true
    );
    wp_enqueue_script('gympat-my-patterns');

    // Локализация данных
    wp_localize_script('gympat-my-patterns', 'gympat_my_patterns', [
        'ajax_url'      => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('gympat_nonce'),
        'form_page_url' => home_url('/?page_id=188/'), // замените на реальный URL страницы с формой
        'i18n'          => [
            'confirm_delete' => __('Вы уверены, что хотите удалить эту выкройку?', 'gymnastics-patterns'),
            'delete_error'   => __('Ошибка при удалении.', 'gymnastics-patterns'),
            'server_error'   => __('Ошибка сервера.', 'gymnastics-patterns'),
            'delete'         => __('Удалить', 'gymnastics-patterns'),
        ],
    ]);

    ob_start();
    include GYMPAT_PLUGIN_DIR . 'templates/my-patterns.php';
    return ob_get_clean();
}
}
