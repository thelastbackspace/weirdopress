<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Deactivator {

    /**
     * Perform cleanup tasks during deactivation.
     *
     * Note: We're intentionally not removing settings on deactivation,
     * only on uninstall if the user chooses to delete data.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events if we have any
        wp_clear_scheduled_hook('wpio_scheduled_optimization');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Maybe log deactivation time for statistics (if user opted in)
        if (get_option('wpio_allow_tracking', false)) {
            update_option('wpio_deactivated', time());
        }
    }
} 