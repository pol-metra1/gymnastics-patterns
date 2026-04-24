/**
 * My Patterns – управление списком выкроек пользователя
 *
 * @package GymnasticsPatterns
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        var ajaxUrl = gympat_my_patterns.ajax_url;
        var nonce = gympat_my_patterns.nonce;
        var formPageUrl = gympat_my_patterns.form_page_url;
        var confirmDeleteText = gympat_my_patterns.i18n.confirm_delete || 'Вы уверены?';
        var deleteErrorText = gympat_my_patterns.i18n.delete_error || 'Ошибка при удалении.';
        var serverErrorText = gympat_my_patterns.i18n.server_error || 'Ошибка сервера.';

        // Поиск по таблице
        $('#gympat-search').on('keyup', function () {
            var term = $(this).val().toLowerCase();
            $('tbody tr').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(term) > -1);
            });
        });

        // Сортировка
        $(document).on('click', '.gympat-sortable', function () {
            var column = $(this).data('sort');
            var $table = $(this).closest('table');
            var $tbody = $table.find('tbody');
            var rows = $tbody.find('tr').get();
            var isAsc = $(this).hasClass('sorted-asc');

            rows.sort(function (a, b) {
                var aVal, bVal;
                switch (column) {
                    case 'name':
                        aVal = $(a).find('.pattern-name').text();
                        bVal = $(b).find('.pattern-name').text();
                        break;
                    case 'gymnast':
                        aVal = $(a).find('.pattern-gymnast').text();
                        bVal = $(b).find('.pattern-gymnast').text();
                        break;
                    case 'date':
                        aVal = $(a).find('.pattern-date').text();
                        bVal = $(b).find('.pattern-date').text();
                        break;
                    default:
                        return 0;
                }
                if (aVal < bVal) return isAsc ? -1 : 1;
                if (aVal > bVal) return isAsc ? 1 : -1;
                return 0;
            });

            $table.find('.gympat-sortable').removeClass('sorted-asc sorted-desc');
            $(this).addClass(isAsc ? 'sorted-desc' : 'sorted-asc');
            $.each(rows, function (_, row) { $tbody.append(row); });
        });

        // Удаление
        $(document).on('click', '.gympat-delete', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var patternId = $btn.data('id');

            if (!confirm(confirmDeleteText)) return;

            $btn.prop('disabled', true).text('...');

            $.post(ajaxUrl, {
                action: 'gymnastics_delete_pattern',
                nonce: nonce,
                pattern_id: patternId
            })
            .done(function (response) {
                if (response.success) {
                    $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                } else {
                    alert(response.data || deleteErrorText);
                    $btn.prop('disabled', false).text(gympat_my_patterns.i18n.delete || 'Удалить');
                }
            })
            .fail(function () {
                alert(serverErrorText);
                $btn.prop('disabled', false).text(gympat_my_patterns.i18n.delete || 'Удалить');
            });
        });

        // Редактирование – сохраняем ID в sessionStorage и переходим на форму
        $(document).on('click', '.gympat-edit', function (e) {
            e.preventDefault();
            var patternId = $(this).data('id');
            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('gympat_edit_id', patternId);
            }
            window.location.href = formPageUrl + '?edit=' + patternId;
        });
    });
})(jQuery);
