<?php
/**
 * WeirdoPress Image Optimizer
 *
 * @package           WeirdoPressImageOptimizer
 * @author            WeirdoPress
 * @copyright         2025 WeirdoPress
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name:       WeirdoPress Image Optimizer
 * Plugin URI:        https://github.com/weirdopress/image-optimizer
 * Description:       A privacy-first, blazing fast, 100% local image compression plugin for WordPress — powered by Squoosh compression quality. No APIs. No tracking. No bloat.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            WeirdoPress
 * Author URI:        https://github.com/weirdopress
 * Text Domain:       weirdopress-image-optimizer
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WPIO_VERSION', '1.0.0');
define('WPIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPIO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPIO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_weirdopress_image_optimizer() {
    require_once WPIO_PLUGIN_DIR . 'includes/class-wpio-activator.php';
    WPIO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_weirdopress_image_optimizer() {
    require_once WPIO_PLUGIN_DIR . 'includes/class-wpio-deactivator.php';
    WPIO_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_weirdopress_image_optimizer');
register_deactivation_hook(__FILE__, 'deactivate_weirdopress_image_optimizer');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WPIO_PLUGIN_DIR . 'includes/class-wpio.php';

/**
 * Begins execution of the plugin.
 */
function run_weirdopress_image_optimizer() {
    $plugin = new WPIO();
    $plugin->run();
}

run_weirdopress_image_optimizer(); 