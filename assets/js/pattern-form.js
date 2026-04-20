jQuery(document).ready(function($) {
    // Динамическое отображение юбки
    $('#gympat-has-skirt').change(function() {
        $('#gympat-skirt-options').toggle(this.checked);
    });

    // Сохранение параметров
    $('#gympat-save').on('click', function() {
        var formData = $('#gympat-pattern-form').serializeArray();
        var data = {
            action: 'gymnastics_save_pattern',
            nonce: gympat_ajax.nonce,
            params: formData,
            pattern_name: prompt('Enter pattern name') || 'Untitled'
        };
        $.post(gympat_ajax.url, data, function(response) {
            if (response.success) alert('Saved!');
        });
    });

    // Генерация с прогрессом
    $('#gympat-pattern-form').on('submit', function(e) {
        e.preventDefault();
        var $progress = $('#gympat-progress');
        $progress.html('<div class="progress-bar"></div>');
        var formData = $(this).serializeArray();
        var data = {
            action: 'gymnastics_generate_pattern',
            nonce: gympat_ajax.nonce,
            params: formData
        };
        $.post(gympat_ajax.url, data, function(response) {
            if (response.success) {
                $('#gympat-pdf-link').html('<a href="' + response.data.pdf_url + '" target="_blank">Download PDF</a>');
            } else {
                alert('Error: ' + response.data);
            }
            $progress.empty();
        }).fail(function() {
            $progress.html('Error');
        });
    });
});
