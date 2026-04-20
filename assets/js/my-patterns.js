/**
 * Gymnastics Pattern Generator - My Patterns Page Scripts
 * Handles search, delete, edit, and download actions on the My Patterns page.
 *
 * @package GymnasticsPatterns
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {

        // Variables
        var $searchInput = $('#gympat-search');
        var $patternsTable = $('.gympat-my-patterns table tbody');
        var $rows = $patternsTable.find('tr');
        var deleteButtons = '.gympat-delete';
        var editButtons = '.gympat-edit';
        var downloadButtons = '.gympat-download';
        var nonce = gympat_my_patterns?.nonce || '';
        var ajaxUrl = gympat_my_patterns?.ajax_url || '';

        // Search functionality
        if ($searchInput.length) {
            $searchInput.on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $rows.each(function() {
                    var $row = $(this);
                    var text = $row.text().toLowerCase();
                    if (text.indexOf(searchTerm) > -1) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                });
            });
        }

        // Delete pattern handler
        $(document).on('click', deleteButtons, function(e) {
            e.preventDefault();
            var $button = $(this);
            var patternId = $button.data('id');
            var $row = $button.closest('tr');

            if (!patternId) {
                alert(gympat_my_patterns?.i18n?.error || 'Error: Pattern ID not found.');
                return;
            }

            if (!confirm(gympat_my_patterns?.i18n?.confirm_delete || 'Are you sure you want to delete this pattern?')) {
                return;
            }

            // Show loading state
            $button.prop('disabled', true).text(gympat_my_patterns?.i18n?.deleting || 'Deleting...');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gymnastics_delete_pattern',
                    nonce: nonce,
                    pattern_id: patternId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row with fade effect
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Show message if no patterns left
                            if ($patternsTable.find('tr').length === 0) {
                                $patternsTable.html('<tr><td colspan="4" class="gympat-text-center">' + 
                                    (gympat_my_patterns?.i18n?.no_patterns || 'No patterns found.') + 
                                    '</td></tr>');
                            }
                        });
                    } else {
                        alert(response.data || gympat_my_patterns?.i18n?.delete_error || 'Error deleting pattern.');
                        $button.prop('disabled', false).text(gympat_my_patterns?.i18n?.delete || 'Delete');
                    }
                },
                error: function() {
                    alert(gympat_my_patterns?.i18n?.ajax_error || 'Server error. Please try again.');
                    $button.prop('disabled', false).text(gympat_my_patterns?.i18n?.delete || 'Delete');
                }
            });
        });

        // Edit pattern handler (redirect to form with pre-filled data)
        $(document).on('click', editButtons, function(e) {
            e.preventDefault();
            var $button = $(this);
            var patternId = $button.data('id');

            if (!patternId) return;

            // Show loading
            var originalText = $button.text();
            $button.prop('disabled', true).text(gympat_my_patterns?.i18n?.loading || 'Loading...');

            // Fetch pattern data via AJAX
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gymnastics_get_pattern_data',
                    nonce: nonce,
                    pattern_id: patternId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var pattern = response.data;
                        // Store parameters in sessionStorage to prefill form
                        if (typeof sessionStorage !== 'undefined') {
                            sessionStorage.setItem('gympat_edit_pattern', JSON.stringify({
                                id: patternId,
                                name: pattern.pattern_name,
                                params: pattern.parameters
                            }));
                        }
                        // Redirect to pattern form page (defined in localized script)
                        var formPageUrl = gympat_my_patterns?.form_page_url || window.location.origin + '/pattern-form/';
                        window.location.href = formPageUrl + '?edit=' + patternId;
                    } else {
                        alert(response.data || gympat_my_patterns?.i18n?.load_error || 'Could not load pattern data.');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(gympat_my_patterns?.i18n?.ajax_error || 'Server error.');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Handle download (just a normal link, but could track clicks)
        // This is optional; the link already works. But we can add analytics if needed.

        // Sorting functionality (if we want client-side sorting)
        var sortConfig = {
            column: 2, // Default: Date column (0: Name, 1: Gymnast, 2: Date, 3: Actions)
            direction: 'desc'
        };

        function sortTable(columnIndex) {
            var rows = $patternsTable.find('tr').get();
            var isAsc = sortConfig.column === columnIndex && sortConfig.direction === 'asc';
            
            rows.sort(function(a, b) {
                var aVal = $(a).find('td').eq(columnIndex).text().trim();
                var bVal = $(b).find('td').eq(columnIndex).text().trim();
                
                // For date column, convert to timestamp
                if (columnIndex === 2) {
                    aVal = new Date(aVal).getTime() || 0;
                    bVal = new Date(bVal).getTime() || 0;
                    return isAsc ? aVal - bVal : bVal - aVal;
                }
                
                // For text columns
                if (aVal < bVal) return isAsc ? -1 : 1;
                if (aVal > bVal) return isAsc ? 1 : -1;
                return 0;
            });
            
            // Update direction
            sortConfig.direction = isAsc ? 'desc' : 'asc';
            sortConfig.column = columnIndex;
            
            // Re-append sorted rows
            $.each(rows, function(index, row) {
                $patternsTable.append(row);
            });
            
            // Update header indicators
            updateSortIndicators();
        }

        function updateSortIndicators() {
            $('.gympat-sortable').removeClass('sorted-asc sorted-desc');
            var $header = $('.gympat-sortable').eq(sortConfig.column);
            $header.addClass(sortConfig.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
        }

        // Make table headers sortable if they have class gympat-sortable
        $('.gympat-sortable').on('click', function() {
            var columnIndex = $(this).index();
            sortTable(columnIndex);
        });

        // Initialize any popup edit form if needed (e.g., modal)
        // For simplicity, we just redirect.

        // Handle any messages from session (e.g., after save)
        if (typeof sessionStorage !== 'undefined') {
            var message = sessionStorage.getItem('gympat_message');
            if (message) {
                var $messageDiv = $('<div class="gympat-message gympat-message-success">').text(message);
                $('.gympat-my-patterns').prepend($messageDiv);
                sessionStorage.removeItem('gympat_message');
                setTimeout(function() {
                    $messageDiv.fadeOut();
                }, 5000);
            }
        }

        // Prevent double submission on delete/edit
        $(document).on('click', '.gympat-actions-cell button, .gympat-actions-cell a', function(e) {
            // Additional check if already processing
            if ($(this).data('processing')) {
                e.preventDefault();
                return false;
            }
            $(this).data('processing', true);
            setTimeout(function() {
                $(this).data('processing', false);
            }.bind(this), 1000);
        });

    });

})(jQuery);
