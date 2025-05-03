/**
 * WeirdoPress Image Optimizer Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Update the range slider value display
        $('.wpio-range').on('input', function() {
            $(this).next('.wpio-range-value').text($(this).val());
        });
        
        // Make the binary status table sortable
        if ($.fn.sortable && $('.wpio-binary-table').length) {
            $('.wpio-binary-table').sortable({
                items: 'tbody > tr',
                cursor: 'move',
                axis: 'y',
                helper: function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                update: function(event, ui) {
                    // This is just for UI demonstration - we don't need to save the order
                }
            });
        }
        
        // Make the notices dismissible
        $(document).on('click', '.notice-dismiss', function() {
            $(this).closest('.notice').fadeOut();
        });
        
    });
    
})(jQuery); 