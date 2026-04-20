<?php
namespace GymPat;

defined('ABSPATH') || exit;

class Database {
    protected static $instance = null;
    private $table_patterns;
    private $table_logs;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_patterns = $wpdb->prefix . 'gymnastics_patterns';
        $this->table_logs     = $wpdb->prefix . 'gymnastics_generation_log';
    }

    public function save_pattern($user_id, $pattern_name, $parameters, $pattern_data = null, $pdf_url = null, $pattern_id = 0) {
        global $wpdb;
        $data = [
            'user_id'      => $user_id,
            'pattern_name' => sanitize_text_field($pattern_name),
            'parameters'   => wp_json_encode($parameters),
            'pattern_data' => $pattern_data ? wp_json_encode($pattern_data) : null,
            'pdf_url'      => $pdf_url ? esc_url_raw($pdf_url) : null,
            'updated_at'   => current_time('mysql'),
        ];

        if ($pattern_id > 0) {
            $wpdb->update($this->table_patterns, $data, ['id' => $pattern_id, 'user_id' => $user_id]);
            return $pattern_id;
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->table_patterns, $data);
            return $wpdb->insert_id;
        }
    }

    public function get_patterns_by_user($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_patterns} WHERE user_id = %d ORDER BY updated_at DESC",
            $user_id
        ), ARRAY_A);
    }

    public function get_pattern($pattern_id, $user_id = null) {
        global $wpdb;
        $where = "id = %d";
        $params = [$pattern_id];
        if ($user_id) {
            $where .= " AND user_id = %d";
            $params[] = $user_id;
        }
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_patterns} WHERE $where", $params), ARRAY_A);
    }

    public function delete_pattern($pattern_id, $user_id = null) {
        global $wpdb;
        $where = ['id' => $pattern_id];
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        return $wpdb->delete($this->table_patterns, $where);
    }

    public function log_error($user_id, $message, $parameters = null) {
        global $wpdb;
        $wpdb->insert($this->table_logs, [
            'user_id'    => $user_id,
            'message'    => sanitize_textarea_field($message),
            'parameters' => $parameters ? wp_json_encode($parameters) : null,
            'created_at' => current_time('mysql'),
        ]);
    }

    public function get_all_patterns_admin($page = 1, $per_page = 20) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_patterns} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ), ARRAY_A);
    }

    public function count_all_patterns() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_patterns}");
    }
}
