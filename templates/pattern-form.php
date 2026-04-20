<?php defined('ABSPATH') || exit; ?>
<div id="gympat-form-wrapper" class="gympat-form-container">
    <form id="gympat-pattern-form" method="post">
        <?php wp_nonce_field('gympat_nonce', 'gympat_nonce'); ?>
        <h2><?php _e('Gymnast Information', 'gymnastics-patterns'); ?></h2>
        <div class="gympat-row">
            <label><?php _e('Gymnast Name', 'gymnastics-patterns'); ?> <input type="text" name="gymnast_name" maxlength="100"></label>
            <label><?php _e('Age', 'gymnastics-patterns'); ?> <input type="number" name="gymnast_age" min="3" max="99" step="1"></label>
        </div>

        <h2><?php _e('Measurements (cm)', 'gymnastics-patterns'); ?></h2>
        <div class="gympat-grid-3">
            <!-- Поля из ТЗ -->
            <label><?php _e('Height', 'gymnastics-patterns'); ?> <input type="number" name="height" step="0.1" min="90" max="220" required></label>
            <label><?php _e('Bust', 'gymnastics-patterns'); ?> <input type="number" name="bust" step="0.1" min="30" max="150" required></label>
            <!-- Остальные 30+ полей аналогично -->
        </div>

        <h2><?php _e('Skirt Options', 'gymnastics-patterns'); ?></h2>
        <label><input type="checkbox" name="has_skirt" id="gympat-has-skirt"> <?php _e('Add Skirt', 'gymnastics-patterns'); ?></label>
        <div id="gympat-skirt-options" style="display:none;">
            <label><?php _e('Skirt Type', 'gymnastics-patterns'); ?>
                <select name="skirt_type">
                    <option value="straight"><?php _e('Straight', 'gymnastics-patterns'); ?></option>
                    <option value="circle"><?php _e('Full Circle', 'gymnastics-patterns'); ?></option>
                    <option value="half_circle"><?php _e('Half Circle', 'gymnastics-patterns'); ?></option>
                    <option value="gathered"><?php _e('Gathered', 'gymnastics-patterns'); ?></option>
                    <option value="tiered"><?php _e('Tiered', 'gymnastics-patterns'); ?></option>
                </select>
            </label>
            <label><?php _e('Skirt Length', 'gymnastics-patterns'); ?> <input type="number" name="skirt_length" step="0.1" min="5" max="80"></label>
            <!-- Дополнительные поля в зависимости от типа -->
        </div>

        <div class="gympat-actions">
            <button type="submit" name="generate" class="button-primary"><?php _e('Generate Pattern', 'gymnastics-patterns'); ?></button>
            <button type="button" id="gympat-save"><?php _e('Save Parameters', 'gymnastics-patterns'); ?></button>
            <select id="gympat-load-pattern"></select>
        </div>
        <div id="gympat-progress"></div>
        <div id="gympat-pdf-link"></div>
    </form>
</div>
