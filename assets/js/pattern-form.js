/**
 * Gymnastics Pattern Generator – Скрипт формы ввода выкройки
 *
 * Обрабатывает:
 * - Динамическое отображение опций рукава и юбки
 * - Валидацию полей на стороне клиента
 * - AJAX-генерацию PDF с индикатором выполнения
 * - Сохранение параметров выкройки
 * - Загрузку ранее сохранённых выкроек из выпадающего списка
 * - Автозагрузку выкройки при наличии параметра edit в URL или sessionStorage
 *
 * @package GymnasticsPatterns
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ==========================================
        // 1. Динамические элементы интерфейса
        // ==========================================

        var $hasSleeve = $('#gympat-has-sleeve');
        var $sleeveOptions = $('#gympat-sleeve-options');

        var $hasSkirt = $('#gympat-has-skirt');
        var $skirtOptions = $('#gympat-skirt-options');

        $hasSleeve.on('change', function () {
            $sleeveOptions.toggle(this.checked);
        }).trigger('change');

        $hasSkirt.on('change', function () {
            $skirtOptions.toggle(this.checked);
        }).trigger('change');

        var $separateMeasuresCheck = $('#use-separate-measures');
        var $separateMeasuresBlock = $('#gympat-separate-skirt-measures');
        if ($separateMeasuresCheck.length) {
            $separateMeasuresCheck.on('change', function () {
                $separateMeasuresBlock.toggle(this.checked);
            }).trigger('change');
        }

        var $skirtType = $('[name="skirt_type"]');
        var $flareCoef = $('[name="flare_coefficient"]').closest('label');
        var $tiers = $('[name="tiers"]').closest('label');

        function toggleSkirtFields() {
            var type = $skirtType.val();
            $flareCoef.toggle(type === 'straight' || type === 'gathered');
            $tiers.toggle(type === 'tiered');
        }
        if ($skirtType.length) {
            $skirtType.on('change', toggleSkirtFields);
            toggleSkirtFields();
        }

        // ==========================================
        // 2. Клиентская валидация
        // ==========================================
        function validateForm() {
            var errors = [];
            $('#gympat-pattern-form [required]:visible').each(function () {
                var $f = $(this);
                var val = $f.val();
                if (!val || val.trim() === '') {
                    var label = $f.siblings('span').text() || $f.closest('label').find('span').text() || $f.attr('name');
                    errors.push(label + ' обязательно.');
                } else {
                    var min = parseFloat($f.attr('min'));
                    var max = parseFloat($f.attr('max'));
                    var num = parseFloat(val);
                    if (!isNaN(min) && !isNaN(max) && (num < min || num > max))
                        errors.push((label || $f.attr('name')) + ' должно быть ' + min + '–' + max + '.');
                }
            });
            return errors;
        }

        function displayErrors(errors) {
            var $msg = $('#gympat-message');
            $msg.removeClass('gympat-message-success').addClass('gympat-message-error')
                .html('<ul><li>' + errors.join('</li><li>') + '</li></ul>').show();
        }

        function clearMessages() { $('#gympat-message').hide().empty(); }

        // ==========================================
        // 3. Генерация выкройки (AJAX)
        // ==========================================
        $('#gympat-pattern-form').on('submit', function (e) {
            e.preventDefault();
            clearMessages();
            var errors = validateForm();
            if (errors.length) { displayErrors(errors); return; }

            var params = {};
            $.each($(this).serializeArray(), function (i, f) {
                if ($.isNumeric(f.value) && f.value !== '') params[f.name] = parseFloat(f.value);
                else if (f.name === 'has_skirt' || f.name === 'has_sleeve' || f.name === 'use_skirt_separate_measures')
                    params[f.name] = f.value === '1' || f.value === 'on';
                else params[f.name] = f.value;
            });
            if (!$hasSkirt.prop('checked')) params.has_skirt = false;
            if (!$hasSleeve.prop('checked')) params.has_sleeve = false;

            var $progress = $('#gympat-progress'), $pdfLink = $('#gympat-pdf-link');
            $progress.html('<div class="gympat-progress-bar"><div class="gympat-progress-fill"></div></div>');
            $pdfLink.empty();
            var $btn = $(this).find('button[type="submit"]').prop('disabled', true).text(gympat_ajax.i18n.generating || 'Генерация...');

            $.ajax({
                url: gympat_ajax.ajax_url, type: 'POST',
                data: { action: 'gymnastics_generate_pattern', nonce: gympat_ajax.nonce, params: params, pattern_id: $('#gympat-load-pattern').val() || 0 },
                success: function (r) {
                    if (r.success && r.data.pdf_url) {
                        $pdfLink.html('<a href="' + r.data.pdf_url + '" target="_blank" class="gympat-download-btn">' + (gympat_ajax.i18n.download_pdf || 'Скачать PDF') + '</a>');
                        if (r.data.cached) $('#gympat-message').removeClass('gympat-message-error').addClass('gympat-message-success').text(gympat_ajax.i18n.cached || 'Из кэша').show();
                    } else displayErrors([r.data || (gympat_ajax.i18n.error || 'Ошибка генерации.')]);
                },
                error: function () { displayErrors([gympat_ajax.i18n.ajax_error || 'Ошибка сервера.']); },
                complete: function () { $progress.empty(); $btn.prop('disabled', false).text(gympat_ajax.i18n.generate || 'Сгенерировать'); }
            });
        });

        // ==========================================
        // 4. Сохранение параметров
        // ==========================================
        $('#gympat-save').on('click', function () {
            clearMessages();
            var name = prompt(gympat_ajax.i18n.pattern_name_prompt || 'Название:');
            if (!name) return;
            var params = {};
            $.each($('#gympat-pattern-form').serializeArray(), function (i, f) {
                if ($.isNumeric(f.value) && f.value !== '') params[f.name] = parseFloat(f.value);
                else if (f.name === 'has_skirt' || f.name === 'has_sleeve' || f.name === 'use_skirt_separate_measures')
                    params[f.name] = f.value === '1' || f.value === 'on';
                else params[f.name] = f.value;
            });
            if (!$hasSkirt.prop('checked')) params.has_skirt = false;
            if (!$hasSleeve.prop('checked')) params.has_sleeve = false;

            $.ajax({
                url: gympat_ajax.ajax_url, type: 'POST',
                data: { action: 'gymnastics_save_pattern', nonce: gympat_ajax.nonce, params: params, pattern_name: name, pattern_id: $('#gympat-load-pattern').val() || 0 },
                success: function (r) {
                    if (r.success) {
                        $('#gympat-message').removeClass('gympat-message-error').addClass('gympat-message-success').text(gympat_ajax.i18n.saved || 'Сохранено').show();
                        loadPatternList(r.data.pattern_id);
                    } else displayErrors([r.data || (gympat_ajax.i18n.save_error || 'Ошибка сохранения.')]);
                },
                error: function () { displayErrors([gympat_ajax.i18n.ajax_error || 'Ошибка сервера.']); }
            });
        });

        // ==========================================
        // 5. Загрузка списка и данных выкройки
        // ==========================================
        function loadPatternList(callback) {
            $.ajax({
                url: gympat_ajax.ajax_url, type: 'POST',
                data: { action: 'gymnastics_get_patterns_list', nonce: gympat_ajax.nonce },
                success: function (response) {
                    if (response.success) {
                        var $select = $('#gympat-load-pattern');
                        $select.empty().append('<option value="">' + (gympat_ajax.i18n.load_saved || 'Загрузить...') + '</option>');
                        $.each(response.data, function (i, p) {
                            var info = '';
                            if (p.gymnast_name) {
                                info = ' (' + p.gymnast_name;
                                if (p.gymnast_age) info += ', ' + p.gymnast_age + ' ' + (gympat_ajax.i18n.years || 'лет');
                                info += ')';
                            }
                            $select.append('<option value="' + p.id + '">' + p.pattern_name + info + '</option>');
                        });
                        if (typeof callback === 'function') callback();
                    }
                }
            });
        }

        function loadPatternData(patternId) {
            if (!patternId) return;
            $.ajax({
                url: gympat_ajax.ajax_url, type: 'POST',
                data: { action: 'gymnastics_get_pattern_data', nonce: gympat_ajax.nonce, pattern_id: patternId },
                success: function (r) {
                    if (r.success) {
                        var params = r.data.parameters;
                        $.each(params, function (key, val) {
                            var $field = $('[name="' + key + '"]');
                            if ($field.length) {
                                if ($field.attr('type') === 'checkbox') $field.prop('checked', val).trigger('change');
                                else if ($field.is('select')) $field.val(val);
                                else $field.val(val);
                            }
                        });
                        $('#gympat-message').removeClass('gympat-message-error').addClass('gympat-message-success')
                            .text(gympat_ajax.i18n.loaded || 'Выкройка загружена.').show();
                    } else {
                        displayErrors([r.data || (gympat_ajax.i18n.load_error || 'Не удалось загрузить.')]);
                    }
                },
                error: function () { displayErrors([gympat_ajax.i18n.ajax_error || 'Ошибка сервера.']); }
            });
        }

        // Обработчик выбора в выпадающем списке
        $('#gympat-load-pattern').on('change', function () {
            var id = $(this).val();
            if (id) loadPatternData(id);
        });

        // ==========================================
        // 6. Инициализация: получаем editId из URL или sessionStorage
        // ==========================================
        var urlParams = new URLSearchParams(window.location.search);
        var editId = urlParams.get('edit');
        console.log('editId из URL:', editId);

        if (!editId && typeof sessionStorage !== 'undefined') {
            editId = sessionStorage.getItem('gympat_edit_id');
            if (editId) {
                console.log('editId из sessionStorage:', editId);
                sessionStorage.removeItem('gympat_edit_id'); // очищаем, чтобы не мешал при следующем входе
            }
        }

        if (editId) {
            // Загружаем список, а потом данные по editId
            loadPatternList(function () {
                $('#gympat-load-pattern').val(editId);
                loadPatternData(editId);
            });
        } else {
            // Просто загружаем список для ручного выбора
            loadPatternList();
        }
    });
})(jQuery);
