<?php defined('ABSPATH') || exit;
$db = GymPat\Database::instance();
$patterns = $db->get_patterns_by_user(get_current_user_id());
?>
<div class="gympat-my-patterns">
    <h2><?php _e('My Patterns', 'gymnastics-patterns'); ?></h2>
    <input type="text" id="gympat-search" placeholder="<?php _e('Search...', 'gymnastics-patterns'); ?>">
    <table>
        <thead>
            <tr><th><?php _e('Name', 'gymnastics-patterns'); ?></th><th><?php _e('Gymnast', 'gymnastics-patterns'); ?></th><th><?php _e('Date', 'gymnastics-patterns'); ?></th><th><?php _e('Actions', 'gymnastics-patterns'); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($patterns as $p):
                $params = json_decode($p['parameters'], true);
            ?>
            <tr>
                <td><?php echo esc_html($p['pattern_name']); ?></td>
                <td><?php echo esc_html($params['gymnast_name'] ?? ''); ?> (<?php echo esc_html($params['gymnast_age'] ?? ''); ?>)</td>
                <td><?php echo mysql2date(get_option('date_format'), $p['updated_at']); ?></td>
                <td>
                    <a href="<?php echo esc_url($p['pdf_url']); ?>" target="_blank"><?php _e('Download', 'gymnastics-patterns'); ?></a> |
                    <button class="gympat-edit" data-id="<?php echo $p['id']; ?>"><?php _e('Edit', 'gymnastics-patterns'); ?></button> |
                    <button class="gympat-delete" data-id="<?php echo $p['id']; ?>"><?php _e('Delete', 'gymnastics-patterns'); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
