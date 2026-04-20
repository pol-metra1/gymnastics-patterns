<?php
/**
 * Admin Patterns List Template
 *
 * @package GymnasticsPatterns
 */

defined('ABSPATH') || exit;

// Ensure required variables are available
if (!isset($patterns) || !isset($total)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error: Unable to load patterns data.', 'gymnastics-patterns') . '</p></div>';
    return;
}

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$total_pages = ceil($total / $per_page);
$db = \GymPat\Database::instance();

// Search term handling (if implemented in query)
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Gymnastics Patterns', 'gymnastics-patterns'); ?></h1>
    <hr class="wp-header-end">

    <?php if ($search): ?>
        <p class="search-description">
            <?php printf(esc_html__('Search results for: %s', 'gymnastics-patterns'), '<strong>' . esc_html($search) . '</strong>'); ?>
            <a href="<?php echo esc_url(remove_query_arg('s')); ?>"><?php esc_html_e('Clear search', 'gymnastics-patterns'); ?></a>
        </p>
    <?php endif; ?>

    <!-- Search box -->
    <form method="get" class="search-form wp-clearfix">
        <?php
        // Preserve query args
        foreach ($_GET as $key => $value) {
            if ('s' === $key || 'paged' === $key) continue;
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="pattern-search-input"><?php esc_html_e('Search Patterns:', 'gymnastics-patterns'); ?></label>
            <input type="search" id="pattern-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Patterns', 'gymnastics-patterns'); ?>">
        </p>
    </form>

    <!-- Bulk actions (optional) -->
    <form method="post" id="patterns-filter">
        <?php wp_nonce_field('gympat_admin_bulk_action', 'gympat_bulk_nonce'); ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'gymnastics-patterns'); ?></label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php esc_html_e('Bulk actions', 'gymnastics-patterns'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete permanently', 'gymnastics-patterns'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'gymnastics-patterns'); ?>">
            </div>

            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base'    => add_query_arg('paged', '%#%'),
                    'format'  => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'   => $total_pages,
                    'current' => $current_page,
                ));
                ?>
                <span class="displaying-num"><?php echo esc_html(sprintf(_n('%s item', '%s items', $total, 'gymnastics-patterns'), number_format_i18n($total))); ?></span>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list patterns">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-primary"><?php esc_html_e('Pattern Name', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('User', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Gymnast', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Created', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Last Updated', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('PDF', 'gymnastics-patterns'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($patterns)): ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No patterns found.', 'gymnastics-patterns'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patterns as $pattern):
                        $user_info = get_userdata($pattern['user_id']);
                        $user_display = $user_info ? $user_info->display_name : __('Unknown', 'gymnastics-patterns');
                        $params = json_decode($pattern['parameters'], true);
                        $gymnast_name = isset($params['gymnast_name']) ? $params['gymnast_name'] : '';
                        $gymnast_age  = isset($params['gymnast_age']) ? $params['gymnast_age'] : '';
                        $gymnast_info = $gymnast_name ? sprintf('%s (%d)', $gymnast_name, $gymnast_age) : '—';
                        $pdf_url = $pattern['pdf_url'];
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="patterns[]" value="<?php echo esc_attr($pattern['id']); ?>">
                            </th>
                            <td class="column-primary" data-colname="<?php esc_attr_e('Pattern Name', 'gymnastics-patterns'); ?>">
                                <strong>
                                    <a href="#" class="row-title gympat-edit-pattern" data-id="<?php echo esc_attr($pattern['id']); ?>">
                                        <?php echo esc_html($pattern['pattern_name']); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="gympat-edit-pattern" data-id="<?php echo esc_attr($pattern['id']); ?>"><?php esc_html_e('Edit', 'gymnastics-patterns'); ?></a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="gympat-delete-pattern" data-id="<?php echo esc_attr($pattern['id']); ?>"><?php esc_html_e('Delete', 'gymnastics-patterns'); ?></a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php esc_html_e('View PDF', 'gymnastics-patterns'); ?></a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'gymnastics-patterns'); ?></span></button>
                            </td>
                            <td data-colname="<?php esc_attr_e('User', 'gymnastics-patterns'); ?>">
                                <?php echo esc_html($user_display); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Gymnast', 'gymnastics-patterns'); ?>">
                                <?php echo esc_html($gymnast_info); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Created', 'gymnastics-patterns'); ?>">
                                <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $pattern['created_at'])); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('Last Updated', 'gymnastics-patterns'); ?>">
                                <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $pattern['updated_at'])); ?>
                            </td>
                            <td data-colname="<?php esc_attr_e('PDF', 'gymnastics-patterns'); ?>">
                                <?php if ($pdf_url): ?>
                                    <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="button button-small"><?php esc_html_e('Download', 'gymnastics-patterns'); ?></a>
                                <?php else: ?>
                                    <span aria-hidden="true">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-primary"><?php esc_html_e('Pattern Name', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('User', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Gymnast', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Created', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('Last Updated', 'gymnastics-patterns'); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e('PDF', 'gymnastics-patterns'); ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'gymnastics-patterns'); ?></label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php esc_html_e('Bulk actions', 'gymnastics-patterns'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete permanently', 'gymnastics-patterns'); ?></option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e('Apply', 'gymnastics-patterns'); ?>">
            </div>
            <div class="tablenav-pages">
                <?php echo paginate_links(array(
                    'base'    => add_query_arg('paged', '%#%'),
                    'format'  => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'   => $total_pages,
                    'current' => $current_page,
                )); ?>
                <span class="displaying-num"><?php echo esc_html(sprintf(_n('%s item', '%s items', $total, 'gymnastics-patterns'), number_format_i18n($total))); ?></span>
            </div>
            <br class="clear">
        </div>
    </form>
