<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueuing admin-specific stylesheet and JavaScript,
 * as well as the settings page and options.
 *
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Admin {

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
     * The binary detector instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WPIO_Binary_Detector    $binary_detector    Binary detector instance.
     */
    private $binary_detector;
    
    /**
     * Reference to the main plugin class.
     *
     * @since    1.0.0
     * @access   public
     * @var      WPIO    $plugin    Main plugin instance.
     */
    public $plugin;

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
        $this->binary_detector = new WPIO_Binary_Detector();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();

        // Load on our settings page
        if (isset($screen->id) && $screen->id === 'settings_page_weirdopress-image-optimizer') {
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'css/wpio-admin.css',
                array(),
                $this->version,
                'all'
            );
        }

        // Also load on upload page for media library functionality
        if (isset($screen->id) && ($screen->id === 'upload' || $screen->id === 'attachment')) {
            wp_enqueue_style(
                $this->plugin_name . '-media',
                plugin_dir_url(__FILE__) . 'css/wpio-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();

        // Load on our settings page
        if (isset($screen->id) && $screen->id === 'settings_page_weirdopress-image-optimizer') {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/wpio-admin.js',
                array('jquery', 'jquery-ui-sortable'),
                $this->version,
                false
            );

            // Add variables for our JavaScript
            wp_localize_script(
                $this->plugin_name,
                'wpio_admin_vars',
                array(
                    'nonce' => wp_create_nonce('wpio_nonce'),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                )
            );
        }

        // Also load on upload page for media library functionality
        if (isset($screen->id) && ($screen->id === 'upload' || $screen->id === 'attachment')) {
            wp_enqueue_script(
                $this->plugin_name . '-media',
                plugin_dir_url(__FILE__) . 'js/wpio-media.js',
                array('jquery'),
                $this->version,
                true
            );

            // Add variables for media JavaScript
            wp_localize_script(
                $this->plugin_name . '-media',
                'wpio_media_vars',
                array(
                    'nonce' => wp_create_nonce('wpio-check-formats'),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                )
            );
        }
    }
    
    /**
     * Add settings menu item and page.
     * 
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main settings page
        add_options_page(
            'WeirdoPress Image Optimizer',
            'Image Optimizer',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
        );
        
        // Optimization logs page
        add_submenu_page(
            'upload.php',
            esc_html__('Optimization Logs', 'weirdopress-image-optimizer'),
            esc_html__('Optimization Logs', 'weirdopress-image-optimizer'),
            'manage_options',
            'wpio-logs',
            array($this, 'display_logs_page')
        );
    }
    
    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     * @param    array    $links    Plugin action links
     * @return   array              Modified action links
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . esc_url(admin_url('options-general.php?page=' . $this->plugin_name)) . '">' . esc_html__('Settings', 'weirdopress-image-optimizer') . '</a>',
        );
        return array_merge($settings_link, $links);
    }
    
    /**
     * Register plugin settings.
     * 
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'wpio_settings',
            'wpio_settings',
            array($this, 'validate_settings')
        );
        
        add_settings_section(
            'wpio_general_section',
            esc_html__('General Settings', 'weirdopress-image-optimizer'),
            array($this, 'general_section_callback'),
            'wpio_settings'
        );

        add_settings_field(
            'enable_webp',
            esc_html__('Enable WebP conversion', 'weirdopress-image-optimizer'),
            array($this, 'enable_webp_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'enable_avif',
            esc_html__('Enable AVIF conversion', 'weirdopress-image-optimizer'),
            array($this, 'enable_avif_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'jpeg_quality',
            esc_html__('JPEG Quality', 'weirdopress-image-optimizer'),
            array($this, 'jpeg_quality_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'preserve_originals',
            esc_html__('Preserve original files', 'weirdopress-image-optimizer'),
            array($this, 'preserve_originals_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'auto_replace',
            esc_html__('Auto-replace in media library', 'weirdopress-image-optimizer'),
            array($this, 'auto_replace_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'log_optimizations',
            esc_html__('Log optimization results', 'weirdopress-image-optimizer'),
            array($this, 'log_optimizations_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            esc_html__('Delete data on uninstalling', 'weirdopress-image-optimizer'),
            array($this, 'delete_data_callback'),
            'wpio_settings',
            'wpio_general_section'
        );

        add_settings_section(
            'wpio_bulk_section',
            esc_html__('Bulk Optimization', 'weirdopress-image-optimizer'),
            array($this, 'bulk_section_callback'),
            'wpio_settings'
        );

        add_settings_field(
            'compression_preset',
            esc_html__('Compression Preset', 'weirdopress-image-optimizer'),
            array($this, 'compression_preset_callback'),
            'wpio_settings',
            'wpio_bulk_section'
        );

        add_settings_section(
            'wpio_binary_section',
            esc_html__('Binary Status', 'weirdopress-image-optimizer'),
            array($this, 'binary_section_callback'),
            'wpio_settings'
        );
    }
    
    /**
     * General settings section callback.
     * 
     * @since    1.0.0
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure how WeirdoPress Image Optimizer should process your images.', 'weirdopress-image-optimizer') . '</p>';
    }
    
    /**
     * Binary status section callback.
     * 
     * @since    1.0.0
     */
    public function binary_section_callback() {
        echo '<p>' . esc_html__('The following table shows the status of the binaries needed for various optimization methods.', 'weirdopress-image-optimizer') . '</p>';

        $binaries = $this->binary_detector->check_binaries();

        echo '<table class="widefat wpio-binary-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Binary', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . esc_html__('Status', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . esc_html__('Version', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . esc_html__('Feature', 'weirdopress-image-optimizer') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($binaries as $binary) {
            echo '<tr>';
            echo '<td>' . esc_html($binary['name']) . '</td>';
            echo '<td>' . ($binary['available'] ? '<span class="wpio-status-ok">✓ Available</span>' : '<span class="wpio-status-error">✗ Not Available</span>') . '</td>';
            echo '<td>' . esc_html($binary['version']) . '</td>';
            echo '<td>' . esc_html($binary['feature']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Check for exec() function
        if (!function_exists('exec')) {
            echo '<div class="wpio-warning">';
            echo '<p><strong>' . esc_html__('Warning', 'weirdopress-image-optimizer') . ':</strong> ';
            echo esc_html__('The exec() function is disabled on your server. This plugin will use PHP-only optimization methods, which may not be as efficient as using external binaries. See the Environment Information panel below for more details.', 'weirdopress-image-optimizer');
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Enable WebP option callback.
     * 
     * @since    1.0.0
     */
    public function enable_webp_callback() {
        $settings = get_option('wpio_settings', []);
        $enable_webp = isset($settings['enable_webp']) ? $settings['enable_webp'] : true;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[enable_webp]" value="1" ' . checked(1, $enable_webp, false) . ' />';
        echo esc_html__('Convert images to WebP format', 'weirdopress-image-optimizer');
        echo '</label>';

        if (!$this->binary_detector->is_binary_available('cwebp')) {
            echo '<p class="description wpio-warning">';
            echo esc_html__('Warning: cwebp binary not found. WebP conversion will use GD library as fallback (lower quality).', 'weirdopress-image-optimizer');
            echo '</p>';
        }
    }
    
    /**
     * Enable AVIF option callback.
     * 
     * @since    1.0.0
     */
    public function enable_avif_callback() {
        $settings = get_option('wpio_settings', []);
        $enable_avif = isset($settings['enable_avif']) ? $settings['enable_avif'] : true;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[enable_avif]" value="1" ' . checked(1, $enable_avif, false) . ' />';
        echo esc_html__('Convert images to AVIF format', 'weirdopress-image-optimizer');
        echo '</label>';

        if (!$this->binary_detector->is_binary_available('avifenc')) {
            echo '<p class="description wpio-warning">';
            echo esc_html__('Warning: avifenc binary not found. AVIF conversion will be skipped.', 'weirdopress-image-optimizer');
            echo '</p>';
        }
    }
    
    /**
     * JPEG quality option callback.
     * 
     * @since    1.0.0
     */
    public function jpeg_quality_callback() {
        $settings = get_option('wpio_settings', []);
        $jpeg_quality = isset($settings['jpeg_quality']) ? intval($settings['jpeg_quality']) : 75;

        echo '<input type="range" min="0" max="100" step="1" name="wpio_settings[jpeg_quality]" value="' . esc_attr($jpeg_quality) . '" class="wpio-range" />';
        echo '<span class="wpio-range-value">' . esc_html($jpeg_quality) . '</span>';
        echo '<p class="description">';
        echo esc_html__('Lower quality = smaller file size but reduced image quality. Recommended: 70-85.', 'weirdopress-image-optimizer');
        echo '</p>';
    }
    
    /**
     * Preserve originals option callback.
     * 
     * @since    1.0.0
     */
    public function preserve_originals_callback() {
        $settings = get_option('wpio_settings', []);
        $preserve_originals = isset($settings['preserve_originals']) ? $settings['preserve_originals'] : true;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[preserve_originals]" value="1" ' . checked(1, $preserve_originals, false) . ' />';
        echo esc_html__('Keep original images for potential restoration', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo esc_html__('Stores original files in a backup directory. Increases disk usage but allows for recovery if needed.', 'weirdopress-image-optimizer');
        echo '</p>';
    }
    
    /**
     * Auto-replace option callback.
     * 
     * @since    1.0.0
     */
    public function auto_replace_callback() {
        $settings = get_option('wpio_settings', []);
        $auto_replace = isset($settings['auto_replace']) ? $settings['auto_replace'] : true;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[auto_replace]" value="1" ' . checked(1, $auto_replace, false) . ' />';
        echo esc_html__('Replace original images with optimized versions in media library', 'weirdopress-image-optimizer');
        echo '</label>';
    }
    
    /**
     * Log optimizations option callback.
     * 
     * @since    1.0.0
     */
    public function log_optimizations_callback() {
        $settings = get_option('wpio_settings', []);
        $log_optimizations = isset($settings['log_optimizations']) ? $settings['log_optimizations'] : true;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[log_optimizations]" value="1" ' . checked(1, $log_optimizations, false) . ' />';
        echo esc_html__('Keep a log of optimization results', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo esc_html__('Logs will store information about each optimized image and the amount of space saved.', 'weirdopress-image-optimizer');
        echo '</p>';
    }
    
    /**
     * Delete data on uninstall option callback.
     * 
     * @since    1.0.0
     */
    public function delete_data_callback() {
        $settings = get_option('wpio_settings', []);
        $delete_data = isset($settings['delete_data_on_uninstall']) ? $settings['delete_data_on_uninstall'] : false;

        echo '<label>';
        echo '<input type="checkbox" name="wpio_settings[delete_data_on_uninstall]" value="1" ' . checked(1, $delete_data, false) . ' />';
        echo esc_html__('Delete all plugin data when uninstalling', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo esc_html__('This will remove all settings and logs when the plugin is deleted.', 'weirdopress-image-optimizer');
        echo '</p>';
    }

    /**
     * Bulk optimization section callback.
     *
     * @since    1.0.0
     */
    public function bulk_section_callback() {
        echo '<p>';
        echo esc_html__('Optimize all your existing images in bulk with advanced settings.', 'weirdopress-image-optimizer');
        echo '</p>';

        // Display statistics
        $stats = $this->get_optimization_stats();
        echo '<div class="wpio-stats-box" style="background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;">';
        echo '<h4>' . esc_html__('Optimization Statistics', 'weirdopress-image-optimizer') . '</h4>';
        echo '<p><strong>' . esc_html__('Total Images:', 'weirdopress-image-optimizer') . '</strong> ' . esc_html($stats['total_images']) . '</p>';
        echo '<p><strong>' . esc_html__('Optimized:', 'weirdopress-image-optimizer') . '</strong> ' . esc_html($stats['optimized_images']) . '</p>';
        echo '<p><strong>' . esc_html__('Space Saved:', 'weirdopress-image-optimizer') . '</strong> ' . esc_html($stats['space_saved']) . '</p>';
        echo '</div>';

        // Bulk optimization button
        echo '<div class="wpio-bulk-section">';
        echo '<button type="button" id="wpio-bulk-optimize" class="button button-primary">' . esc_html__('Optimize All Images', 'weirdopress-image-optimizer') . '</button>';
        echo '<span id="wpio-bulk-progress" style="margin-left: 10px; display: none;">';
        echo esc_html__('Processing...', 'weirdopress-image-optimizer');
        echo ' <span id="wpio-progress-count">0/0</span>';
        echo '</span>';
        echo '</div>';

        echo '<div id="wpio-bulk-log" style="margin-top: 15px; max-height: 200px; overflow-y: auto; display: none;"></div>';
    }

    /**
     * Compression preset callback.
     *
     * @since    1.0.0
     */
    public function compression_preset_callback() {
        $settings = get_option('wpio_settings', []);
        $preset = isset($settings['compression_preset']) ? $settings['compression_preset'] : 'balanced';

        echo '<select name="wpio_settings[compression_preset]">';
        echo '<option value="high" ' . selected('high', $preset, false) . '>' . esc_html__('High Quality (85%)', 'weirdopress-image-optimizer') . '</option>';
        echo '<option value="balanced" ' . selected('balanced', $preset, false) . '>' . esc_html__('Balanced (75%)', 'weirdopress-image-optimizer') . '</option>';
        echo '<option value="performance" ' . selected('performance', $preset, false) . '>' . esc_html__('Performance (65%)', 'weirdopress-image-optimizer') . '</option>';
        echo '<option value="custom" ' . selected('custom', $preset, false) . '>' . esc_html__('Custom', 'weirdopress-image-optimizer') . '</option>';
        echo '</select>';

        echo '<div id="wpio-custom-quality" style="margin-top: 10px; display: none;">';
        echo '<label for="wpio_custom_quality">' . esc_html__('Custom Quality:', 'weirdopress-image-optimizer') . '</label>';
        echo '<input type="range" id="wpio_custom_quality" name="wpio_settings[custom_quality]" min="1" max="100" value="' . esc_attr(isset($settings['custom_quality']) ? $settings['custom_quality'] : 75) . '" style="width: 200px; vertical-align: middle;">';
        echo '<span id="wpio_quality_value">' . esc_html(isset($settings['custom_quality']) ? $settings['custom_quality'] : 75) . '</span>%';
        echo '</div>';

        echo '<p class="description">';
        echo esc_html__('Choose a compression preset or set custom quality. Lower values mean smaller files but lower quality.', 'weirdopress-image-optimizer');
        echo '</p>';
    }

    /**
     * Get optimization statistics.
     *
     * @since    1.0.0
     * @return   array    Statistics data
     */
    private function get_optimization_stats() {
        global $wpdb;

        // Get total images
        $total_images = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
        ");

        // Get optimized images from logs
        $logs = get_option('wpio_optimization_logs', []);
        $optimized_count = count($logs);

        // Calculate space saved
        $space_saved = 0;
        foreach ($logs as $log) {
            if (isset($log['original_size']) && isset($log['optimized_size'])) {
                $space_saved += ($log['original_size'] - $log['optimized_size']);
            }
        }

        // Format space saved
        if ($space_saved < 1024) {
            $space_saved_formatted = $space_saved . ' B';
        } elseif ($space_saved < 1024 * 1024) {
            $space_saved_formatted = round($space_saved / 1024, 1) . ' KB';
        } else {
            $space_saved_formatted = round($space_saved / (1024 * 1024), 1) . ' MB';
        }

        return [
            'total_images' => intval($total_images),
            'optimized_images' => $optimized_count,
            'space_saved' => $space_saved_formatted
        ];
    }

    /**
     * Validate settings before saving.
     * 
     * @since    1.0.0
     * @param    array    $input    Settings input
     * @return   array              Sanitized settings
     */
    public function validate_settings($input) {
        $sanitized = [];
        
        // Boolean options
        $sanitized['enable_webp'] = isset($input['enable_webp']) ? true : false;
        $sanitized['enable_avif'] = isset($input['enable_avif']) ? true : false;
        $sanitized['preserve_originals'] = isset($input['preserve_originals']) ? true : false;
        $sanitized['auto_replace'] = isset($input['auto_replace']) ? true : false;
        $sanitized['log_optimizations'] = isset($input['log_optimizations']) ? true : false;
        $sanitized['delete_data_on_uninstall'] = isset($input['delete_data_on_uninstall']) ? true : false;
        
        // Numeric options
        $sanitized['jpeg_quality'] = isset($input['jpeg_quality']) ?
            intval($input['jpeg_quality']) : 75;

        // Ensure jpeg_quality is between 0 and 100
        if ($sanitized['jpeg_quality'] < 0) {
            $sanitized['jpeg_quality'] = 0;
        } elseif ($sanitized['jpeg_quality'] > 100) {
            $sanitized['jpeg_quality'] = 100;
        }

        // Compression preset
        $sanitized['compression_preset'] = isset($input['compression_preset']) ?
            sanitize_text_field($input['compression_preset']) : 'balanced';

        // Custom quality
        $sanitized['custom_quality'] = isset($input['custom_quality']) ?
            intval($input['custom_quality']) : 75;

        // Ensure custom_quality is between 0 and 100
        if ($sanitized['custom_quality'] < 0) {
            $sanitized['custom_quality'] = 0;
        } elseif ($sanitized['custom_quality'] > 100) {
            $sanitized['custom_quality'] = 100;
        }

        // Map presets to quality values
        if ($sanitized['compression_preset'] !== 'custom') {
            switch ($sanitized['compression_preset']) {
                case 'high':
                    $sanitized['jpeg_quality'] = 85;
                    break;
                case 'balanced':
                    $sanitized['jpeg_quality'] = 75;
                    break;
                case 'performance':
                    $sanitized['jpeg_quality'] = 65;
                    break;
            }
        }

        return $sanitized;
    }
    
    /**
     * Render the settings page.
     * 
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        // Load environment capabilities
        $env_capabilities = $this->get_environment_capabilities();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpio-header">
                <div class="wpio-logo">
                    <!-- Logo would go here, could be an SVG or image -->
                    <h2>WeirdoPress Image Optimizer</h2>
                </div>
                <div class="wpio-version">
                    <span><?php echo esc_html__('Version', 'weirdopress-image-optimizer') . ': ' . esc_html($this->version); ?></span>
                </div>
            </div>
            
            <div class="wpio-container">
                <div class="wpio-settings">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('wpio_settings');
                        do_settings_sections('wpio_settings');
                        submit_button();
                        ?>
                    </form>
                    
                    <?php $this->render_environment_panel($env_capabilities); ?>
                </div>
                
                <div class="wpio-sidebar">
                    <div class="wpio-box">
                        <h3><?php esc_html_e('About', 'weirdopress-image-optimizer'); ?></h3>
                        <p>
                            <?php esc_html_e('WeirdoPress Image Optimizer automatically compresses your images using efficient algorithms like those in Squoosh, but directly on your server without any external API calls.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>

                    <div class="wpio-box">
                        <h3><?php esc_html_e('Features', 'weirdopress-image-optimizer'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Automatic compression on upload', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php esc_html_e('WebP & AVIF conversion', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php esc_html_e('Local processing (no API calls)', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php esc_html_e('Backup original images', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php esc_html_e('Smart fallbacks when binaries aren\'t available', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php esc_html_e('Works on shared hosting', 'weirdopress-image-optimizer'); ?></li>
                        </ul>
                    </div>

                    <div class="wpio-box">
                        <h3><?php esc_html_e('Support', 'weirdopress-image-optimizer'); ?></h3>
                        <p>
                            <?php esc_html_e('Need help? Have questions?', 'weirdopress-image-optimizer'); ?>
                        </p>
                        <a href="https://github.com/weirdopress/image-optimizer/issues" class="button button-secondary" target="_blank">
                            <?php esc_html_e('GitHub Support', 'weirdopress-image-optimizer'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get environment capabilities for optimization.
     * 
     * @since    1.1.0
     * @return   array    Array of environment capabilities.
     */
    private function get_environment_capabilities() {
        $capabilities = [
            'exec_enabled' => function_exists('exec'),
            'gd_enabled' => extension_loaded('gd'),
            'imagick_enabled' => extension_loaded('imagick'),
            'webp_support' => false,
            'avif_support' => false,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'php_version' => phpversion(),
        ];
        
        // Check for WebP support in GD
        if ($capabilities['gd_enabled'] && function_exists('imagewebp')) {
            $capabilities['webp_support'] = true;
        }
        
        // Check for WebP/AVIF support in Imagick
        if ($capabilities['imagick_enabled']) {
            try {
                $imagick = new Imagick();
                $formats = $imagick->queryFormats();
                
                if (in_array('WEBP', $formats)) {
                    $capabilities['webp_support'] = true;
                }
                
                if (in_array('AVIF', $formats)) {
                    $capabilities['avif_support'] = true;
                }
            } catch (Exception $e) {
                // Imagick might be installed but not working properly
            }
        }
        
        return $capabilities;
    }
    
    /**
     * Render environment information panel.
     * 
     * @since    1.1.0
     * @param    array    $capabilities    Environment capabilities.
     */
    private function render_environment_panel($capabilities) {
        ?>
        <div class="wpio-environment-panel">
            <h2><?php esc_html_e('Environment Information', 'weirdopress-image-optimizer'); ?></h2>

            <div class="wpio-env-description">
                <p>
                    <?php esc_html_e('This panel shows your server\'s capabilities for image optimization. WeirdoPress Image Optimizer adapts to your server environment to provide the best possible results.', 'weirdopress-image-optimizer'); ?>
                </p>
            </div>

            <table class="widefat wpio-env-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Feature', 'weirdopress-image-optimizer'); ?></th>
                        <th><?php esc_html_e('Status', 'weirdopress-image-optimizer'); ?></th>
                        <th><?php esc_html_e('Impact', 'weirdopress-image-optimizer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e('PHP Version', 'weirdopress-image-optimizer'); ?></td>
                        <td><?php echo esc_html($capabilities['php_version']); ?></td>
                        <td>
                            <?php
                            if (version_compare($capabilities['php_version'], '7.4', '>=')) {
                                echo '<span class="wpio-status-ok">✓ Optimal</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ Limited</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('exec() Function', 'weirdopress-image-optimizer'); ?></td>
                        <td>
                            <?php
                            echo $capabilities['exec_enabled']
                                ? '<span class="wpio-status-ok">✓ Available</span>'
                                : '<span class="wpio-status-error">✗ Disabled</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($capabilities['exec_enabled']) {
                                echo '<span class="wpio-status-ok">✓ Full optimization</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ Using PHP-only methods</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('GD Library', 'weirdopress-image-optimizer'); ?></td>
                        <td>
                            <?php
                            echo $capabilities['gd_enabled']
                                ? '<span class="wpio-status-ok">✓ Available</span>'
                                : '<span class="wpio-status-error">✗ Not Available</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($capabilities['gd_enabled']) {
                                echo '<span class="wpio-status-ok">✓ Basic image processing</span>';
                            } else {
                                echo '<span class="wpio-status-error">✗ Required for fallback methods</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('ImageMagick', 'weirdopress-image-optimizer'); ?></td>
                        <td>
                            <?php
                            echo $capabilities['imagick_enabled']
                                ? '<span class="wpio-status-ok">✓ Available</span>'
                                : '<span class="wpio-status-warning">⚠️ Not Available</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($capabilities['imagick_enabled']) {
                                echo '<span class="wpio-status-ok">✓ Advanced image processing</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ Recommended for better results</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('WebP Support', 'weirdopress-image-optimizer'); ?></td>
                        <td>
                            <?php
                            echo $capabilities['webp_support']
                                ? '<span class="wpio-status-ok">✓ Available</span>'
                                : '<span class="wpio-status-warning">⚠️ Not Available</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($capabilities['webp_support']) {
                                echo '<span class="wpio-status-ok">✓ Can create WebP images</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ WebP conversion disabled</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('AVIF Support', 'weirdopress-image-optimizer'); ?></td>
                        <td>
                            <?php
                            echo $capabilities['avif_support']
                                ? '<span class="wpio-status-ok">✓ Available</span>'
                                : '<span class="wpio-status-warning">⚠️ Not Available</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($capabilities['avif_support']) {
                                echo '<span class="wpio-status-ok">✓ Can create AVIF images</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ AVIF conversion disabled</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Memory Limit', 'weirdopress-image-optimizer'); ?></td>
                        <td><?php echo esc_html($capabilities['memory_limit']); ?></td>
                        <td>
                            <?php
                            $memory_limit = $this->parse_memory_size($capabilities['memory_limit']);
                            if ($memory_limit >= 128 * 1024 * 1024) {
                                echo '<span class="wpio-status-ok">✓ Sufficient for most images</span>';
                            } else {
                                echo '<span class="wpio-status-warning">⚠️ May be limited for large images</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="wpio-env-recommendations">
                <h3><?php esc_html_e('Recommendations', 'weirdopress-image-optimizer'); ?></h3>

                <?php if (!$capabilities['exec_enabled']): ?>
                    <div class="wpio-recommendation">
                        <p>
                            <strong><?php esc_html_e('Enable exec() function:', 'weirdopress-image-optimizer'); ?></strong>
                            <?php esc_html_e('For better compression results, ask your hosting provider to enable the PHP exec() function. With exec() enabled, the plugin can use specialized binaries like cwebp, avifenc, and jpegoptim for higher quality compression.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!$capabilities['imagick_enabled']): ?>
                    <div class="wpio-recommendation">
                        <p>
                            <strong><?php esc_html_e('Install ImageMagick extension:', 'weirdopress-image-optimizer'); ?></strong>
                            <?php esc_html_e('The ImageMagick PHP extension provides better image processing capabilities than GD. Ask your hosting provider if they can enable it for your account.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!$capabilities['webp_support']): ?>
                    <div class="wpio-recommendation">
                        <p>
                            <strong><?php esc_html_e('Enable WebP support:', 'weirdopress-image-optimizer'); ?></strong>
                            <?php esc_html_e('WebP images are smaller than JPEGs and PNGs while maintaining similar quality. Consider upgrading your PHP version or asking your host to add WebP support to your GD or ImageMagick installation.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($this->parse_memory_size($capabilities['memory_limit']) < 128 * 1024 * 1024): ?>
                    <div class="wpio-recommendation">
                        <p>
                            <strong><?php esc_html_e('Increase memory limit:', 'weirdopress-image-optimizer'); ?></strong>
                            <?php esc_html_e('A higher PHP memory limit allows for processing larger images. Consider increasing it to at least 128M if you work with large images.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($capabilities['exec_enabled'] && $capabilities['imagick_enabled'] && $capabilities['webp_support'] && $this->parse_memory_size($capabilities['memory_limit']) >= 128 * 1024 * 1024): ?>
                    <div class="wpio-recommendation wpio-recommendation-good">
                        <p>
                            <strong><?php esc_html_e('Your environment is well-configured:', 'weirdopress-image-optimizer'); ?></strong>
                            <?php esc_html_e('All necessary features are available for optimal image compression. You should get excellent results with this plugin.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Parse memory size string to bytes.
     * 
     * @since    1.1.0
     * @param    string    $size    Memory size string (e.g., '128M').
     * @return   int                Size in bytes.
     */
    private function parse_memory_size($size) {
        $unit = strtolower(substr($size, -1));
        $size = (int)$size;
        
        switch ($unit) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * Display requirement warnings if needed.
     * 
     * @since    1.0.0
     */
    public function display_requirement_warnings() {
        $screen = get_current_screen();
        
        // Only show on plugins page or our settings page
        if (!isset($screen->id) || ($screen->id !== 'plugins' && $screen->id !== 'settings_page_weirdopress-image-optimizer')) {
            return;
        }
        
        // Check for PHP version warning
        if (get_option('wpio_php_warning', false)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('WeirdoPress Image Optimizer', 'weirdopress-image-optimizer'); ?>:</strong>
                    <?php esc_html_e('This plugin works best with PHP 7.4 or higher. Your current PHP version may have limited functionality.', 'weirdopress-image-optimizer'); ?>
                </p>
            </div>
            <?php
        }
        
        // Check for exec() function warning
        if (get_option('wpio_exec_warning', false)) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e('WeirdoPress Image Optimizer', 'weirdopress-image-optimizer'); ?>:</strong>
                    <?php esc_html_e('The PHP exec() function is disabled. This plugin requires exec() to run external binaries. Please contact your hosting provider to enable this function, or the plugin will use lower quality fallback methods.', 'weirdopress-image-optimizer'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add custom columns to the media library.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns.
     * @return   array                Modified columns.
     */
    public function add_media_columns($columns) {
        $columns['wpio_status'] = esc_html__('Optimization', 'weirdopress-image-optimizer');
        return $columns;
    }
    
    /**
     * Display content for the WPIO Status column in the media library
     *
     * @since    1.0.0
     * @param    string     $column_name    Column name
     * @param    int        $attachment_id  Attachment ID
     */
    public function display_media_column_content($column_name, $attachment_id) {
        if ('wpio_status' !== $column_name) {
            return;
        }

        // Check if it's an image
        if (!wp_attachment_is_image($attachment_id)) {
            echo '<span class="wpio-badge wpio-badge-not-image">' . esc_html__('Not an image', 'weirdopress-image-optimizer') . '</span>';
            return;
        }

        // Get the file path
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            echo '<span class="wpio-badge wpio-badge-error">' . esc_html__('File not found', 'weirdopress-image-optimizer') . '</span>';
            return;
        }

        // Check file extension to see if it's optimizable (JPG, JPEG, PNG)
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';
        $optimizable_types = array('jpg', 'jpeg', 'png');
        
        // Get optimization data
        $optimization_data = get_post_meta($attachment_id, '_wpio_optimization_data', true);
        
        // Check for WebP and AVIF versions
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        $avif_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.avif';
        $has_webp = file_exists($webp_path);
        $has_avif = file_exists($avif_path);
        
        // Get size info for all formats
        $original_size = filesize($file_path);
        $webp_size = $has_webp ? filesize($webp_path) : 0;
        $avif_size = $has_avif ? filesize($avif_path) : 0;
        
        // Calculate savings for each format
        $webp_savings = $has_webp && $original_size > 0 ? round(($original_size - $webp_size) / $original_size * 100, 1) : 0;
        $avif_savings = $has_avif && $original_size > 0 ? round(($original_size - $avif_size) / $original_size * 100, 1) : 0;
        
        // Display optimization status
        echo '<div class="wpio-media-status">';

        // If we have optimization data from metadata
        if ($optimization_data) {
            // Show optimization badge
            echo '<div class="wpio-status-row">';
            echo '<span class="wpio-status-icon wpio-status-optimized"></span>';
            echo '<span class="wpio-status-text">' . esc_html__('Optimized', 'weirdopress-image-optimizer') . '</span>';
            echo '</div>';

            // Show size reduction
            if (!empty($optimization_data['original_size']) && !empty($optimization_data['optimized_size'])) {
                $orig_size = size_format($optimization_data['original_size'], 1);
                $opt_size = size_format($optimization_data['optimized_size'], 1);
                $savings_percent = isset($optimization_data['savings_percent']) ?
                    $optimization_data['savings_percent'] :
                    round(($optimization_data['original_size'] - $optimization_data['optimized_size']) / $optimization_data['original_size'] * 100, 1);

                echo '<div class="wpio-optimization-info">';
                echo '<span>' . esc_html($orig_size) . ' → ' . esc_html($opt_size) . '</span>';
                if ($savings_percent > 0) {
                    echo ' <span class="wpio-savings-positive">-' . esc_html($savings_percent) . '%</span>';
                } else {
                    echo ' <span class="wpio-savings-negative">0%</span>';
                }
                echo '</div>';
            }
        } else if (!in_array($extension, $optimizable_types)) {
            // For non-optimizable types (like WebP, AVIF already)
            echo '<div class="wpio-status-row">';
            echo '<span class="wpio-status-icon wpio-status-optimized"></span>';
            echo '<span class="wpio-status-text">' . esc_html(strtoupper($extension)) . ' ' . esc_html__('format', 'weirdopress-image-optimizer') . '</span>';
            echo '</div>';
        } else {
            // Not optimized yet
            echo '<div class="wpio-status-row">';
            echo '<span class="wpio-status-icon wpio-status-not-optimized"></span>';
            echo '<span class="wpio-status-text">' . esc_html__('Not optimized', 'weirdopress-image-optimizer') . '</span>';
            echo '</div>';
            echo '<button class="button button-small wpio-optimize-single" data-id="' . esc_attr($attachment_id) . '">' . esc_html__('Optimize', 'weirdopress-image-optimizer') . '</button>';
        }
        
        // Show available formats
        if ($has_webp || $has_avif) {
            echo '<div class="wpio-formats-available">';

            if ($has_webp) {
                echo '<span class="wpio-format-badge wpio-webp-badge" title="WebP: ' . esc_attr(size_format($webp_size, 1)) . ' (' . esc_attr($webp_savings) . '% ' . esc_attr__('savings', 'weirdopress-image-optimizer') . ')">WebP</span>';
            }

            if ($has_avif) {
                echo '<span class="wpio-format-badge wpio-avif-badge" title="AVIF: ' . esc_attr(size_format($avif_size, 1)) . ' (' . esc_attr($avif_savings) . '% ' . esc_attr__('savings', 'weirdopress-image-optimizer') . ')">AVIF</span>';
            }

            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add optimization details to attachment edit fields.
     *
     * @since    1.0.0
     * @param    array    $form_fields    Existing form fields.
     * @param    object   $post           Attachment post object.
     * @return   array                    Modified form fields.
     */
    public function add_attachment_fields($form_fields, $post) {
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }
        
        // Check if image has been optimized
        $optimized = get_post_meta($post->ID, 'wpio_optimized', true);
        
        if (!$optimized) {
            $form_fields['wpio_status'] = array(
                'label' => esc_html__('Optimization', 'weirdopress-image-optimizer'),
                'input' => 'html',
                'html'  => '<span class="wpio-status-warning">' . esc_html__('Not optimized', 'weirdopress-image-optimizer') . '</span>',
            );
            return $form_fields;
        }
        
        // Get optimization stats
        $original_size = get_post_meta($post->ID, 'wpio_original_size', true);
        $optimized_size = get_post_meta($post->ID, 'wpio_optimized_size', true);
        $saved_bytes = get_post_meta($post->ID, 'wpio_saved_bytes', true);
        $saved_percent = get_post_meta($post->ID, 'wpio_saved_percent', true);
        
        $html = '<div class="wpio-optimization-details">';
        
        if ($saved_percent > 0) {
            $html .= '<span class="wpio-status-ok">' . sprintf(esc_html__('Saved %s%%', 'weirdopress-image-optimizer'), esc_html(number_format($saved_percent, 1))) . '</span><br>';
            $html .= '<strong>' . esc_html__('Original size', 'weirdopress-image-optimizer') . ':</strong> ' . esc_html($this->format_bytes($original_size)) . '<br>';
            $html .= '<strong>' . esc_html__('Optimized size', 'weirdopress-image-optimizer') . ':</strong> ' . esc_html($this->format_bytes($optimized_size)) . '<br>';
            $html .= '<strong>' . esc_html__('Saved', 'weirdopress-image-optimizer') . ':</strong> ' . esc_html($this->format_bytes($saved_bytes));
        } else {
            $html .= '<span class="wpio-status-ok">' . esc_html__('Already optimized', 'weirdopress-image-optimizer') . '</span>';
        }

        // Check if WebP or AVIF versions exist
        $file_path = get_attached_file($post->ID);
        $webp_path = substr($file_path, 0, strrpos($file_path, '.')) . '.webp';
        $avif_path = substr($file_path, 0, strrpos($file_path, '.')) . '.avif';

        if (file_exists($webp_path)) {
            $html .= '<br><strong>' . esc_html__('WebP Version', 'weirdopress-image-optimizer') . ':</strong> ' . esc_html__('Yes', 'weirdopress-image-optimizer');
        }

        if (file_exists($avif_path)) {
            $html .= '<br><strong>' . esc_html__('AVIF Version', 'weirdopress-image-optimizer') . ':</strong> ' . esc_html__('Yes', 'weirdopress-image-optimizer');
        }

        $html .= '</div>';

        $form_fields['wpio_status'] = array(
            'label' => esc_html__('Optimization', 'weirdopress-image-optimizer'),
            'input' => 'html',
            'html'  => $html,
        );
        
        return $form_fields;
    }
    
    /**
     * Format bytes to human-readable format.
     * 
     * @since    1.0.0
     * @param    int       $bytes    Bytes to format.
     * @return   string              Human-readable size.
     */
    private function format_bytes($bytes) {
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        
        while ($bytes > 1024) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Display the optimization logs page
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        // Get logs
        $logs = get_option('wpio_optimization_logs', array());
        
        echo '<div class="wrap wpio-logs-page">';
        echo '<h1>' . esc_html__('Image Optimization Logs', 'weirdopress-image-optimizer') . '</h1>';

        if (empty($logs)) {
            echo '<div class="wpio-empty-logs">';
            echo '<p>' . esc_html__('No optimization logs found. Optimize some images first!', 'weirdopress-image-optimizer') . '</p>';
            echo '</div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped wpio-logs-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . esc_html__('File', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Original Format', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Conversion', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Original Size', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Optimized Size', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Savings', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('WebP/AVIF', 'weirdopress-image-optimizer') . '</th>';
            echo '<th>' . esc_html__('Date', 'weirdopress-image-optimizer') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($logs as $log) {
                // Get file info
                $file_name = isset($log['filename']) ? $log['filename'] : 
                            (isset($log['file_path']) ? basename($log['file_path']) : 'Unknown');
                $original_type = isset($log['original_type']) ? $log['original_type'] : 'Unknown';
                
                // Set conversion format display
                $conversion_display = $original_type;
                $conversion_type = '';
                
                if (isset($log['conversion_type']) && $log['conversion_type'] === 'converted') {
                    // This is a dedicated WebP/AVIF conversion log entry
                    $conversion_type = isset($log['format']) ? strtoupper($log['format']) : '';
                    $conversion_display = '<span class="wpio-conversion-badge">' . $conversion_type . '</span>';
                } elseif (!empty($log['conversion_format']) && $log['conversion_format'] !== 'original') {
                    // This is a standard entry with conversion information
                    $conversion_type = strtoupper($log['conversion_format']);
                    $conversion_display = $original_type . ' → <span class="wpio-conversion-badge">' . $conversion_type . '</span>';
                }
                
                // Format sizes
                $original_size = isset($log['original_size']) ? size_format($log['original_size'], 1) : 'Unknown';
                $optimized_size = isset($log['optimized_size']) ? size_format($log['optimized_size'], 1) : 'Unknown';
                
                // Calculate savings
                $savings = '';
                if (isset($log['savings_percent'])) {
                    $savings_percent = $log['savings_percent'];
                    $savings_class = $savings_percent > 0 ? 'wpio-savings-positive' : 'wpio-savings-negative';
                    $savings = '<span class="' . $savings_class . '">-' . $savings_percent . '%</span>';
                } elseif (isset($log['original_size']) && isset($log['optimized_size']) && $log['original_size'] > 0) {
                    $savings_percent = round(($log['original_size'] - $log['optimized_size']) / $log['original_size'] * 100, 1);
                    $savings_class = $savings_percent > 0 ? 'wpio-savings-positive' : 'wpio-savings-negative';
                    $savings = '<span class="' . $savings_class . '">-' . $savings_percent . '%</span>';
                }
                
                // WebP/AVIF availability indicators
                $modern_formats = '';
                if (isset($log['has_webp']) && $log['has_webp']) {
                    $webp_size = isset($log['webp_size_human']) ? $log['webp_size_human'] : '';
                    $webp_savings = isset($log['webp_saved_percent']) ? $log['webp_saved_percent'] . '%' : '';
                    $modern_formats .= '<span class="wpio-format-badge wpio-webp-badge" title="WebP: ' . $webp_size . ' (' . $webp_savings . ' savings)">WebP</span>';
                }
                
                if (isset($log['has_avif']) && $log['has_avif']) {
                    $avif_size = isset($log['avif_size_human']) ? $log['avif_size_human'] : '';
                    $avif_savings = isset($log['avif_saved_percent']) ? $log['avif_saved_percent'] . '%' : '';
                    $modern_formats .= '<span class="wpio-format-badge wpio-avif-badge" title="AVIF: ' . $avif_size . ' (' . $avif_savings . ' savings)">AVIF</span>';
                }
                
                // If this is a dedicated WebP/AVIF entry, show badge for the specific format
                if (isset($log['conversion_type']) && $log['conversion_type'] === 'converted') {
                    $format = isset($log['format']) ? $log['format'] : '';
                    if ($format === 'webp') {
                        $modern_formats = '<span class="wpio-format-badge wpio-webp-badge">WebP</span>';
                    } elseif ($format === 'avif') {
                        $modern_formats = '<span class="wpio-format-badge wpio-avif-badge">AVIF</span>';
                    }
                }
                
                // Format date
                $date = isset($log['date']) ? $log['date'] : 
                       (isset($log['timestamp']) ? date('Y-m-d H:i:s', $log['timestamp']) : 'Unknown');
                
                echo '<tr>';
                echo '<td>' . esc_html($file_name) . '</td>';
                echo '<td>' . esc_html($original_type) . '</td>';
                echo '<td>' . $conversion_display . '</td>';
                echo '<td>' . esc_html($original_size) . '</td>';
                echo '<td>' . esc_html($optimized_size) . '</td>';
                echo '<td>' . $savings . '</td>';
                echo '<td>' . $modern_formats . '</td>';
                echo '<td>' . esc_html($date) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_wpio_optimize_single_image', array($this, 'ajax_optimize_single_image'));
        add_action('wp_ajax_wpio_bulk_optimize', array($this, 'ajax_bulk_optimize'));
        add_action('wp_ajax_wpio_get_bulk_progress', array($this, 'ajax_get_bulk_progress'));
    }

    /**
     * AJAX handler for optimizing a single image
     */
    public function ajax_optimize_single_image() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpio_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check if attachment ID is provided
        if ( ! isset( $_POST['attachment_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Missing attachment ID.' ) );
        }

        $attachment_id = intval( $_POST['attachment_id'] );

        // Check if attachment exists and is an image
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => 'Not a valid image attachment.' ) );
        }

        // Get the file path
        $file_path = get_attached_file( $attachment_id );
        if ( ! file_exists( $file_path ) ) {
            wp_send_json_error( array( 'message' => 'Image file not found.' ) );
        }

        // Check if file extension is supported
        $extension = pathinfo( $file_path, PATHINFO_EXTENSION );
        if ( ! in_array( strtolower( $extension ), array( 'jpg', 'jpeg', 'png' ) ) ) {
            wp_send_json_error( array( 'message' => 'File type not supported.' ) );
        }

        // Get the settings
        $settings = get_option('wpio_settings', []);

        // Backup the original file if needed
        if ( $settings['preserve_originals'] ) {
            $this->plugin->file_manager->backup_original( $file_path );
        }

        // Optimize the image
        $processor = new WPIO_Image_Processor();
        $result = $processor->optimize_image( $file_path );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Failed to optimize image.' ) );
        }

        // Create WebP version if enabled
        if ( $settings['enable_webp'] ) {
            $processor->convert_to_webp( $file_path );
        }

        // Create AVIF version if enabled
        if ( $settings['enable_avif'] ) {
            $processor->convert_to_avif( $file_path );
        }

        // Calculate savings
        $original_size = isset( $result['original_size'] ) ? $result['original_size'] : 0;
        $optimized_size = isset( $result['optimized_size'] ) ? $result['optimized_size'] : 0;
        $savings = $original_size - $optimized_size;
        $savings_percent = $original_size > 0 ? round( ( $savings / $original_size ) * 100, 1 ) : 0;

        // Prepare optimization data
        $optimization_data = array(
            'original_size'    => $original_size,
            'optimized_size'   => $optimized_size,
            'savings'          => $savings,
            'savings_percent'  => $savings_percent,
            'timestamp'        => current_time( 'timestamp' ),
        );

        // Save optimization data to attachment meta
        update_post_meta( $attachment_id, '_wpio_optimization_data', $optimization_data );

        // Log the optimization
        $this->plugin->file_manager->log_optimization( array(
            'file_path'       => $file_path,
            'attachment_id'   => $attachment_id,
            'original_size'   => $original_size,
            'optimized_size'  => $optimized_size,
            'savings'         => $savings,
            'savings_percent' => $savings_percent,
        ) );

        // Success response
        wp_send_json_success( array(
            'message'         => 'Image successfully optimized.',
            'savings_percent' => $savings_percent,
            'original_size'   => size_format( $original_size ),
            'optimized_size'  => size_format( $optimized_size ),
        ) );
    }

    /**
     * AJAX handler for bulk optimization
     */
    public function ajax_bulk_optimize() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpio_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        // Get unoptimized images
        global $wpdb;
        $images = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as file_path
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND pm.meta_key = '_wp_attached_file'
            AND p.ID NOT IN (
                SELECT DISTINCT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_wpio_optimization_data'
            )
            ORDER BY p.ID DESC
            LIMIT 50
        ");

        if (empty($images)) {
            wp_send_json_success(array(
                'message' => 'All images are already optimized!',
                'total' => 0
            ));
        }

        // Store the queue in transient for processing
        set_transient('wpio_bulk_queue', $images, HOUR_IN_SECONDS);
        set_transient('wpio_bulk_progress', 0, HOUR_IN_SECONDS);
        set_transient('wpio_bulk_total', count($images), HOUR_IN_SECONDS);
        set_transient('wpio_bulk_log', [], HOUR_IN_SECONDS);

        wp_send_json_success(array(
            'message' => 'Starting bulk optimization...',
            'total' => count($images)
        ));
    }

    /**
     * AJAX handler to get bulk optimization progress
     */
    public function ajax_get_bulk_progress() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpio_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        $progress = get_transient('wpio_bulk_progress');
        $total = get_transient('wpio_bulk_total');
        $logs = get_transient('wpio_bulk_log');

        if ($progress === false || $total === false) {
            wp_send_json_error(array('message' => 'No bulk optimization in progress.'));
        }

        // Process a few images if still in progress
        if ($progress < $total) {
            $this->process_bulk_batch(5); // Process 5 images per request
        }

        $progress_percent = $total > 0 ? round(($progress / $total) * 100) : 100;

        wp_send_json_success(array(
            'processed' => $progress,
            'total' => $total,
            'percent' => $progress_percent,
            'logs' => $logs,
            'complete' => $progress >= $total
        ));
    }

    /**
     * Process a batch of images for bulk optimization
     *
     * @param int $batch_size Number of images to process
     */
    private function process_bulk_batch($batch_size) {
        $queue = get_transient('wpio_bulk_queue');
        $progress = get_transient('wpio_bulk_progress');
        $logs = get_transient('wpio_bulk_log');

        if (empty($queue) || $progress >= get_transient('wpio_bulk_total')) {
            return;
        }

        $processed = 0;
        $settings = get_option('wpio_settings', []);

        while ($processed < $batch_size && !empty($queue)) {
            $image = array_shift($queue);
            $attachment_id = $image->ID;
            $file_path = get_attached_file($attachment_id);

            if ($file_path && file_exists($file_path)) {
                $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    try {
                        // Backup if needed
                        if (isset($settings['preserve_originals']) && $settings['preserve_originals']) {
                            $backup_path = $file_path . '.backup';
                            if (!file_exists($backup_path)) {
                                copy($file_path, $backup_path);
                            }
                        }

                        // Get original size
                        $original_size = filesize($file_path);

                        // Optimize based on settings
                        if (isset($settings['compression_preset'])) {
                            $quality = $settings['jpeg_quality'];
                        } else {
                            $quality = 75;
                        }

                        // Simple optimization (in real implementation, use your compressor)
                        if ($extension === 'png' && function_exists('imagepng')) {
                            $img = imagecreatefrompng($file_path);
                            imagepng($img, $file_path, 9);
                            imagedestroy($img);
                        } elseif (in_array($extension, ['jpg', 'jpeg']) && function_exists('imagejpeg')) {
                            $img = imagecreatefromjpeg($file_path);
                            imagejpeg($img, $file_path, $quality);
                            imagedestroy($img);
                        }

                        // Calculate savings
                        $optimized_size = filesize($file_path);
                        $savings = $original_size - $optimized_size;
                        $savings_percent = $original_size > 0 ? round(($savings / $original_size) * 100, 1) : 0;

                        // Update attachment metadata
                        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));

                        // Log optimization
                        $optimization_data = array(
                            'date' => current_time('mysql'),
                            'original_size' => $original_size,
                            'optimized_size' => $optimized_size,
                            'savings_percent' => $savings_percent,
                            'method' => 'gd'
                        );
                        update_post_meta($attachment_id, '_wpio_optimization_data', $optimization_data);

                        // Add to logs
                        $logs[] = array(
                            'attachment_id' => $attachment_id,
                            'filename' => basename($file_path),
                            'status' => 'success',
                            'savings' => $savings_percent . '%'
                        );

                    } catch (Exception $e) {
                        $logs[] = array(
                            'attachment_id' => $attachment_id,
                            'filename' => basename($file_path),
                            'status' => 'error',
                            'message' => $e->getMessage()
                        );
                    }
                }
            }

            $processed++;
            $progress++;
        }

        // Update transients
        set_transient('wpio_bulk_queue', $queue, HOUR_IN_SECONDS);
        set_transient('wpio_bulk_progress', $progress, HOUR_IN_SECONDS);
        set_transient('wpio_bulk_log', $logs, HOUR_IN_SECONDS);

        // Clean up if complete
        if ($progress >= get_transient('wpio_bulk_total')) {
            delete_transient('wpio_bulk_queue');
            delete_transient('wpio_bulk_progress');
            delete_transient('wpio_bulk_total');
        }
    }
} 