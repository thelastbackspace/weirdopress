<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WPIO_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('WPIO_VERSION')) {
            $this->version = WPIO_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'weirdopress-image-optimizer';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_compressor_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WPIO_Loader. Orchestrates the hooks of the plugin.
     * - WPIO_i18n. Defines internationalization functionality.
     * - WPIO_Admin. Defines all hooks for the admin area.
     * - WPIO_Public. Defines all hooks for the public side of the site.
     * - WPIO_Binary_Detector. Detects available binaries for compression.
     * - WPIO_Compressor. Handles the image compression process.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpio-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpio-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wpio-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wpio-public.php';

        /**
         * The class responsible for detecting available binaries.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpio-binary-detector.php';

        /**
         * The class responsible for handling file operations.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpio-file-manager.php';

        /**
         * The class responsible for compressing images.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpio-compressor.php';

        $this->loader = new WPIO_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the WPIO_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new WPIO_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WPIO_Admin($this->get_plugin_name(), $this->get_version());
        $plugin_admin->plugin = $this;

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Add settings link to plugins page
        $plugin_basename = plugin_basename(plugin_dir_path(dirname(__FILE__)) . 'weirdopress-image-optimizer.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        // Media library column for optimization status
        $this->loader->add_filter('manage_media_columns', $plugin_admin, 'add_media_columns');
        $this->loader->add_action('manage_media_custom_column', $plugin_admin, 'display_media_column_content', 10, 2);

        // Add optimization information to attachment details
        $this->loader->add_filter('attachment_fields_to_edit', $plugin_admin, 'add_attachment_fields', 10, 2);

        // Register AJAX handlers
        $this->loader->add_action('admin_init', $plugin_admin, 'register_ajax_handlers');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WPIO_Public($this->get_plugin_name(), $this->get_version());
        
        // Enqueue public styles and scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Add content filters for image URL replacement
        $this->loader->add_action('init', $plugin_public, 'add_content_filters');
        
        // Add filter for srcset handling
        $this->loader->add_filter('wp_calculate_image_srcset', $plugin_public, 'webp_srcset_filter');
        
        // Admin-side modifications for media library
        $this->loader->add_filter('wp_prepare_attachment_for_js', $plugin_public, 'modify_admin_attachment_for_js', 10, 3);
        $this->loader->add_action('admin_head', $plugin_public, 'add_admin_styles');
        $this->loader->add_action('admin_footer', $plugin_public, 'add_admin_scripts');
        
        // Also add filter for the actual attachment preview in the editor
        $this->loader->add_filter('get_image_tag', $plugin_public, 'modify_admin_attachment_preview', 10, 2);
        $this->loader->add_filter('wp_get_attachment_image', $plugin_public, 'modify_admin_attachment_preview', 10, 2);
        
        // Register AJAX handler for checking modern formats
        $this->loader->add_action('wp_ajax_wpio_check_modern_formats', $plugin_public, 'check_modern_formats_ajax');
    }

    /**
     * Register all of the hooks related to image compression
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_compressor_hooks() {
        $compressor = new WPIO_Compressor($this->get_plugin_name(), $this->get_version());
        
        // Hook into image uploads
        $this->loader->add_filter('wp_handle_upload', $compressor, 'handle_upload', 10, 2);
        
        // Hook into attachment creation
        $this->loader->add_filter('wp_generate_attachment_metadata', $compressor, 'optimize_attachment', 10, 2);
    }

    /**
     * Add filters to correctly display WebP/AVIF filenames in the media library
     * 
     * @since    1.0.0
     * @access   private
     */
    private function define_attachment_filters() {
        // Update filenames in attachment UI
        $this->loader->add_filter('wp_prepare_attachment_for_js', $this, 'update_attachment_filename_for_js', 10, 3);
    }

    /**
     * Update attachment filename for JS representation
     * 
     * @since    1.0.0
     * @param    array     $response    Attachment response data
     * @param    object    $attachment  Attachment object
     * @param    array     $meta        Attachment meta
     * @return   array                  Modified response data
     */
    public function update_attachment_filename_for_js($response, $attachment, $meta) {
        if (isset($response['filename'])) {
            // Check if we have stored a WebP or AVIF conversion
            if (get_post_meta($attachment->ID, '_wp_attachment_metadata_converted', true)) {
                $converted_type = get_post_meta($attachment->ID, '_wp_attachment_metadata_converted_type', true);
                
                if ($converted_type === 'image/webp') {
                    $extension = '.webp';
                    $response['filename'] = preg_replace('/\.(jpe?g|png)$/i', $extension, $response['filename']);
                    $response['filesizeHumanReadable'] = size_format(filesize(get_post_meta($attachment->ID, '_wp_attachment_metadata_converted_path', true)));
                } elseif ($converted_type === 'image/avif') {
                    $extension = '.avif';
                    $response['filename'] = preg_replace('/\.(jpe?g|png)$/i', $extension, $response['filename']);
                    $response['filesizeHumanReadable'] = size_format(filesize(get_post_meta($attachment->ID, '_wp_attachment_metadata_converted_path', true)));
                }
            }
        }
        
        return $response;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->define_attachment_filters();
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    WPIO_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
} 