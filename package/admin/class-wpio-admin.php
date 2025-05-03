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
        // Only load on our settings page
        $screen = get_current_screen();
        if (!isset($screen->id) || $screen->id !== 'settings_page_weirdopress-image-optimizer') {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/wpio-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load on our settings page
        $screen = get_current_screen();
        if (!isset($screen->id) || $screen->id !== 'settings_page_weirdopress-image-optimizer') {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wpio-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }
    
    /**
     * Add settings menu item and page.
     * 
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            'WeirdoPress Image Optimizer',
            'Image Optimizer',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
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
            '<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . __('Settings', 'weirdopress-image-optimizer') . '</a>',
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
            __('General Settings', 'weirdopress-image-optimizer'),
            array($this, 'general_section_callback'),
            'wpio_settings'
        );
        
        add_settings_field(
            'enable_webp',
            __('Enable WebP conversion', 'weirdopress-image-optimizer'),
            array($this, 'enable_webp_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'enable_avif',
            __('Enable AVIF conversion', 'weirdopress-image-optimizer'),
            array($this, 'enable_avif_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'jpeg_quality',
            __('JPEG Quality', 'weirdopress-image-optimizer'),
            array($this, 'jpeg_quality_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'preserve_originals',
            __('Preserve original files', 'weirdopress-image-optimizer'),
            array($this, 'preserve_originals_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'auto_replace',
            __('Auto-replace in media library', 'weirdopress-image-optimizer'),
            array($this, 'auto_replace_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'log_optimizations',
            __('Log optimization results', 'weirdopress-image-optimizer'),
            array($this, 'log_optimizations_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_field(
            'delete_data_on_uninstall',
            __('Delete data on uninstall', 'weirdopress-image-optimizer'),
            array($this, 'delete_data_callback'),
            'wpio_settings',
            'wpio_general_section'
        );
        
        add_settings_section(
            'wpio_binary_section',
            __('Binary Status', 'weirdopress-image-optimizer'),
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
        echo '<p>' . __('Configure how WeirdoPress Image Optimizer should process your images.', 'weirdopress-image-optimizer') . '</p>';
    }
    
    /**
     * Binary status section callback.
     * 
     * @since    1.0.0
     */
    public function binary_section_callback() {
        echo '<p>' . __('The following table shows the status of the binaries needed for various optimization methods.', 'weirdopress-image-optimizer') . '</p>';
        
        $binaries = $this->binary_detector->check_binaries();
        
        echo '<table class="widefat wpio-binary-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Binary', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . __('Status', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . __('Version', 'weirdopress-image-optimizer') . '</th>';
        echo '<th>' . __('Feature', 'weirdopress-image-optimizer') . '</th>';
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
            echo '<p><strong>' . __('Warning', 'weirdopress-image-optimizer') . ':</strong> ';
            echo __('The exec() function is disabled on your server. This plugin requires exec() to run external binaries. Please contact your hosting provider to enable this function.', 'weirdopress-image-optimizer');
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
        echo __('Convert images to WebP format', 'weirdopress-image-optimizer');
        echo '</label>';
        
        if (!$this->binary_detector->is_binary_available('cwebp')) {
            echo '<p class="description wpio-warning">';
            echo __('Warning: cwebp binary not found. WebP conversion will use GD library as fallback (lower quality).', 'weirdopress-image-optimizer');
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
        echo __('Convert images to AVIF format', 'weirdopress-image-optimizer');
        echo '</label>';
        
        if (!$this->binary_detector->is_binary_available('avifenc')) {
            echo '<p class="description wpio-warning">';
            echo __('Warning: avifenc binary not found. AVIF conversion will be skipped.', 'weirdopress-image-optimizer');
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
        
        echo '<input type="range" min="0" max="100" step="1" name="wpio_settings[jpeg_quality]" value="' . $jpeg_quality . '" class="wpio-range" />';
        echo '<span class="wpio-range-value">' . $jpeg_quality . '</span>';
        echo '<p class="description">';
        echo __('Lower quality = smaller file size but reduced image quality. Recommended: 70-85.', 'weirdopress-image-optimizer');
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
        echo __('Keep original images for potential restoration', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo __('Stores original files in a backup directory. Increases disk usage but allows for recovery if needed.', 'weirdopress-image-optimizer');
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
        echo __('Replace original images with optimized versions in media library', 'weirdopress-image-optimizer');
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
        echo __('Keep a log of optimization results', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo __('Logs will store information about each optimized image and the amount of space saved.', 'weirdopress-image-optimizer');
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
        echo __('Delete all plugin data when uninstalling', 'weirdopress-image-optimizer');
        echo '</label>';
        echo '<p class="description">';
        echo __('This will remove all settings and logs when the plugin is deleted.', 'weirdopress-image-optimizer');
        echo '</p>';
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
        
        return $sanitized;
    }
    
    /**
     * Render the settings page.
     * 
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpio-header">
                <div class="wpio-logo">
                    <!-- Logo would go here, could be an SVG or image -->
                    <h2>WeirdoPress Image Optimizer</h2>
                </div>
                <div class="wpio-version">
                    <span><?php echo __('Version', 'weirdopress-image-optimizer') . ': ' . esc_html($this->version); ?></span>
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
                </div>
                
                <div class="wpio-sidebar">
                    <div class="wpio-box">
                        <h3><?php _e('About', 'weirdopress-image-optimizer'); ?></h3>
                        <p>
                            <?php _e('WeirdoPress Image Optimizer automatically compresses your images using efficient algorithms like those in Squoosh, but directly on your server without any external API calls.', 'weirdopress-image-optimizer'); ?>
                        </p>
                    </div>
                    
                    <div class="wpio-box">
                        <h3><?php _e('Features', 'weirdopress-image-optimizer'); ?></h3>
                        <ul>
                            <li><?php _e('Automatic compression on upload', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php _e('WebP & AVIF conversion', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php _e('Local processing (no API calls)', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php _e('Backup original images', 'weirdopress-image-optimizer'); ?></li>
                            <li><?php _e('Smart fallbacks when binaries aren\'t available', 'weirdopress-image-optimizer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="wpio-box">
                        <h3><?php _e('Support', 'weirdopress-image-optimizer'); ?></h3>
                        <p>
                            <?php _e('Need help? Have questions?', 'weirdopress-image-optimizer'); ?>
                        </p>
                        <a href="https://github.com/weirdopress/image-optimizer/issues" class="button button-secondary" target="_blank">
                            <?php _e('GitHub Support', 'weirdopress-image-optimizer'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
                    <strong><?php _e('WeirdoPress Image Optimizer', 'weirdopress-image-optimizer'); ?>:</strong>
                    <?php _e('This plugin works best with PHP 7.4 or higher. Your current PHP version may have limited functionality.', 'weirdopress-image-optimizer'); ?>
                </p>
            </div>
            <?php
        }
        
        // Check for exec() function warning
        if (get_option('wpio_exec_warning', false)) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e('WeirdoPress Image Optimizer', 'weirdopress-image-optimizer'); ?>:</strong>
                    <?php _e('The PHP exec() function is disabled. This plugin requires exec() to run external binaries. Please contact your hosting provider to enable this function, or the plugin will use lower quality fallback methods.', 'weirdopress-image-optimizer'); ?>
                </p>
            </div>
            <?php
        }
    }
} 