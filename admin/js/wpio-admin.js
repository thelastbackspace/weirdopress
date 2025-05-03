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
        
        // Handle optimize single image button
        $(document).on('click', '.wpio-optimize-single', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const attachmentId = button.data('id');
            
            // Disable button and show loading state
            button.prop('disabled', true).text('Optimizing...');
            
            // Send AJAX request to optimize the image
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpio_optimize_single_image',
                    attachment_id: attachmentId,
                    nonce: wpio_admin_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh the column content
                        const column = button.closest('td');
                        column.html('<span class="wpio-badge wpio-badge-optimized">Optimized</span>');
                        
                        // Show success message
                        $('body').append('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p><button type="button" class="notice-dismiss"></button></div>');
                    } else {
                        // Show error message and reset button
                        $('body').append('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p><button type="button" class="notice-dismiss"></button></div>');
                        button.prop('disabled', false).text('Optimize');
                    }
                },
                error: function() {
                    // Show error message and reset button
                    $('body').append('<div class="notice notice-error is-dismissible"><p>Error optimizing image. Please try again.</p><button type="button" class="notice-dismiss"></button></div>');
                    button.prop('disabled', false).text('Optimize');
                }
            });
        });
    });
    
})(jQuery); 