</div>

<!-- Inline script for admin actions -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Delete pattern handler
    $('.gympat-delete-pattern').on('click', function(e) {
        e.preventDefault();
        var $link = $(this);
        var patternId = $link.data('id');
        var $row = $link.closest('tr');

        if (!patternId) return;

        if (!confirm(<?php echo json_encode(__('Are you sure you want to delete this pattern? This cannot be undone.', 'gymnastics-patterns')); ?>)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gymnastics_delete_pattern',
                nonce: <?php echo json_encode(wp_create_nonce('gympat_admin_nonce')); ?>,
                pattern_id: patternId
            },
            beforeSend: function() {
                $link.text(<?php echo json_encode(__('Deleting...', 'gymnastics-patterns')); ?>);
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Update count
                        var count = $('.wp-list-table tbody tr').length;
                        $('.displaying-num').text(count + ' ' + (count === 1 ? <?php echo json_encode(__('item', 'gymnastics-patterns')); ?> : <?php echo json_encode(__('items', 'gymnastics-patterns')); ?>));
                    });
                } else {
                    alert(response.data || <?php echo json_encode(__('Error deleting pattern.', 'gymnastics-patterns')); ?>);
                    $link.text(<?php echo json_encode(__('Delete', 'gymnastics-patterns')); ?>);
                }
            },
            error: function() {
                alert(<?php echo json_encode(__('Server error. Please try again.', 'gymnastics-patterns')); ?>);
                $link.text(<?php echo json_encode(__('Delete', 'gymnastics-patterns')); ?>);
            }
        });
    });

    // Bulk delete action
    $('#doaction, #doaction2').on('click', function(e) {
        var $button = $(this);
        var $form = $button.closest('form');
        var action = $button.prev('select').val() || $button.prev().prev('select').val(); // hack for top/bottom
        if (action !== 'delete') return;

        var $checkboxes = $form.find('input[name="patterns[]"]:checked');
        if ($checkboxes.length === 0) {
            alert(<?php echo json_encode(__('Please select at least one pattern.', 'gymnastics-patterns')); ?>);
            e.preventDefault();
            return;
        }

        if (!confirm(<?php echo json_encode(__('Are you sure you want to delete the selected patterns?', 'gymnastics-patterns')); ?>)) {
            e.preventDefault();
        }
    });

    // Select all checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        $('input[name="patterns[]"]').prop('checked', this.checked);
    });
});
</script>

<style>
    .patterns .column-primary .row-actions {
        visibility: hidden;
    }
    .patterns tr:hover .row-actions {
        visibility: visible;
    }
    .patterns .toggle-row {
        display: none;
    }
    @media screen and (max-width: 782px) {
        .patterns .column-primary {
            position: relative;
        }
        .patterns .toggle-row {
            display: inline-block;
            position: absolute;
            right: 8px;
            top: 8px;
        }
    }
</style>
