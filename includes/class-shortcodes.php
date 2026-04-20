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
        ob_start();
        include GYMPAT_PLUGIN_DIR . 'templates/pattern-form.php';
        return ob_get_clean();
    }

    public function render_my_patterns($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your patterns.', 'gymnastics-patterns') . '</p>';
        }
        wp_enqueue_script('gympat-my-patterns');
        wp_enqueue_style('gympat-style');
        ob_start();
        include GYMPAT_PLUGIN_DIR . 'templates/my-patterns.php';
        return ob_get_clean();
    }
}
