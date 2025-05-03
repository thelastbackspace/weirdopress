<?php
/**
 * Fired during plugin activation
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Activator {

    /**
     * Initialize plugin settings and perform activation tasks.
     *
     * Sets up the default options for the plugin and checks for system requirements.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Set default options if they don't exist
        if (!get_option('wpio_settings')) {
            $default_settings = [
                'enable_webp' => true,
                'enable_avif' => true,
                'jpeg_quality' => 75,
                'preserve_originals' => true,
                'auto_replace' => true,
                'log_optimizations' => true,
            ];
            
            update_option('wpio_settings', $default_settings);
        }
        
        // Create a flag indicating this is a new installation
        if (!get_option('wpio_installed')) {
            add_option('wpio_installed', time());
        }
        
        // Update the version in the database
        update_option('wpio_version', WPIO_VERSION);
        
        // Check for PHP requirements
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            // Log or display a notice - we won't halt activation since WP already checks this
            update_option('wpio_php_warning', true);
        } else {
            delete_option('wpio_php_warning');
        }
        
        // Check for exec() function availability
        if (!function_exists('exec')) {
            update_option('wpio_exec_warning', true);
        } else {
            delete_option('wpio_exec_warning');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
} 