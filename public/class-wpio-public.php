<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Handles content replacement to serve WebP and AVIF images
 * when browsers support them.
 *
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Plugin settings.
     */
    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = get_option('wpio_settings', []);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // No styles needed at this time
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue script if WebP or AVIF is enabled
        if (
            (isset($this->settings['enable_webp']) && $this->settings['enable_webp']) ||
            (isset($this->settings['enable_avif']) && $this->settings['enable_avif'])
        ) {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/wpio-public.js',
                array(),
                $this->version,
                true
            );
        }
    }

    /**
     * Replace image URLs in content with WebP/AVIF versions if available.
     *
     * @since    1.0.0
     * @param    string    $content    The content to replace images in.
     * @return   string                The content with replaced image URLs.
     */
    public function replace_image_urls($content) {
        // Only process if WebP or AVIF is enabled
        if (
            (!isset($this->settings['enable_webp']) || !$this->settings['enable_webp']) &&
            (!isset($this->settings['enable_avif']) || !$this->settings['enable_avif'])
        ) {
            return $content;
        }

        // Check if browser supports WebP via Accept header
        $supports_webp = false;
        $supports_avif = false;

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $accepts = explode(',', $_SERVER['HTTP_ACCEPT']);
            foreach ($accepts as $accept) {
                if (strpos($accept, 'image/webp') !== false) {
                    $supports_webp = true;
                }
                if (strpos($accept, 'image/avif') !== false) {
                    $supports_avif = true;
                }
            }
        }

        // If no modern format is supported, return content as is
        if (!$supports_webp && !$supports_avif) {
            return $content;
        }

        // Use AVIF if supported and enabled, otherwise use WebP
        $target_format = '';
        if ($supports_avif && isset($this->settings['enable_avif']) && $this->settings['enable_avif']) {
            $target_format = 'avif';
        } elseif ($supports_webp && isset($this->settings['enable_webp']) && $this->settings['enable_webp']) {
            $target_format = 'webp';
        }

        // If no target format, return content as is
        if (empty($target_format)) {
            return $content;
        }

        // Use regex to find all img tags and replace src attributes
        $pattern = '/<img([^>]+)src=(["\'])([^"\']+)\.([a-zA-Z]{3,4})(["\'])([^>]*)>/i';

        $content = preg_replace_callback($pattern, function($matches) use ($target_format) {
            // original parts: <img{$1}src={$2}{$3}.{$4}{$5}{$6}>
            $before_src = $matches[1];
            $quote_start = $matches[2];
            $img_url = $matches[3];
            $img_ext = strtolower($matches[4]);
            $quote_end = $matches[5];
            $after_src = $matches[6];

            // Only replace jpg, jpeg, and png images
            if (!in_array($img_ext, ['jpg', 'jpeg', 'png'])) {
                return $matches[0]; // Return original if not a format we handle
            }

            // Build the new URL with WebP/AVIF extension
            $new_url = $img_url . '.' . $target_format;

            // Check if the file exists (for local images)
            $site_url = site_url();
            $upload_dir = wp_upload_dir();
            
            // Only perform file existence check for images in the uploads directory
            if (strpos($img_url, $site_url) === 0 && strpos($img_url, $upload_dir['baseurl']) !== false) {
                $file_path = str_replace(
                    $upload_dir['baseurl'],
                    $upload_dir['basedir'],
                    $img_url . '.' . $target_format
                );
                
                if (!file_exists($file_path)) {
                    return $matches[0]; // Return original if WebP/AVIF doesn't exist
                }
            }

            // Return the modified img tag
            return "<img{$before_src}src={$quote_start}{$new_url}{$quote_end}{$after_src}>";
        }, $content);

        return $content;
    }

    /**
     * Add content filter for image replacement.
     *
     * @since    1.0.0
     */
    public function add_content_filters() {
        add_filter('the_content', array($this, 'replace_image_urls'));
        add_filter('post_thumbnail_html', array($this, 'replace_image_urls'));
        add_filter('widget_text_content', array($this, 'replace_image_urls'));
    }

    /**
     * Add support for srcset in responsive images.
     *
     * @since    1.0.0
     * @param    array     $sources    Image sources array with URLs and widths.
     * @return   array                 Modified sources array.
     */
    public function webp_srcset_filter($sources) {
        if (empty($sources)) {
            return $sources;
        }

        // Only process if WebP or AVIF is enabled
        if (
            (!isset($this->settings['enable_webp']) || !$this->settings['enable_webp']) &&
            (!isset($this->settings['enable_avif']) || !$this->settings['enable_avif'])
        ) {
            return $sources;
        }
        
        // Check if browser supports WebP via Accept header
        $supports_webp = false;
        $supports_avif = false;

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $accepts = explode(',', $_SERVER['HTTP_ACCEPT']);
            foreach ($accepts as $accept) {
                if (strpos($accept, 'image/webp') !== false) {
                    $supports_webp = true;
                }
                if (strpos($accept, 'image/avif') !== false) {
                    $supports_avif = true;
                }
            }
        }

        // If no modern format is supported, return sources as is
        if (!$supports_webp && !$supports_avif) {
            return $sources;
        }

        // Determine which format to use (prefer AVIF over WebP)
        $target_format = '';
        if ($supports_avif && isset($this->settings['enable_avif']) && $this->settings['enable_avif']) {
            $target_format = 'avif';
        } elseif ($supports_webp && isset($this->settings['enable_webp']) && $this->settings['enable_webp']) {
            $target_format = 'webp';
        }

        // If no target format, return sources as is
        if (empty($target_format)) {
            return $sources;
        }

        $upload_dir = wp_upload_dir();
        
        foreach ($sources as &$source) {
            $original_url = $source['url'];
            
            // Only process JPG, JPEG and PNG files
            if (!preg_match('/\.(jpe?g|png)$/i', $original_url)) {
                continue;
            }
            
            // Create WebP/AVIF URL
            $new_url = preg_replace('/\.(jpe?g|png)$/i', '.' . $target_format, $original_url);
            
            if ($new_url !== $original_url) {
                // Check if the file exists (file existence check for local paths)
                $file_path = '';
                
                if (strpos($original_url, $upload_dir['baseurl']) === 0) {
                    $file_path = str_replace(
                        $upload_dir['baseurl'],
                        $upload_dir['basedir'],
                        $new_url
                    );
                }
                
                // Only use the WebP/AVIF version if the file actually exists
                if (!empty($file_path) && file_exists($file_path)) {
                    $source['url'] = $new_url;
                    // Update the mime type as well
                    if ($target_format === 'webp') {
                        $source['mime-type'] = 'image/webp';
                    } elseif ($target_format === 'avif') {
                        $source['mime-type'] = 'image/avif';
                    }
                }
            }
        }

        return $sources;
    }
    
    /**
     * Modify admin image preview URLs to show WebP/AVIF versions.
     * 
     * @since    1.0.0
     * @param    string    $html       HTML output for the image preview.
     * @param    int       $post_id    Attachment ID.
     * @return   string                Modified HTML with WebP/AVIF URLs when available.
     */
    public function modify_admin_attachment_preview($html, $post_id) {
        // Handle different function signatures for different filters
        if (!is_numeric($post_id)) {
            // For wp_get_attachment_image filter, args are different
            if (func_num_args() >= 6) {
                // Called from get_image_tag
                $html = func_get_arg(0);
                $post_id = func_get_arg(1);
            } else {
                return $html;
            }
        }
        
        // Only process if WebP or AVIF is enabled
        if (
            (!isset($this->settings['enable_webp']) || !$this->settings['enable_webp']) &&
            (!isset($this->settings['enable_avif']) || !$this->settings['enable_avif'])
        ) {
            return $html;
        }
        
        // Check if the attachment is an image
        if (!wp_attachment_is_image($post_id)) {
            return $html;
        }
        
        // Check if WebP or AVIF version exists
        $original_file = get_attached_file($post_id);
        if (!$original_file || !file_exists($original_file)) {
            return $html;
        }
        
        // Only proceed for JPG/JPEG/PNG files
        if (!preg_match('/\.(jpe?g|png)$/i', $original_file)) {
            return $html;
        }
        
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $original_file);
        $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $original_file);
        
        // Determine which modern format to use
        $modern_path = '';
        $modern_format = '';
        
        if (isset($this->settings['enable_avif']) && $this->settings['enable_avif'] && file_exists($avif_path)) {
            $modern_path = $avif_path;
            $modern_format = 'avif';
        } elseif (isset($this->settings['enable_webp']) && $this->settings['enable_webp'] && file_exists($webp_path)) {
            $modern_path = $webp_path;
            $modern_format = 'webp';
        }
        
        // If no modern format exists, return HTML as-is
        if (empty($modern_path) || empty($modern_format)) {
            return $html;
        }
        
        // Get URLs for the files
        $upload_dir = wp_upload_dir();
        $original_url = wp_get_attachment_url($post_id);
        $modern_url = preg_replace('/\.(jpe?g|png)$/i', '.' . $modern_format, $original_url);
        
        // For the standard media library preview
        if (strpos($html, 'wp-post-image') !== false || strpos($html, 'attachment-') !== false) {
            // Replace src attributes in the HTML
            $html = preg_replace('/src=(["\'])([^"\']+)\.(jpe?g|png)(["\'])/i', 'src=$1$2.' . $modern_format . '$4', $html);
            
            // Add button to toggle between formats
            $button_html = '<div class="wpio-format-toggle">';
            $button_html .= '<button type="button" class="button wpio-toggle-format" data-original="' . esc_attr($original_url) . '" data-modern="' . esc_attr($modern_url) . '" data-format="' . esc_attr(strtoupper($modern_format)) . '">';
            $button_html .= sprintf(__('Toggle %s/Original', 'weirdopress-image-optimizer'), strtoupper($modern_format));
            $button_html .= '</button>';
            $button_html .= '</div>';
            
            // Add toggle button after the image
            $html = preg_replace('/(class="[^"]*(?:wp-post-image|attachment-)[^"]*"[^>]*>)/i', '$1' . $button_html, $html);
        }
        
        return $html;
    }

    /**
     * Filter for the admin media library attachment data
     * 
     * @since    1.0.0
     * @param    array     $response    The attachment data
     * @param    object    $attachment  The attachment object
     * @param    array     $meta        The attachment metadata
     * @return   array                  Modified attachment data
     */
    public function modify_admin_attachment_for_js($response, $attachment, $meta) {
        // Only process images
        if (!isset($response['type']) || $response['type'] !== 'image') {
            return $response;
        }
        
        // Only process if WebP or AVIF is enabled
        if (
            (!isset($this->settings['enable_webp']) || !$this->settings['enable_webp']) &&
            (!isset($this->settings['enable_avif']) || !$this->settings['enable_avif'])
        ) {
            return $response;
        }
        
        // Check if WebP or AVIF version exists
        $original_file = get_attached_file($attachment->ID);
        if (!$original_file || !file_exists($original_file)) {
            return $response;
        }
        
        // Only proceed for JPG/JPEG/PNG files
        if (!preg_match('/\.(jpe?g|png)$/i', $original_file)) {
            return $response;
        }
        
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $original_file);
        $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $original_file);
        
        // Check which formats exist
        $has_webp = file_exists($webp_path);
        $has_avif = file_exists($avif_path);
        
        // Add optimization info to the response
        $response['optimized'] = false;
        
        if ($has_webp || $has_avif) {
            $response['optimized'] = true;
            $response['optimizedFormats'] = [];
            
            if ($has_webp) {
                $response['optimizedFormats'][] = 'WebP';
            }
            if ($has_avif) {
                $response['optimizedFormats'][] = 'AVIF';
            }
            
            // Use the preferred format for URLs
            $preferred_format = $has_avif ? 'avif' : 'webp';
            $preferred_path = $has_avif ? $avif_path : $webp_path;
            
            // Get URLs
            $original_url = $response['url'];
            $modern_url = preg_replace('/\.(jpe?g|png)$/i', '.' . $preferred_format, $original_url);
            
            // Calculate size savings
            $original_size = filesize($original_file);
            $optimized_size = filesize($preferred_path);
            $saved_bytes = $original_size - $optimized_size;
            $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 1) : 0;
            
            // Add optimization data
            $response['originalSize'] = $original_size;
            $response['optimizedSize'] = $optimized_size;
            $response['savedBytes'] = $saved_bytes;
            $response['savedPercent'] = $saved_percent;
            
            // Replace URLs with WebP/AVIF versions
            if (isset($response['url'])) {
                $response['originalUrl'] = $response['url'];
                $response['url'] = $modern_url;
            }
            
            // Also replace sizes URLs
            if (isset($response['sizes']) && is_array($response['sizes'])) {
                foreach ($response['sizes'] as $size => &$size_data) {
                    if (isset($size_data['url'])) {
                        $size_data['originalUrl'] = $size_data['url'];
                        $size_data['url'] = preg_replace('/\.(jpe?g|png)$/i', '.' . $preferred_format, $size_data['url']);
                    }
                }
            }
        }
        
        return $response;
    }

    /**
     * Add custom CSS to admin head for WebP toggle button styling.
     * 
     * @since    1.0.0
     */
    public function add_admin_styles() {
        echo '<style>
        .wpio-format-toggle {
            margin-top: 10px;
            text-align: center;
        }
        .wpio-toggle-format {
            margin-bottom: 10px !important;
        }
        </style>';
    }

    /**
     * Add custom JavaScript to admin footer for WebP toggle functionality.
     * 
     * @since    1.0.0
     */
    public function add_admin_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
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
                                nonce: '<?php echo wp_create_nonce('wpio-check-formats'); ?>'
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
        </script>
        <?php
    }
    
    /**
     * AJAX handler to check if an attachment has WebP/AVIF versions.
     * 
     * @since    1.0.0
     */
    public function check_modern_formats_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpio-check-formats')) {
            wp_send_json_error(['message' => 'Invalid security token.']);
            return;
        }
        
        // Get attachment ID
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID.']);
            return;
        }
        
        // Check if this is an image
        if (!wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => 'Not an image.']);
            return;
        }
        
        // Get original file path
        $original_file = get_attached_file($attachment_id);
        if (!$original_file) {
            wp_send_json_error(['message' => 'Could not get attachment file.']);
            return;
        }
        
        // Check for WebP and AVIF versions
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_file);
        $avif_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.avif', $original_file);
        
        $has_webp = file_exists($webp_path);
        $has_avif = file_exists($avif_path);
        
        // If neither exists, return error
        if (!$has_webp && !$has_avif) {
            wp_send_json_error(['message' => 'No modern formats available.']);
            return;
        }
        
        // Determine which format to use (prefer AVIF over WebP)
        $modern_format = $has_avif ? 'avif' : 'webp';
        $modern_path = $has_avif ? $avif_path : $webp_path;
        
        // Get URLs
        $original_url = wp_get_attachment_url($attachment_id);
        $modern_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.' . $modern_format, $original_url);
        
        // Send success response
        wp_send_json_success([
            'has_modern' => true,
            'format' => $modern_format,
            'original_url' => $original_url,
            'modern_url' => $modern_url
        ]);
    }
} 