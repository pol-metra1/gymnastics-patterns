<?php
/**
 * Шаблон страницы «Мои выкройки»
 *
 * Отображает список сохранённых пользователем выкроек с возможностью
 * поиска, сортировки, скачивания PDF, редактирования и удаления.
 *
 * @package GymnasticsPatterns
 */
defined('ABSPATH') || exit;

$db = \GymPat\Database::instance();
$patterns = $db->get_patterns_by_user(get_current_user_id());

// URL страницы с формой – необходимо указать реальный адрес страницы,
// где размещён шорткод [gymnastics_pattern_form].
$form_page_url = home_url('/?page_id=188/');
?>
<div class="bt"><a class="a" href="/">Выход</a></div>
<div class="btnew"><a class="a" href="/?page_id=188/">Новая выкройка</a></div>
<div class="gympat-my-patterns">
    <h2><?php echo esc_html__('Мои выкройки', 'gymnastics-patterns'); ?></h2>

    <input type="text" id="gympat-search" placeholder="<?php echo esc_attr__('Поиск...', 'gymnastics-patterns'); ?>" autocomplete="off">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th class="gympat-sortable" data-sort="name"><?php echo esc_html__('Название', 'gymnastics-patterns'); ?></th>
                <th class="gympat-sortable" data-sort="gymnast"><?php echo esc_html__('Гимнастка', 'gymnastics-patterns'); ?></th>
                <th class="gympat-sortable" data-sort="date"><?php echo esc_html__('Дата', 'gymnastics-patterns'); ?></th>
                <th><?php echo esc_html__('Действия', 'gymnastics-patterns'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patterns)): ?>
                <tr>
                    <td colspan="4"><?php echo esc_html__('У вас пока нет сохранённых выкроек.', 'gymnastics-patterns'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($patterns as $p):
                    $params = json_decode($p['parameters'], true);
                    $gymnast_name = $params['gymnast_name'] ?? '';
                    $gymnast_age  = $params['gymnast_age'] ?? '';
                    $gymnast_display = $gymnast_name
                        ? $gymnast_name . ($gymnast_age ? ', ' . $gymnast_age . ' ' . __('лет', 'gymnastics-patterns') : '')
                        : '—';
                    $pdf_url = $p['pdf_url'];
                    $created = mysql2date(get_option('date_format'), $p['created_at']);
                    $updated = mysql2date(get_option('date_format'), $p['updated_at']);
                ?>
                    <tr data-pattern-id="<?php echo esc_attr($p['id']); ?>">
                        <td class="pattern-name"><?php echo esc_html($p['pattern_name']); ?></td>
                        <td class="pattern-gymnast"><?php echo esc_html($gymnast_display); ?></td>
                        <td class="pattern-date"><?php echo esc_html($updated); ?> (созд. <?php echo esc_html($created); ?>)</td>
                        <td class="gympat-actions-cell">
                            <?php if ($pdf_url): ?>
                                <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="button button-small gympat-download">
                                    <?php echo esc_html__('Скачать PDF', 'gymnastics-patterns'); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="button button-small gympat-edit" data-id="<?php echo esc_attr($p['id']); ?>">
                                <?php echo esc_html__('Редактировать', 'gymnastics-patterns'); ?>
                            </button>
                            <button type="button" class="button button-small gympat-delete" data-id="<?php echo esc_attr($p['id']); ?>">
                                <?php echo esc_html__('Удалить', 'gymnastics-patterns'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
