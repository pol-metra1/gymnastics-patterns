<?php
namespace GymPat;

defined('ABSPATH') || exit;

class IntegrationWoo {
    public function __construct() {
        add_action('init', [$this, 'create_virtual_product']);
        add_action('woocommerce_order_status_completed', [$this, 'grant_pattern_access']);
    }

    public function create_virtual_product() {
        // Создать товар при активации опции (однократно)
        if (!get_option('gympat_woo_product_id')) {
            $product_id = wp_insert_post([
                'post_title'   => __('Pattern Generation Access', 'gymnastics-patterns'),
                'post_content' => __('Digital access to generate custom gymnastics patterns.', 'gymnastics-patterns'),
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ]);
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_price', '19.99');
            update_option('gympat_woo_product_id', $product_id);
        }
    }

    public function grant_pattern_access($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        $product_id = get_option('gympat_woo_product_id');

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                // Даём доступ, например, увеличиваем лимит генераций в user_meta
                $current = get_user_meta($user_id, 'gympat_credits', true) ?: 0;
                update_user_meta($user_id, 'gympat_credits', $current + 1);
                break;
            }
        }
    }
}
