<?php
/**
 * Enhanced PHP-only image optimization
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.1.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * PHP-only Image optimizer class.
 *
 * This class provides advanced image optimization using only PHP's native
 * functions and libraries (GD/Imagick), designed to work on shared hosting
 * where exec() is disabled. It implements custom algorithms for better
 * compression without relying on external binaries.
 *
 * @since      1.1.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_PHP_Optimizer {
    
    /**
     * Plugin settings.
     *
     * @since    1.1.0
     * @access   private
     * @var      array    $settings    Plugin settings.
     */
    private $settings;
    
    /**
     * Available PHP extensions and features.
     *
     * @since    1.1.0
     * @access   private
     * @var      array    $capabilities    Available PHP capabilities.
     */
    private $capabilities;
    
    /**
     * File manager instance.
     *
     * @since    1.1.0
     * @access   private
     * @var      WPIO_File_Manager    $file_manager    File manager instance.
     */
    private $file_manager;
    
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1.0
     */
    public function __construct() {
        $this->settings = get_option('wpio_settings', []);
        $this->file_manager = new WPIO_File_Manager();
        $this->capabilities = $this->detect_capabilities();
    }
    
    /**
     * Detect available PHP image manipulation capabilities.
     *
     * @since    1.1.0
     * @return   array    Array of detected capabilities.
     */
    public function detect_capabilities() {
        $capabilities = [
            'gd' => extension_loaded('gd'),
            'imagick' => extension_loaded('imagick'),
            'webp_support' => false,
            'avif_support' => false,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
        
        // Check for WebP support in GD
        if ($capabilities['gd'] && function_exists('imagewebp')) {
            $capabilities['webp_support'] = true;
        }
        
        // Check for WebP support in Imagick
        if ($capabilities['imagick']) {
            $imagick = new Imagick();
            $formats = $imagick->queryFormats();
            
            if (in_array('WEBP', $formats)) {
                $capabilities['webp_support'] = true;
            }
            
            if (in_array('AVIF', $formats)) {
                $capabilities['avif_support'] = true;
            }
        }
        
        return $capabilities;
    }
    
    /**
     * Optimize an image using PHP-only methods.
     *
     * @since    1.1.0
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
                
            case 'gif':
                $optimized = $this->optimize_gif($file_path);
                break;
                
            case 'webp':
                $optimized = $this->optimize_webp($file_path);
                break;
                
            case 'avif':
                // AVIF is already optimized in most cases
                $optimized = [
                    'original_size' => $original_size,
                    'optimized_size' => $original_size,
                    'saved_bytes' => 0,
                    'saved_percent' => 0,
                ];
                break;
        }
        
        // Convert to WebP if enabled and supported
        if (isset($this->settings['enable_webp']) && 
            $this->settings['enable_webp'] && 
            $this->capabilities['webp_support'] && 
            $file_ext !== 'webp' && 
            $file_ext !== 'avif') {
            $this->convert_to_webp($file_path);
        }
        
        // Convert to AVIF if enabled and supported
        if (isset($this->settings['enable_avif']) && 
            $this->settings['enable_avif'] && 
            $this->capabilities['avif_support'] && 
            $file_ext !== 'avif') {
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
                'method' => 'php-only',
            ]);
        }
        
        return $optimized;
    }
    
    /**
     * Optimize JPEG image with advanced PHP techniques.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the JPEG file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_jpeg($file_path) {
        $original_size = filesize($file_path);
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        // Try Imagick first as it generally provides better results
        if ($this->capabilities['imagick']) {
            try {
                $image = new Imagick($file_path);
                
                // Strip metadata
                $image->stripImage();
                
                // Set compression type and quality
                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($quality);
                
                // Set interlace scheme (progressive JPEG)
                $image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
                
                // Write optimized image
                $image->writeImage($file_path);
                $image->clear();
                
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                    'method' => 'imagick',
                ];
            } catch (Exception $e) {
                // Fall back to GD if Imagick fails
            }
        }
        
        // Fall back to GD
        if ($this->capabilities['gd']) {
            $image = @imagecreatefromjpeg($file_path);
            
            if (!$image) {
                return false;
            }
            
            // Create temporary file
            $tmp_file = $file_path . '.tmp';
            
            // Save with new quality
            $success = imagejpeg($image, $tmp_file, $quality);
            
            // Free memory
            imagedestroy($image);
            
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
                    'method' => 'gd',
                ];
            }
            
            // Clean up temp file if it exists
            if (file_exists($tmp_file)) {
                unlink($tmp_file);
            }
        }
        
        return false;
    }
    
    /**
     * Optimize PNG image with color quantization and other techniques.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the PNG file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_png($file_path) {
        $original_size = filesize($file_path);
        $quality = isset($this->settings['png_quality']) ? intval($this->settings['png_quality']) : 9; // 0-9 for PNG
        
        // Imagick provides better PNG optimization options
        if ($this->capabilities['imagick']) {
            try {
                $image = new Imagick($file_path);
                
                // Check if the image has transparency
                $has_transparency = $this->check_transparency($image);
                
                // Strip metadata
                $image->stripImage();
                
                // If image doesn't have transparency and has many colors, we can reduce colors
                if (!$has_transparency && $image->getImageColors() > 256) {
                    // Reduce to 256 colors with dithering for better visual quality
                    $image->quantizeImage(256, Imagick::COLORSPACE_RGB, 0, true, false);
                }
                
                // Set compression level (0-9)
                $image->setImageCompressionQuality($quality * 10); // Convert 0-9 to 0-90 range
                $image->setOption('png:compression-level', $quality);
                
                // Write optimized image
                $image->writeImage($file_path);
                $image->clear();
                
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                    'method' => 'imagick',
                ];
            } catch (Exception $e) {
                // Fall back to GD if Imagick fails
            }
        }
        
        // Fall back to GD
        if ($this->capabilities['gd']) {
            $image = @imagecreatefrompng($file_path);
            
            if (!$image) {
                return false;
            }
            
            // Check if image has transparency
            $has_transparency = $this->check_png_transparency_gd($image);
            
            // If no transparency, we can convert to a palette image
            if (!$has_transparency) {
                // Convert to true color if it's not
                imagepalettetotruecolor($image);
                
                // Custom color reduction (could be enhanced with a custom quantization algorithm)
                $width = imagesx($image);
                $height = imagesy($image);
                $palette_image = imagecreatetruecolor($width, $height);
                
                // Create a new image with max 256 colors
                imagecopy($palette_image, $image, 0, 0, 0, 0, $width, $height);
                imagetruecolortopalette($image, true, 256);
            }
            
            // Create temporary file
            $tmp_file = $file_path . '.tmp';
            
            // PNG compression level (0-9)
            $success = imagepng($image, $tmp_file, $quality);
            
            // Free memory
            imagedestroy($image);
            
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
                    'method' => 'gd',
                ];
            }
            
            // Clean up temp file if it exists
            if (file_exists($tmp_file)) {
                unlink($tmp_file);
            }
        }
        
        return false;
    }
    
    /**
     * Optimize GIF image.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the GIF file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_gif($file_path) {
        $original_size = filesize($file_path);
        
        // Imagick provides better GIF optimization
        if ($this->capabilities['imagick']) {
            try {
                $image = new Imagick($file_path);
                
                // Strip metadata
                $image->stripImage();
                
                // Optimize the image frames
                $image = $this->optimize_gif_frames($image);
                
                // Write optimized image
                $image->writeImages($file_path, true);
                $image->clear();
                
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                    'method' => 'imagick',
                ];
            } catch (Exception $e) {
                // GIFs are better handled by Imagick, so we'll return original stats if it fails
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $original_size,
                    'saved_bytes' => 0,
                    'saved_percent' => 0,
                    'method' => 'none',
                ];
            }
        }
        
        // GD is not great for GIFs, especially animated ones, so we'll return original stats
        return [
            'original_size' => $original_size,
            'optimized_size' => $original_size,
            'saved_bytes' => 0,
            'saved_percent' => 0,
            'method' => 'none',
        ];
    }
    
    /**
     * Optimize WebP image.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the WebP file.
     * @return   array|false             Optimization results or false if failed.
     */
    private function optimize_webp($file_path) {
        // WebP is already pretty optimized, but we can try to re-encode with specific settings
        $original_size = filesize($file_path);
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        if ($this->capabilities['imagick'] && $this->capabilities['webp_support']) {
            try {
                $image = new Imagick($file_path);
                
                // Strip metadata
                $image->stripImage();
                
                // Set WebP compression
                $image->setImageFormat('WEBP');
                $image->setOption('webp:lossless', 'false');
                $image->setOption('webp:method', '6'); // Higher is better but slower
                $image->setOption('webp:alpha-quality', '85');
                $image->setImageCompressionQuality($quality);
                
                // Write optimized image
                $image->writeImage($file_path);
                $image->clear();
                
                $new_size = filesize($file_path);
                $saved_bytes = $original_size - $new_size;
                $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
                
                return [
                    'original_size' => $original_size,
                    'optimized_size' => $new_size,
                    'saved_bytes' => $saved_bytes,
                    'saved_percent' => $saved_percent,
                    'method' => 'imagick',
                ];
            } catch (Exception $e) {
                // Fall back to GD if Imagick fails
            }
        }
        
        // Try GD if WebP is supported
        if ($this->capabilities['gd'] && $this->capabilities['webp_support'] && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($file_path);
            
            if (!$image) {
                return false;
            }
            
            // Create temporary file
            $tmp_file = $file_path . '.tmp';
            
            // Save with new quality
            $success = imagewebp($image, $tmp_file, $quality);
            
            // Free memory
            imagedestroy($image);
            
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
                    'method' => 'gd',
                ];
            }
            
            // Clean up temp file if it exists
            if (file_exists($tmp_file)) {
                unlink($tmp_file);
            }
        }
        
        // If we can't optimize, return original stats
        return [
            'original_size' => $original_size,
            'optimized_size' => $original_size,
            'saved_bytes' => 0,
            'saved_percent' => 0,
            'method' => 'none',
        ];
    }
    
    /**
     * Convert image to WebP format using PHP functions.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the original file.
     * @return   string|false            Path to WebP file or false if failed.
     */
    private function convert_to_webp($file_path) {
        if (!$this->capabilities['webp_support']) {
            return false;
        }
        
        $webp_path = $this->file_manager->get_destination_path($file_path, 'webp');
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        if ($this->capabilities['imagick']) {
            try {
                $image = new Imagick($file_path);
                
                // Strip metadata
                $image->stripImage();
                
                // Set WebP format and options
                $image->setImageFormat('WEBP');
                $image->setOption('webp:lossless', 'false');
                $image->setOption('webp:method', '6'); // Higher is better but slower
                $image->setOption('webp:alpha-quality', '85');
                $image->setImageCompressionQuality($quality);
                
                // Write WebP file
                $image->writeImage($webp_path);
                $image->clear();
                
                return file_exists($webp_path) ? $webp_path : false;
            } catch (Exception $e) {
                // Fall back to GD if Imagick fails
            }
        }
        
        // Try GD
        if ($this->capabilities['gd'] && function_exists('imagewebp')) {
            $file_ext = $this->file_manager->get_extension($file_path);
            
            switch ($file_ext) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($file_path);
                    break;
                    
                case 'png':
                    $image = @imagecreatefrompng($file_path);
                    
                    // Handle transparency
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
        
        return false;
    }
    
    /**
     * Convert image to AVIF format using PHP functions.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the original file.
     * @return   string|false            Path to AVIF file or false if failed.
     */
    private function convert_to_avif($file_path) {
        if (!$this->capabilities['avif_support']) {
            return false;
        }
        
        $avif_path = $this->file_manager->get_destination_path($file_path, 'avif');
        $quality = isset($this->settings['jpeg_quality']) ? intval($this->settings['jpeg_quality']) : 75;
        
        if ($this->capabilities['imagick']) {
            try {
                $image = new Imagick($file_path);
                
                // Strip metadata
                $image->stripImage();
                
                // Set AVIF format
                $image->setImageFormat('AVIF');
                $image->setImageCompressionQuality($quality);
                
                // Write AVIF file
                $image->writeImage($avif_path);
                $image->clear();
                
                return file_exists($avif_path) ? $avif_path : false;
            } catch (Exception $e) {
                // AVIF is relatively new, so if Imagick fails, we probably can't do it
                return false;
            }
        }
        
        // GD doesn't generally support AVIF output yet in most PHP installations
        return false;
    }
    
    /**
     * Check if an Imagick PNG image has transparency.
     *
     * @since    1.1.0
     * @param    Imagick   $image    Imagick image object.
     * @return   bool                True if image has transparency.
     */
    private function check_transparency($image) {
        // Check if the image has an alpha channel
        return $image->getImageAlphaChannel();
    }
    
    /**
     * Check if a GD PNG image has transparency.
     *
     * @since    1.1.0
     * @param    resource  $image    GD image resource.
     * @return   bool                True if image has transparency.
     */
    private function check_png_transparency_gd($image) {
        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample the image to check for transparent pixels
        // We don't check every pixel for performance, just a sample
        $sample_size = min(100, max($width, $height));
        $step_x = max(1, floor($width / $sample_size));
        $step_y = max(1, floor($height / $sample_size));
        
        for ($y = 0; $y < $height; $y += $step_y) {
            for ($x = 0; $x < $width; $x += $step_x) {
                $color_index = imagecolorat($image, $x, $y);
                $color = imagecolorsforindex($image, $color_index);
                
                // If any pixel has alpha < 127 (not fully opaque), it has transparency
                if (isset($color['alpha']) && $color['alpha'] > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Optimize GIF frames for animated GIFs.
     *
     * @since    1.1.0
     * @param    Imagick   $image    Imagick image object.
     * @return   Imagick             Optimized Imagick image.
     */
    private function optimize_gif_frames($image) {
        // This is a simplified optimization
        // Full GIF optimization is complex and can be expanded
        
        $image->optimizeImageLayers();
        
        return $image;
    }
    
    /**
     * Compress image in chunks to avoid memory issues.
     *
     * @since    1.1.0
     * @param    string    $file_path    Path to the image file.
     * @param    int       $chunk_size   Chunk size in pixels.
     * @return   bool                    True if successful.
     */
    public function process_large_image_in_chunks($file_path, $chunk_size = 1000) {
        // Implementation for large image processing would go here
        // This is a placeholder for future implementation
        
        return false;
    }
} 