<?php
namespace GymPat;

defined('ABSPATH') || exit;

class Deactivator {
    public static function deactivate() {
        global $wpdb;
        $prefix = $wpdb->prefix;

        // Переименовываем таблицы с суффиксом _backup_
        $tables = ['gymnastics_patterns', 'gymnastics_generation_log'];
        foreach ($tables as $table) {
            $old = $prefix . $table;
            $new = $prefix . $table . '_backup_' . date('YmdHis');
            $wpdb->query("RENAME TABLE `$old` TO `$new`");
        }

        // Очищаем запланированные задачи
        wp_clear_scheduled_hook('gympat_async_generate_pdf');
    }
}
