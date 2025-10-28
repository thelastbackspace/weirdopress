/**
 * WeirdoPress Image Optimizer Media JavaScript
 * Handles format toggle functionality in media library
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle format toggle button clicks
        $(document).on('click', '.wpio-toggle-format', function() {
            var $button = $(this);
            var $img = $button.closest('.attachment-details').find('img');
            if (!$img.length) {
                $img = $button.closest('div').find('img.wp-post-image');
            }

            if (!$img.length) return;

            var originalSrc = $button.data('original');
            var modernSrc = $button.data('modern');
            var format = $button.data('format');

            // Toggle between original and modern format
            if ($img.attr('src') === modernSrc) {
                $img.attr('src', originalSrc);
                $button.text('Show ' + format);
            } else {
                $img.attr('src', modernSrc);
                $button.text('Show Original');
            }
        });

        // Add toggle button to the attachment details modal if it doesn't exist yet
        $(document).on('click', '.attachment', function() {
            setTimeout(function() {
                var $modal = $('.media-modal');
                if ($modal.length && !$modal.find('.wpio-toggle-format').length) {
                    // Get attachment ID
                    var attachmentId = $modal.find('.attachment-details').data('id');
                    if (!attachmentId) return;

                    // Check if this image has optimized versions via Ajax
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpio_check_modern_formats',
                            attachment_id: attachmentId,
                            nonce: wpio_media_vars.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.has_modern) {
                                var $img = $modal.find('.attachment-details').find('img');
                                if (!$img.length) return;

                                var $toggleBtn = $('<div class="wpio-format-toggle">' +
                                    '<button type="button" class="button wpio-toggle-format" ' +
                                    'data-original="' + response.data.original_url + '" ' +
                                    'data-modern="' + response.data.modern_url + '" ' +
                                    'data-format="' + response.data.format.toUpperCase() + '">' +
                                    'Toggle ' + response.data.format.toUpperCase() + '/Original' +
                                    '</button></div>');

                                $toggleBtn.insertAfter($img);
                            }
                        }
                    });
                }
            }, 500);
        });
    });

})(jQuery);