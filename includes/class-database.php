<?php
/**
 * Database Class
 *
 * Обеспечивает взаимодействие с таблицами плагина.
 *
 * @package GymnasticsPatterns
 */

namespace GymPat;

defined( 'ABSPATH' ) || exit;

class Database {

    /**
     * Экземпляр класса (Singleton).
     *
     * @var self|null
     */
    protected static $instance = null;

    /**
     * Имя таблицы выкроек.
     *
     * @var string
     */
    private $table_patterns;

    /**
     * Имя таблицы логов.
     *
     * @var string
     */
    private $table_logs;

    /**
     * Конструктор.
     */
    private function __construct() {
        global $wpdb;
        $this->table_patterns = $wpdb->prefix . 'gymnastics_patterns';
        $this->table_logs     = $wpdb->prefix . 'gymnastics_generation_log';
    }

    /**
     * Возвращает единственный экземпляр класса.
     *
     * @return self
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Сохраняет или обновляет выкройку.
     *
     * @param int         $user_id
     * @param string      $pattern_name
     * @param array       $parameters
     * @param string|null $pattern_data
     * @param string|null $pdf_url
     * @param int         $pattern_id
     * @return int ID записи.
     */
    public function save_pattern( $user_id, $pattern_name, $parameters, $pattern_data = null, $pdf_url = null, $pattern_id = 0 ) {
        global $wpdb;
        $data = [
            'user_id'      => $user_id,
            'pattern_name' => sanitize_text_field( $pattern_name ),
            'parameters'   => wp_json_encode( $parameters ),
            'pattern_data' => $pattern_data ? wp_json_encode( $pattern_data ) : null,
            'pdf_url'      => $pdf_url ? esc_url_raw( $pdf_url ) : null,
            'updated_at'   => current_time( 'mysql' ),
        ];

        if ( $pattern_id > 0 ) {
            $wpdb->update( $this->table_patterns, $data, [ 'id' => $pattern_id, 'user_id' => $user_id ] );
            return $pattern_id;
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $this->table_patterns, $data );
            return $wpdb->insert_id;
        }
    }

    /**
     * Получает выкройки конкретного пользователя.
     *
     * @param int $user_id
     * @return array
     */
    public function get_patterns_by_user( $user_id ) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_patterns} WHERE user_id = %d ORDER BY updated_at DESC",
            $user_id
        );
        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Получает одну выкройку по ID (с опциональной проверкой владельца).
     *
     * @param int      $pattern_id
     * @param int|null $user_id   Если передан, проверяется принадлежность пользователю.
     * @return array|null
     */
    public function get_pattern( $pattern_id, $user_id = null ) {
        global $wpdb;
        if ( $user_id ) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_patterns} WHERE id = %d AND user_id = %d",
                $pattern_id,
                $user_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_patterns} WHERE id = %d",
                $pattern_id
            );
        }
        return $wpdb->get_row( $query, ARRAY_A );
    }

    /**
     * Удаляет выкройку. Если передан user_id, удаляет только при совпадении владельца.
     *
     * @param int      $pattern_id
     * @param int|null $user_id
     * @return bool
     */
    public function delete_pattern( $pattern_id, $user_id = null ) {
        global $wpdb;
        $where = [ 'id' => $pattern_id ];
        if ( $user_id ) {
            $where['user_id'] = $user_id;
        }
        return (bool) $wpdb->delete( $this->table_patterns, $where );
    }

    /**
     * Логирует ошибку генерации.
     *
     * @param int         $user_id
     * @param string      $message
     * @param array|null  $parameters
     */
    public function log_error( $user_id, $message, $parameters = null ) {
        global $wpdb;
        $wpdb->insert( $this->table_logs, [
            'user_id'    => $user_id,
            'message'    => sanitize_textarea_field( $message ),
            'parameters' => $parameters ? wp_json_encode( $parameters ) : null,
            'created_at' => current_time( 'mysql' ),
        ] );
    }

    /**
     * Возвращает все выкройки для администратора с учётом поиска и пагинации.
     *
     * @param int    $per_page Количество записей на странице.
     * @param int    $page     Номер страницы.
     * @param string $search   Строка поиска (ищет по названию, имени гимнастки, имени пользователя).
     * @return array
     */
    public function get_all_patterns_admin( $per_page = 20, $page = 1, $search = '' ) {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $search ) ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where      .= ' AND (p.pattern_name LIKE %s OR p.parameters LIKE %s)';
            $params[]   = $search_like;
            $params[]   = $search_like;
        }

        $offset = ( $page - 1 ) * $per_page;

        $query = "SELECT p.*, u.display_name AS user_name
                  FROM {$this->table_patterns} p
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                  WHERE {$where}
                  ORDER BY p.updated_at DESC
                  LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        // Безопасный вызов prepare с распаковкой массива параметров
        return $wpdb->get_results( $wpdb->prepare( $query, ...$params ), ARRAY_A );
    }

    /**
     * Подсчитывает общее количество выкроек (с учётом поиска).
     *
     * @param string $search
     * @return int
     */
    public function count_all_patterns( $search = '' ) {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $search ) ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where      .= ' AND (p.pattern_name LIKE %s OR p.parameters LIKE %s)';
            $params[]   = $search_like;
            $params[]   = $search_like;
        }

        $query = "SELECT COUNT(*) FROM {$this->table_patterns} p WHERE {$where}";

        if ( ! empty( $params ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare( $query, ...$params ) );
        }

        return (int) $wpdb->get_var( $query );
    }
}
