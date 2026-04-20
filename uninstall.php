<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;
$prefix = $wpdb->prefix;

// Удаление таблиц (если опция разрешает)
if (get_option('gympat_delete_data_on_uninstall', false)) {
    $tables = ['gymnastics_patterns', 'gymnastics_generation_log'];
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$prefix}{$table}`");
    }

    // Удаление опций
    delete_option('gympat_woo_enabled');
    delete_option('gympat_max_pages');
    delete_option('gympat_pdf_directory');
    delete_option('gympat_cache_ttl');

    // Удаление PDF-файлов
    $pdf_dir = WP_CONTENT_DIR . '/uploads/gymnastics-patterns/';
    if (is_dir($pdf_dir)) {
        array_map('unlink', glob("$pdf_dir/*.*"));
        rmdir($pdf_dir);
    }
}
