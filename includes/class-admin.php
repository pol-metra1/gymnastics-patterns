<?php
namespace GymPat;

defined('ABSPATH') || exit;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_admin_pages() {
        add_menu_page(
            __('Gymnastics Patterns', 'gymnastics-patterns'),
            __('Patterns', 'gymnastics-patterns'),
            'manage_options',
            'gympat-admin',
            [$this, 'render_patterns_list'],
            'dashicons-clipboard',
            30
        );
        add_submenu_page(
            'gympat-admin',
            __('Settings', 'gymnastics-patterns'),
            __('Settings', 'gymnastics-patterns'),
            'manage_options',
            'gympat-settings',
            [$this, 'render_settings']
        );
    }

    public function render_patterns_list() {
        $db = Database::instance();
        $page = max(1, absint($_GET['paged'] ?? 1));
        $patterns = $db->get_all_patterns_admin($page);
        $total = $db->count_all_patterns();
        include GYMPAT_PLUGIN_DIR . 'templates/admin-patterns.php';
    }

    public function render_settings() {
        // Сохранение опций
        if (isset($_POST['gympat_settings_nonce']) && wp_verify_nonce($_POST['gympat_settings_nonce'], 'gympat_settings')) {
            update_option('gympat_woo_enabled', !empty($_POST['gympat_woo_enabled']));
            update_option('gympat_max_pages', absint($_POST['gympat_max_pages']));
            echo '<div class="updated"><p>' . __('Settings saved.', 'gymnastics-patterns') . '</p></div>';
        }
        $woo_enabled = get_option('gympat_woo_enabled', false);
        $max_pages = get_option('gympat_max_pages', 50);
        ?>
        <div class="wrap">
            <h1><?php _e('Pattern Generator Settings', 'gymnastics-patterns'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('gympat_settings', 'gympat_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="gympat_woo_enabled"><?php _e('Enable WooCommerce Integration', 'gymnastics-patterns'); ?></label></th>
                        <td><input type="checkbox" name="gympat_woo_enabled" id="gympat_woo_enabled" value="1" <?php checked($woo_enabled); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="gympat_max_pages"><?php _e('Maximum A4 pages', 'gymnastics-patterns'); ?></label></th>
                        <td><input type="number" name="gympat_max_pages" id="gympat_max_pages" value="<?php echo esc_attr($max_pages); ?>" min="1" max="100"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'gympat') !== false) {
            wp_enqueue_style('gympat-admin', GYMPAT_PLUGIN_URL . 'assets/css/admin.css', [], GYMPAT_VERSION);
        }
    }
}
