<?php
/**
 * Административный шаблон списка всех выкроек.
 *
 * Доступен только пользователям с правом manage_options.
 *
 * @package GymnasticsPatterns
 */
defined('ABSPATH') || exit;

if (!current_user_can('manage_options')) {
    wp_die(__('У вас нет прав для доступа к этой странице.', 'gymnastics-patterns'));
}

$db = \GymPat\Database::instance();
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Получаем общее количество и записи с учётом поиска
$total = $db->count_all_patterns($search);
$patterns = $db->get_all_patterns_admin($per_page, $current_page, $search);
$total_pages = ceil($total / $per_page);

$form_page_url = home_url('/?page_id=188/'); // замените на реальный URL страницы с шорткодом
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Выкройки гимнасток', 'gymnastics-patterns'); ?></h1>
    <hr class="wp-header-end">

    <form method="get" class="search-form">
        <?php
        // Сохраняем другие параметры, кроме поиска и пагинации
        foreach ($_GET as $key => $value) {
            if ($key !== 's' && $key !== 'paged') {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        ?>
        <p class="search-box">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                   placeholder="<?php echo esc_attr__('Поиск по названию или имени', 'gymnastics-patterns'); ?>">
            <input type="submit" class="button" value="<?php echo esc_attr__('Искать', 'gymnastics-patterns'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Название', 'gymnastics-patterns'); ?></th>
                <th><?php echo esc_html__('Пользователь', 'gymnastics-patterns'); ?></th>
                <th><?php echo esc_html__('Гимнастка', 'gymnastics-patterns'); ?></th>
                <th><?php echo esc_html__('Дата обновления', 'gymnastics-patterns'); ?></th>
                <th><?php echo esc_html__('Действия', 'gymnastics-patterns'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patterns)): ?>
                <tr>
                    <td colspan="5"><?php echo esc_html__('Выкроек не найдено.', 'gymnastics-patterns'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($patterns as $p):
                    // Имя пользователя из запроса (LEFT JOIN с таблицей users)
                    $user_display = $p['user_name'] ?: __('Неизвестный', 'gymnastics-patterns');
                    $params = json_decode($p['parameters'], true);
                    $gymnast = ($params['gymnast_name'] ?? '') . (isset($params['gymnast_age']) ? ' (' . $params['gymnast_age'] . ')' : '');
                    $pdf_url = $p['pdf_url'];
                ?>
                    <tr data-pattern-id="<?php echo esc_attr($p['id']); ?>">
                        <td class="column-primary"><?php echo esc_html($p['pattern_name']); ?></td>
                        <td><?php echo esc_html($user_display); ?></td>
                        <td><?php echo esc_html($gymnast ?: '—'); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format'), $p['updated_at'])); ?></td>
                        <td>
                            <?php if ($pdf_url): ?>
                                <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="button button-small">
                                    <?php echo esc_html__('Скачать PDF', 'gymnastics-patterns'); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="button button-small gympat-edit"
                                    data-id="<?php echo esc_attr($p['id']); ?>">
                                <?php echo esc_html__('Редактировать', 'gymnastics-patterns'); ?>
                            </button>
                            <button type="button" class="button button-small gympat-delete"
                                    data-id="<?php echo esc_attr($p['id']); ?>">
                                <?php echo esc_html__('Удалить', 'gymnastics-patterns'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    var nonce = '<?php echo esc_js(wp_create_nonce('gympat_nonce')); ?>';
    var formPageUrl = '<?php echo esc_js($form_page_url); ?>';

    // Удаление выкройки
    $('.gympat-delete').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var id = $btn.data('id');
        if (!confirm('<?php echo esc_js(__('Вы уверены, что хотите удалить эту выкройку?', 'gymnastics-patterns')); ?>')) {
            return;
        }

        $btn.prop('disabled', true).text('...');

        $.post(ajaxurl, {
            action: 'gymnastics_delete_pattern',
            nonce: nonce,
            pattern_id: id
        })
        .done(function (response) {
            if (response.success) {
                $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
            } else {
                alert(response.data || '<?php echo esc_js(__('Ошибка при удалении.', 'gymnastics-patterns')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Удалить', 'gymnastics-patterns')); ?>');
            }
        })
        .fail(function () {
            alert('<?php echo esc_js(__('Ошибка сервера.', 'gymnastics-patterns')); ?>');
            $btn.prop('disabled', false).text('<?php echo esc_js(__('Удалить', 'gymnastics-patterns')); ?>');
        });
    });

    // Редактирование – сохраняем ID в sessionStorage и переходим на форму
    $('.gympat-edit').on('click', function (e) {
        e.preventDefault();
        var patternId = $(this).data('id');
        if (typeof sessionStorage !== 'undefined') {
            sessionStorage.setItem('gympat_edit_id', patternId);
        }
        window.location.href = formPageUrl + '?edit=' + patternId;
    });
});
</script>
