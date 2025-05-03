<?php
/**
 * Image compression functionality
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * Image compressor class.
 *
 * This class handles image compression using various binaries:
 * - cwebp for WebP conversion
 * - avifenc for AVIF conversion
 * - jpegoptim/mozjpeg for JPEG optimization
 * - Fallback to GD/Imagick when binaries are not available
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Compressor {
    
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
     * Binary detector instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WPIO_Binary_Detector    $binary_detector    Binary detector instance.
     */
    private $binary_detector;
    
    /**
     * File manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WPIO_File_Manager    $file_manager    File manager instance.
     */
    private $file_manager;
    
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->binary_detector = new WPIO_Binary_Detector();
        $this->file_manager = new WPIO_File_Manager();
        $this->settings = get_option('wpio_settings', []);
    }
    
    /**
     * Handle image upload and process it.
     *
     * This is hooked to 'wp_handle_upload' filter.
     *
     * @since    1.0.0
     * @param    array     $upload    Upload data.
     * @param    string    $context   Upload context.
     * @return   array                Modified upload data.
     */
    public function handle_upload($upload, $context) {
        // Only proceed if this is an image upload
        if (isset($upload['file']) && isset($upload['type']) && strpos($upload['type'], 'image/') === 0) {
            // We don't need to do anything here, the actual optimization happens in optimize_attachment
            // This hook is useful for future enhancements
        }
        
        return $upload;
    }
    
    /**
     * Optimize attachment after it has been created.
     *
     * This is hooked to 'wp_generate_attachment_metadata' filter.
     *
     * @since    1.0.0
     * @param    array     $metadata      Attachment metadata.
     * @param    int       $attachment_id Attachment ID.
     * @return   array                    Modified metadata.
     */
    public function optimize_attachment($metadata, $attachment_id) {
        if (!is_array($metadata) || !isset($metadata['file'])) {
            return $metadata;
        }
        
        // Get the main file
        $upload_dir = wp_upload_dir();
        $main_file = trailingslashit($upload_dir['basedir']) . $metadata['file'];
        
        // Optimize the main file
        $optimized = $this->optimize_image($main_file);
        
        // If optimization was successful, store results in attachment meta
        if ($optimized) {
            update_post_meta($attachment_id, 'wpio_optimized', true);
            update_post_meta($attachment_id, 'wpio_original_size', $optimized['original_size']);
            update_post_meta($attachment_id, 'wpio_optimized_size', $optimized['optimized_size']);
            update_post_meta($attachment_id, 'wpio_saved_bytes', $optimized['saved_bytes']);
            update_post_meta($attachment_id, 'wpio_saved_percent', $optimized['saved_percent']);
        }
        
        // Optimize resized images if they exist
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_key => $size_data) {
                if (isset($size_data['file'])) {
                    $size_file = trailingslashit(dirname($main_file)) . $size_data['file'];
                    $this->optimize_image($size_file);
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Optimize a single image file.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @return   array|false             Optimization results or false if failed.
     */
    public function optimize_image($file_path) {
        if (!file_exists($file_path) || !$this->file_manager->is_image($file_path)) {
            return false;
        }
        
        // Backup original if enabled
        $this->file_manager->backup_original($file_path);
        
        $file_ext = $this->file_manager->get_extension($file_path);
        $original_size = filesize($file_path);
        $optimized = false;
        
        // Process based on file type
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $optimized = $this->optimize_jpeg($file_path);
                break;
                
            case 'png':
                $optimized = $this->optimize_png($file_path);
                break;
                
            case 'webp':
                $optimized = $this->optimize_webp($file_path);
                break;
                
            case 'avif':
                // AVIF is already optimized, just return stats
                $optimized = [
                    'original_size' => $original_size,
                    'optimized_size' => $original_size,
                    'saved_bytes' => 0,
                    'saved_percent' => 0,
                ];
                break;
        }
        
        // Convert to WebP if enabled
        if (isset($this->settings['enable_webp']) && $this->settings['enable_webp'] && $file_ext !== 'webp' && $file_ext !== 'avif') {
            $this->convert_to_webp($file_path);
        }
        
        // Convert to AVIF if enabled
        if (isset($this->settings['enable_avif']) && $this->settings['enable_avif'] && $file_ext !== 'avif') {
            $this->convert_to_avif($file_path);
        }
        
        // Log optimization if enabled
        if ($optimized && isset($this->settings['log_optimizations']) && $this->settings['log_optimizations']) {
            $this->file_manager->log_optimization([
                'file' => $file_path,
                'type' => $file_ext,
                'original_size' => $optimized['original_size'],
                'optimized_size' => $optimized['optimized_size'],
                'saved_bytes' => $optimized['saved_bytes'],
                'saved_percent' => $optimized['saved_percent'],
            ]);
        }
        
        return $optimized;
    }
    
    /**
     * Optimize JPEG image.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the JPEG file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_jpeg($file_path) {
        $original_size = filesize($file_path);
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        // Try MozJPEG first if available
        if ($this->binary_detector->is_binary_available('mozjpeg')) {
            $tmp_file = $file_path . '.tmp';
            $cmd = sprintf('cjpeg -quality %d -optimize -progressive -outfile %s %s', 
                $quality, 
                escapeshellarg($tmp_file), 
                escapeshellarg($file_path)
            );
            
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($tmp_file)) {
                rename($tmp_file, $file_path);
                return $this->file_manager->calculate_savings($this->file_manager->get_destination_path($file_path, 'jpg.orig'), $file_path);
            }
        }
        
        // Try jpegoptim if available
        if ($this->binary_detector->is_binary_available('jpegoptim')) {
            $cmd = sprintf('jpegoptim -m%d --strip-all --all-progressive %s', 
                $quality, 
                escapeshellarg($file_path)
            );
            
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0) {
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                ];
            }
        }
        
        // Fallback to GD/Imagick
        return $this->optimize_with_gd($file_path, $quality);
    }
    
    /**
     * Optimize PNG image.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the PNG file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_png($file_path) {
        $original_size = filesize($file_path);
        
        // Currently we don't have a specific PNG optimizer in the requirements
        // We'll just convert to WebP/AVIF if enabled
        // For now, return original stats
        return [
            'original_size' => $original_size,
            'optimized_size' => $original_size,
            'saved_bytes' => 0,
            'saved_percent' => 0,
        ];
    }
    
    /**
     * Optimize WebP image.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the WebP file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_webp($file_path) {
        $original_size = filesize($file_path);
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        // Try cwebp if available
        if ($this->binary_detector->is_binary_available('cwebp')) {
            $tmp_file = $file_path . '.tmp';
            $cmd = sprintf('cwebp -q %d %s -o %s', 
                $quality, 
                escapeshellarg($file_path), 
                escapeshellarg($tmp_file)
            );
            
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($tmp_file)) {
                rename($tmp_file, $file_path);
                
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                ];
            }
        }
        
        // WebP is already optimized, just return stats
        return [
            'original_size' => $original_size,
            'optimized_size' => $original_size,
            'saved_bytes' => 0,
            'saved_percent' => 0,
        ];
    }
    
    /**
     * Convert image to WebP format.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the original file.
     * @return   string|false            Path to WebP file or false if failed.
     */
    private function convert_to_webp($file_path) {
        $webp_path = $this->file_manager->get_destination_path($file_path, 'webp');
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        // Try cwebp if available
        if ($this->binary_detector->is_binary_available('cwebp')) {
            $cmd = sprintf('cwebp -q %d %s -o %s', 
                $quality, 
                escapeshellarg($file_path), 
                escapeshellarg($webp_path)
            );
            
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($webp_path)) {
                return $webp_path;
            }
        }
        
        // Fallback to GD/Imagick for WebP conversion
        return $this->convert_to_webp_with_gd($file_path, $webp_path, $quality);
    }
    
    /**
     * Convert image to AVIF format.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the original file.
     * @return   string|false            Path to AVIF file or false if failed.
     */
    private function convert_to_avif($file_path) {
        $avif_path = $this->file_manager->get_destination_path($file_path, 'avif');
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        // Try avifenc if available
        if ($this->binary_detector->is_binary_available('avifenc')) {
            $speed = 6; // Medium speed, good quality balance
            $cmd = sprintf('avifenc --min 0 --max 63 --speed %d --quality %d %s %s', 
                $speed,
                $quality, 
                escapeshellarg($file_path), 
                escapeshellarg($avif_path)
            );
            
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($avif_path)) {
                return $avif_path;
            }
        }
        
        // No reliable fallback for AVIF conversion in PHP yet
        return false;
    }
    
    /**
     * Optimize image using GD library (fallback).
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @param    int       $quality      Quality setting (0-100).
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_with_gd($file_path, $quality) {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
            return false;
        }
        
        $original_size = filesize($file_path);
        $file_ext = $this->file_manager->get_extension($file_path);
        
        // Create image from file based on extension
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file_path);
                break;
                
            case 'png':
                $image = @imagecreatefrompng($file_path);
                break;
                
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($file_path);
                } else {
                    return false;
                }
                break;
                
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Create temporary file
        $tmp_file = $file_path . '.tmp';
        
        // Save image with new quality
        $success = false;
        
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($image, $tmp_file, $quality);
                break;
                
            case 'png':
                // For PNG, use a compression level between 0-9
                $png_quality = (9 - round(($quality / 100) * 9));
                $success = imagepng($image, $tmp_file, $png_quality);
                break;
                
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($image, $tmp_file, $quality);
                }
                break;
        }
        
        // Free memory
        imagedestroy($image);
        
        // If successful, replace original with optimized version
        if ($success && file_exists($tmp_file)) {
            rename($tmp_file, $file_path);
            
            $new_size = filesize($file_path);
            $saved_bytes = $original_size - $new_size;
            $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
            
            return [
                'original_size' => $original_size,
                'optimized_size' => $new_size,
                'saved_bytes' => $saved_bytes,
                'saved_percent' => $saved_percent,
            ];
        }
        
        // Clean up temp file if it exists
        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
        
        return false;
    }
    
    /**
     * Convert image to WebP using GD library (fallback).
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the original file.
     * @param    string    $webp_path    Path to save WebP file.
     * @param    int       $quality      Quality setting (0-100).
     * @return   string|false            Path to WebP file or false if failed.
     */
    private function convert_to_webp_with_gd($file_path, $webp_path, $quality) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $file_ext = $this->file_manager->get_extension($file_path);
        
        // Create image from file based on extension
        switch ($file_ext) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file_path);
                break;
                
            case 'png':
                $image = @imagecreatefrompng($file_path);
                
                // Handle transparency for PNG
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
                
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Save as WebP
        $success = imagewebp($image, $webp_path, $quality);
        
        // Free memory
        imagedestroy($image);
        
        return ($success && file_exists($webp_path)) ? $webp_path : false;
    }
} 