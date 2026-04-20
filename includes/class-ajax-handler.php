<?php
namespace GymPat;

defined('ABSPATH') || exit;

class AjaxHandler {
    public function __construct() {
        add_action('wp_ajax_gymnastics_generate_pattern', [$this, 'generate_pattern']);
        add_action('wp_ajax_gymnastics_save_pattern', [$this, 'save_pattern']);
        add_action('wp_ajax_gymnastics_delete_pattern', [$this, 'delete_pattern']);
        add_action('wp_ajax_gymnastics_get_pattern_data', [$this, 'get_pattern_data']);
    }

    public function generate_pattern() {
        if (!wp_verify_nonce($_POST['nonce'], 'gympat_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        if (!is_user_logged_in()) {
            wp_send_json_error('Not authorized');
        }

        $params = $this->validate_params($_POST['params'] ?? []);
        if (is_wp_error($params)) {
            wp_send_json_error($params->get_error_message());
        }

        try {
            // Проверяем кэш
            $cache_key = 'gympat_pattern_' . md5(serialize($params));
            $cached = get_transient($cache_key);
            if ($cached) {
                wp_send_json_success(['pdf_url' => $cached, 'cached' => true]);
            }

            // Расчёт выкройки
            $calculator = new PatternCalculator($params);
            $pattern_data = $calculator->get_all_points();
            $bbox = $calculator->get_bounding_box();
            $pattern_data['bbox'] = $bbox;

            // Генерация PDF
            $pdf = new PDF_Generator($pattern_data, $params['gymnast_name'], $params['gymnast_age']);
            $filename = 'pattern_' . uniqid() . '.pdf';
            $pdf_path = GYMPAT_PDF_DIR . $filename;
            $pdf->generate($pdf_path);

            $pdf_url = content_url('/uploads/gymnastics-patterns/' . $filename);
            set_transient($cache_key, $pdf_url, get_option('gympat_cache_ttl', DAY_IN_SECONDS));

            wp_send_json_success(['pdf_url' => $pdf_url]);
        } catch (\Exception $e) {
            Database::instance()->log_error(get_current_user_id(), $e->getMessage(), $params);
            wp_send_json_error($e->getMessage());
        }
    }

    public function save_pattern() {
        // Проверка nonce и прав
        $params = $this->validate_params($_POST['params'] ?? []);
        $pattern_name = sanitize_text_field($_POST['pattern_name'] ?? '');
        $pattern_id = absint($_POST['pattern_id'] ?? 0);

        $db = Database::instance();
        $user_id = get_current_user_id();
        $pattern_id = $db->save_pattern($user_id, $pattern_name, $params, null, null, $pattern_id);
        wp_send_json_success(['pattern_id' => $pattern_id]);
    }

    public function delete_pattern() {
        // Аналогично с проверками
        $pattern_id = absint($_POST['pattern_id'] ?? 0);
        $db = Database::instance();
        $db->delete_pattern($pattern_id, get_current_user_id());
        wp_send_json_success();
    }

    public function get_pattern_data() {
        $pattern_id = absint($_POST['pattern_id'] ?? 0);
        $db = Database::instance();
        $pattern = $db->get_pattern($pattern_id, get_current_user_id());
        if ($pattern) {
            $pattern['parameters'] = json_decode($pattern['parameters'], true);
            wp_send_json_success($pattern);
        }
        wp_send_json_error('Not found');
    }

    private function validate_params($raw) {
        // Валидация всех полей с диапазонами
        $errors = [];
        $params = [];
        // ... (реализовать полную валидацию)
        return $params;
    }
}
