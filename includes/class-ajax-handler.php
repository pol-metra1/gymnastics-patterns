<?php
/**
 * AJAX Handler Class
 *
 * Обрабатывает все AJAX-запросы плагина:
 * - генерация PDF,
 * - сохранение / загрузка / удаление выкроек,
 * - получение списка сохранённых выкроек.
 *
 * @package GymnasticsPatterns
 * @since   1.0.0
 */

namespace GymPat;

defined( 'ABSPATH' ) || exit;

class AjaxHandler {

    /**
     * Регистрация AJAX-хуков.
     */
    public function __construct() {
        add_action( 'wp_ajax_gymnastics_generate_pattern',   [ $this, 'generate_pattern' ] );
        add_action( 'wp_ajax_gymnastics_save_pattern',      [ $this, 'save_pattern' ] );
        add_action( 'wp_ajax_gymnastics_delete_pattern',    [ $this, 'delete_pattern' ] );
        add_action( 'wp_ajax_gymnastics_get_pattern_data',  [ $this, 'get_pattern_data' ] );
        add_action( 'wp_ajax_gymnastics_get_patterns_list', [ $this, 'get_patterns_list' ] );
    }

    /**
     * Генерация PDF выкройки.
     */
    public function generate_pattern() {
        // Проверка nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'gympat_nonce' ) ) {
            wp_send_json_error( __( 'Недействительный токен безопасности.', 'gymnastics-patterns' ) );
        }

