<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Only delete options if the user has enabled the "delete_data" setting
$settings = get_option('wpio_settings', []);
$delete_data = isset($settings['delete_data_on_uninstall']) ? $settings['delete_data_on_uninstall'] : false;

if ($delete_data) {
    // Delete plugin options
    delete_option('wpio_settings');
    delete_option('wpio_version');
    delete_option('wpio_installed');
    delete_option('wpio_deactivated');
    delete_option('wpio_php_warning');
    delete_option('wpio_exec_warning');
    delete_option('wpio_binary_status');
    delete_option('wpio_allow_tracking');
    
    // Delete any logs if they exist
    delete_option('wpio_optimization_log');
    
    // For multisite installations
    if (is_multisite()) {
        delete_site_option('wpio_settings');
        delete_site_option('wpio_version');
        delete_site_option('wpio_installed');
        delete_site_option('wpio_network_settings');
    }
    
    // If we were storing any transients, delete them
    delete_transient('wpio_binary_check');
    
    global $wpdb;
    
    // Delete any postmeta created by the plugin
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'wpio_%'");
} 