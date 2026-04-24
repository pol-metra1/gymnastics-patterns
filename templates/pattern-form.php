<?php
/**
 * Шаблон формы ввода мерок для генерации выкройки купальника.
 *
 * Содержит более 30 антропометрических полей, секции персональных данных,
 * опциональные блоки для рукава и юбки с динамическим отображением.
 *
 * @package GymnasticsPatterns
 */
defined('ABSPATH') || exit;
?>

<div class="bt"><a class="a" href="/?page_id=195/">Назад</a></div>
<div id="gympat-form-wrapper" class="gympat-form-container">
    <form id="gympat-pattern-form" method="post" novalidate>
        <?php wp_nonce_field('gympat_nonce', 'gympat_nonce'); ?>

        <!-- ==================== Персональные данные ==================== -->
        <fieldset>
            <legend class="legend"><?php echo esc_html__('Информация о гимнастке', 'gymnastics-patterns'); ?></legend>
            <div class="gympat-row">
                <label>
                    <span><?php echo esc_html__('Имя гимнастки', 'gymnastics-patterns'); ?></span>
                    <input type="text" name="gymnast_name" maxlength="100"
                           placeholder="<?php echo esc_attr__('Например, Анна', 'gymnastics-patterns'); ?>">
                </label>
                <label>
                    <span><?php echo esc_html__('Возраст', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="gymnast_age" min="3" max="99" step="1"
                           placeholder="<?php echo esc_attr__('Возраст', 'gymnastics-patterns'); ?>">
                </label>
            </div>
        </fieldset>

        <!-- ==================== Основные мерки (30+ полей) ==================== -->
        <fieldset>
            <legend class="legend"><?php echo esc_html__('Основные мерки (см)', 'gymnastics-patterns'); ?></legend>
            <div class="gympat-grid-3">

                <!-- 1. Рост -->
                <label>
                    <span><?php echo esc_html__('Рост', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="height" step="0.1" min="90" max="220" required
                           placeholder="<?php echo esc_attr__('например, 160', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 2. Обхват груди -->
                <label>
                    <span><?php echo esc_html__('Обхват груди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="bust" step="0.1" min="30" max="150" required
                           placeholder="<?php echo esc_attr__('например, 80', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 3. Обхват под грудью -->
                <label>
                    <span><?php echo esc_html__('Обхват под грудью', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="under_bust" step="0.1" min="20" max="130" required
                           placeholder="<?php echo esc_attr__('например, 70', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 4. Обхват талии -->
                <label>
                    <span><?php echo esc_html__('Обхват талии', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="waist" step="0.1" min="30" max="130" required
                           placeholder="<?php echo esc_attr__('например, 65', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 5. Обхват бёдер -->
                <label>
                    <span><?php echo esc_html__('Обхват бёдер', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="hips" step="0.1" min="30" max="160" required
                           placeholder="<?php echo esc_attr__('например, 90', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 6. Ширина груди -->
                <label>
                    <span><?php echo esc_html__('Ширина груди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="chest_width" step="0.1" min="10" max="60" required
                           placeholder="<?php echo esc_attr__('например, 30', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 7. Ширина спины -->
                <label>
                    <span><?php echo esc_html__('Ширина спины', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="back_width" step="0.1" min="10" max="60" required
                           placeholder="<?php echo esc_attr__('например, 32', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 8. Длина переда до талии -->
                <label>
                    <span><?php echo esc_html__('Длина переда до талии', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="front_waist_length" step="0.1" min="20" max="80" required
                           placeholder="<?php echo esc_attr__('например, 40', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 9. Длина спины до талии -->
                <label>
                    <span><?php echo esc_html__('Длина спины до талии', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="back_waist_length" step="0.1" min="20" max="80" required
                           placeholder="<?php echo esc_attr__('например, 38', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 10. Высота груди -->
                <label>
                    <span><?php echo esc_html__('Высота груди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="bust_height" step="0.1" min="5" max="50" required
                           placeholder="<?php echo esc_attr__('например, 25', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 11. Расстояние между центрами груди -->
                <label>
                    <span><?php echo esc_html__('Расст. между центрами груди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="bust_distance" step="0.1" min="5" max="30" required
                           placeholder="<?php echo esc_attr__('например, 18', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 12. Глубина проймы -->
                <label>
                    <span><?php echo esc_html__('Глубина проймы', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="armhole_depth" step="0.1" min="5" max="40" required
                           placeholder="<?php echo esc_attr__('например, 18', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 13. Обхват плеча (бицепса) -->
                <label>
                    <span><?php echo esc_html__('Обхват плеча (бицепса)', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="arm_circ" step="0.1" min="5" max="60" required
                           placeholder="<?php echo esc_attr__('например, 28', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 14. Длина плеча -->
                <label>
                    <span><?php echo esc_html__('Длина плеча', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="shoulder_length" step="0.1" min="5" max="30" required
                           placeholder="<?php echo esc_attr__('например, 12', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 15. Высота сидения -->
                <label>
                    <span><?php echo esc_html__('Высота сидения', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="seat_height" step="0.1" min="5" max="40" required
                           placeholder="<?php echo esc_attr__('например, 28', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 16. Длина боковая (талия – низ трусов) -->
                <label>
                    <span><?php echo esc_html__('Длина боковая (талия – трусы)', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="side_length" step="0.1" min="5" max="40" required
                           placeholder="<?php echo esc_attr__('например, 15', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 17. Обхват ноги у паха -->
                <label>
                    <span><?php echo esc_html__('Обхват ноги у паха', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="leg_circ" step="0.1" min="20" max="80" required
                           placeholder="<?php echo esc_attr__('например, 55', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 18. Ширина шага -->
                <label>
                    <span><?php echo esc_html__('Ширина шага', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="step_width" step="0.1" min="5" max="20" required
                           placeholder="<?php echo esc_attr__('например, 8', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 19. Длина купальника (плечо – низ трусов) -->
                <label>
                    <span><?php echo esc_html__('Длина купальника (плечо – низ)', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="leotard_length" step="0.1" min="30" max="120" required
                           placeholder="<?php echo esc_attr__('например, 70', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 20. Обхват шеи -->
                <label>
                    <span><?php echo esc_html__('Обхват шеи', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="neck_circ" step="0.1" min="15" max="50" required
                           placeholder="<?php echo esc_attr__('например, 35', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 21. Ширина горловины спереди -->
                <label>
                    <span><?php echo esc_html__('Ширина горловины спереди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="front_neck_width" step="0.1" min="5" max="20" required
                           placeholder="<?php echo esc_attr__('например, 10', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 22. Глубина горловины спереди -->
                <label>
                    <span><?php echo esc_html__('Глубина горловины спереди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="front_neck_depth" step="0.1" min="2" max="15" required
                           placeholder="<?php echo esc_attr__('например, 5', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 23. Ширина горловины сзади -->
                <label>
                    <span><?php echo esc_html__('Ширина горловины сзади', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="back_neck_width" step="0.1" min="5" max="20" required
                           placeholder="<?php echo esc_attr__('например, 12', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 24. Глубина горловины сзади -->
                <label>
                    <span><?php echo esc_html__('Глубина горловины сзади', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="back_neck_depth" step="0.1" min="1" max="10" required
                           placeholder="<?php echo esc_attr__('например, 2', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 25. Длина бокового шва -->
                <label>
                    <span><?php echo esc_html__('Длина бокового шва', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="side_seam_length" step="0.1" min="5" max="40" required
                           placeholder="<?php echo esc_attr__('например, 18', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 26. Обхват талии спереди -->
                <label>
                    <span><?php echo esc_html__('Обхват талии спереди', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="front_waist_circ" step="0.1" min="20" max="150" required
                           placeholder="<?php echo esc_attr__('например, 35', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 27. Обхват талии сзади -->
                <label>
                    <span><?php echo esc_html__('Обхват талии сзади', 'gymnastics-patterns'); ?></span>
                    <input type="number" name="back_waist_circ" step="0.1" min="20" max="150" required
                           placeholder="<?php echo esc_attr__('например, 30', 'gymnastics-patterns'); ?>">
                </label>

                <!-- 28. Полнота груди (выпадающий список) -->
                <label>
                    <span><?php echo esc_html__('Полнота груди', 'gymnastics-patterns'); ?></span>
                    <select name="bust_fullness">
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </label>

                <!-- Дополнительные поля можно добавить через фильтр -->
                <?php do_action('gympat_extra_measurement_fields'); ?>
            </div>
        </fieldset>

        <!-- ==================== Рукав (опционально) ==================== -->
        <fieldset>
            <legend class="legend"><?php echo esc_html__('Рукав', 'gymnastics-patterns'); ?></legend>
            <label class="checkbox-label">
                <input type="checkbox" name="has_sleeve" id="gympat-has-sleeve" value="1">
                <span><?php echo esc_html__('Добавить рукав', 'gymnastics-patterns'); ?></span>
            </label>
            <div id="gympat-sleeve-options">
                <div class="gympat-row">
                    <label>
                        <span><?php echo esc_html__('Длина рукава', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="sleeve_length" step="0.1" min="5" max="60" required
                               placeholder="<?php echo esc_attr__('например, 25', 'gymnastics-patterns'); ?>">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Обхват запястья', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="wrist_circ" step="0.1" min="5" max="30" required
                               placeholder="<?php echo esc_attr__('например, 16', 'gymnastics-patterns'); ?>">
                    </label>
                </div>
            </div>
        </fieldset>

        <!-- ==================== Юбка (опционально) ==================== -->
        <fieldset>
            <legend class="legend"><?php echo esc_html__('Юбка', 'gymnastics-patterns'); ?></legend>
            <label class="checkbox-label">
                <input type="checkbox" name="has_skirt" id="gympat-has-skirt" value="1">
                <span><?php echo esc_html__('Добавить юбку', 'gymnastics-patterns'); ?></span>
            </label>
            <div id="gympat-skirt-options" style="display:none;">
                <div class="gympat-row">
                    <label>
                        <span><?php echo esc_html__('Тип юбки', 'gymnastics-patterns'); ?></span>
                        <select name="skirt_type">
                            <option value="straight"><?php echo esc_html__('Прямая', 'gymnastics-patterns'); ?></option>
                            <option value="circle"><?php echo esc_html__('Солнце', 'gymnastics-patterns'); ?></option>
                            <option value="half_circle"><?php echo esc_html__('Полусолнце', 'gymnastics-patterns'); ?></option>
                            <option value="gathered"><?php echo esc_html__('Татьянка (сборка)', 'gymnastics-patterns'); ?></option>
                            <option value="tiered"><?php echo esc_html__('С воланами (ярусами)', 'gymnastics-patterns'); ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?php echo esc_html__('Длина юбки (см)', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="skirt_length" step="0.1" min="5" max="80"
                               placeholder="<?php echo esc_attr__('например, 40', 'gymnastics-patterns'); ?>">
                    </label>
                </div>
                <div class="gympat-row">
                    <label>
                        <span><?php echo esc_html__('Коэффициент расширения', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="flare_coefficient" step="0.1" min="1" max="3" value="1.2"
                               placeholder="1.0–3.0">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Количество ярусов', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="tiers" min="1" max="3" value="2">
                    </label>
                    <label>
                        <span><?php echo esc_html__('Высота кокетки (см)', 'gymnastics-patterns'); ?></span>
                        <input type="number" name="yoke_height" step="0.1" min="0" max="20" value="0">
                    </label>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="use_skirt_separate_measures" id="use-separate-measures">
                    <span><?php echo esc_html__('Отдельные мерки талии/бёдер для юбки', 'gymnastics-patterns'); ?></span>
                </label>
                <div id="gympat-separate-skirt-measures" style="display:none;">
                    <div class="gympat-row">
                        <label>
                            <span><?php echo esc_html__('Обхват талии для юбки', 'gymnastics-patterns'); ?></span>
                            <input type="number" name="skirt_waist" step="0.1" min="30" max="130"
                                   placeholder="<?php echo esc_attr__('например, 65', 'gymnastics-patterns'); ?>">
                        </label>
                        <label>
                            <span><?php echo esc_html__('Обхват бёдер для юбки', 'gymnastics-patterns'); ?></span>
                            <input type="number" name="skirt_hips" step="0.1" min="30" max="160"
                                   placeholder="<?php echo esc_attr__('например, 92', 'gymnastics-patterns'); ?>">
                        </label>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- ==================== Кнопки действий ==================== -->
        <div class="gympat-actions">
            <button type="submit" name="generate" class="button-primary">
                <?php echo esc_html__('Сгенерировать выкройку', 'gymnastics-patterns'); ?>
            </button>
            <button type="button" id="gympat-save">
                <?php echo esc_html__('Сохранить параметры', 'gymnastics-patterns'); ?>
            </button>
            <select id="gympat-load-pattern">
                <option value=""><?php echo esc_html__('Загрузить...', 'gymnastics-patterns'); ?></option>
            </select>
        </div>

        <div id="gympat-progress" aria-live="polite"></div>
        <div id="gympat-pdf-link"></div>
        <div id="gympat-message" class="gympat-message" style="display:none;"></div>
    </form>
</div>