        // Только авторизованные пользователи
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Требуется авторизация.', 'gymnastics-patterns' ) );
        }

        $raw_params = $_POST['params'] ?? [];
        $params = $this->validate_params( $raw_params );
        if ( is_wp_error( $params ) ) {
            wp_send_json_error( $params->get_error_message() );
        }

        // Увеличиваем ресурсы
        @set_time_limit( 60 );
        @ini_set( 'memory_limit', '256M' );

        try {
            // Проверяем кэш (ключ — md5 от сериализованных параметров)
            $cache_key = 'gympat_pattern_' . md5( serialize( $params ) );
            $cached = get_transient( $cache_key );
            if ( $cached ) {
                wp_send_json_success( [
                    'pdf_url' => $cached,
                    'cached'  => true,
                ] );
            }

            // Расчёт выкройки
            if ( ! class_exists( 'GymPat\PatternCalculator' ) ) {
                throw new \Exception( __( 'Класс PatternCalculator не найден.', 'gymnastics-patterns' ) );
            }
            $calculator   = new PatternCalculator( $params );
            $pattern_data = $calculator->get_all_points();

            // Проверка и подключение TCPDF
            $tcpdf_file = GYMPAT_PLUGIN_DIR . 'vendor/tcpdf/tcpdf.php';
            if ( ! file_exists( $tcpdf_file ) ) {
                throw new \Exception( sprintf( __( 'Библиотека TCPDF не найдена по пути: %s', 'gymnastics-patterns' ), $tcpdf_file ) );
            }
            require_once $tcpdf_file;
            if ( ! class_exists( 'TCPDF' ) ) {
                throw new \Exception( __( 'Класс TCPDF не определён.', 'gymnastics-patterns' ) );
            }

            // Генерация PDF
            $pdf = new PDF_Generator(
                $pattern_data,
                $params['gymnast_name'] ?? '',
                $params['gymnast_age'] ?? 0
            );

            // Убедимся, что директория для PDF существует и доступна для записи
            if ( ! is_dir( GYMPAT_PDF_DIR ) ) {
                wp_mkdir_p( GYMPAT_PDF_DIR );
            }
            if ( ! is_writable( GYMPAT_PDF_DIR ) ) {
                throw new \Exception( __( 'Директория для PDF недоступна для записи.', 'gymnastics-patterns' ) );
            }

            $filename = 'pattern_' . uniqid() . '.pdf';
            $pdf_path = GYMPAT_PDF_DIR . $filename;
            $pdf->generate( $pdf_path );

            $pdf_url = content_url( '/uploads/gymnastics-patterns/' . $filename );

            // Сохраняем в кэш
            set_transient( $cache_key, $pdf_url, (int) get_option( 'gympat_cache_ttl', DAY_IN_SECONDS ) );

            wp_send_json_success( [ 'pdf_url' => $pdf_url ] );
        } catch ( \Exception $e ) {
            Database::instance()->log_error( get_current_user_id(), $e->getMessage(), $params );
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * Сохранение параметров выкройки в БД.
     */
    public function save_pattern() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'gympat_nonce' ) ) {
            wp_send_json_error( __( 'Недействительный токен безопасности.', 'gymnastics-patterns' ) );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Требуется авторизация.', 'gymnastics-patterns' ) );
        }

        $params = $this->validate_params( $_POST['params'] ?? [] );
        if ( is_wp_error( $params ) ) {
            wp_send_json_error( $params->get_error_message() );
        }

        $pattern_name = sanitize_text_field( $_POST['pattern_name'] ?? __( 'Без названия', 'gymnastics-patterns' ) );
        $pattern_id   = absint( $_POST['pattern_id'] ?? 0 );

        $db      = Database::instance();
        $user_id = get_current_user_id();

        // Если передан ID — проверяем, что выкройка принадлежит пользователю
        if ( $pattern_id > 0 ) {
            $existing = $db->get_pattern( $pattern_id, $user_id );
            if ( ! $existing ) {
                wp_send_json_error( __( 'Выкройка не найдена или доступ запрещён.', 'gymnastics-patterns' ) );
            }
        }

        $pid = $db->save_pattern( $user_id, $pattern_name, $params, null, null, $pattern_id );
        wp_send_json_success( [ 'pattern_id' => $pid ] );
    }

    /**
     * Удаление выкройки.
     */
    public function delete_pattern() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'gympat_nonce' ) ) {
            wp_send_json_error( __( 'Недействительный токен безопасности.', 'gymnastics-patterns' ) );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Требуется авторизация.', 'gymnastics-patterns' ) );
        }

        $pattern_id = absint( $_POST['pattern_id'] ?? 0 );
        if ( ! $pattern_id ) {
            wp_send_json_error( __( 'Не указан идентификатор выкройки.', 'gymnastics-patterns' ) );
        }

        $db      = Database::instance();
        $user_id = get_current_user_id();

        // Администраторы могут удалять любые выкройки
        if ( current_user_can( 'manage_options' ) ) {
            $deleted = $db->delete_pattern( $pattern_id );
        } else {
            $deleted = $db->delete_pattern( $pattern_id, $user_id );
        }

        if ( $deleted ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( __( 'Не удалось удалить выкройку.', 'gymnastics-patterns' ) );
        }
    }

    /**
     * Получение данных одной выкройки (для редактирования).
     */
    public function get_pattern_data() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'gympat_nonce' ) ) {
            wp_send_json_error( __( 'Недействительный токен безопасности.', 'gymnastics-patterns' ) );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Требуется авторизация.', 'gymnastics-patterns' ) );
        }

        $pattern_id = absint( $_POST['pattern_id'] ?? 0 );
        if ( ! $pattern_id ) {
            wp_send_json_error( __( 'Не указан идентификатор выкройки.', 'gymnastics-patterns' ) );
        }

        $db = Database::instance();
        $user_id = get_current_user_id();

        // Администраторы могут просматривать любые
        if ( current_user_can( 'manage_options' ) ) {
            $pattern = $db->get_pattern( $pattern_id );
        } else {
            $pattern = $db->get_pattern( $pattern_id, $user_id );
        }

        if ( $pattern ) {
            $pattern['parameters'] = json_decode( $pattern['parameters'], true );
            wp_send_json_success( $pattern );
        } else {
            wp_send_json_error( __( 'Выкройка не найдена.', 'gymnastics-patterns' ) );
        }
    }

    /**
     * Возвращает список выкроек текущего пользователя для выпадающего списка.
     */
    public function get_patterns_list() {
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'gympat_nonce' ) ) {
            wp_send_json_error( __( 'Недействительный токен безопасности.', 'gymnastics-patterns' ) );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Требуется авторизация.', 'gymnastics-patterns' ) );
        }

        $db       = Database::instance();
        $patterns = $db->get_patterns_by_user( get_current_user_id() );

        $list = [];
        foreach ( $patterns as $p ) {
            $params = json_decode( $p['parameters'], true );
            $list[] = [
                'id'             => $p['id'],
                'pattern_name'   => $p['pattern_name'],
                'gymnast_name'   => $params['gymnast_name'] ?? '',
                'gymnast_age'    => $params['gymnast_age'] ?? '',
                'pdf_url'        => $p['pdf_url'],
                'updated_at'     => $p['updated_at'],
            ];
        }

        wp_send_json_success( $list );
    }

    /**
     * Базовая валидация массива параметров.
     *
     * @param mixed $raw
     * @return array|\WP_Error
     */
    private function validate_params( $raw ) {
        if ( ! is_array( $raw ) ) {
            return new \WP_Error( 'invalid_params', __( 'Параметры должны быть массивом.', 'gymnastics-patterns' ) );
        }

        // Дополнительная проверка обязательных полей (при необходимости)
        // Основная очистка и валидация диапазонов происходит в PatternCalculator::sanitize_params()
        return $raw;
    }
}
