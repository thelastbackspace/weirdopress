<?php
/**
 * File management functionality
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * File manager class.
 *
 * This class handles file operations for the plugin such as:
 * - Backing up original files
 * - Managing file paths
 * - Handling file replacements
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_File_Manager {
    
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
     */
    public function __construct() {
        $this->settings = get_option('wpio_settings', []);
    }
    
    /**
     * Backup original file if preserve_originals is enabled.
     * 
     * @since    1.0.0
     * @param    string    $file_path    Path to the file to backup.
     * @return   bool|string             Backup path if successful, false otherwise.
     */
    public function backup_original($file_path) {
        if (!isset($this->settings['preserve_originals']) || !$this->settings['preserve_originals']) {
            return false;
        }
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Create backup directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $backup_dir = trailingslashit($upload_dir['basedir']) . 'wpio-backups';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            
            // Create index.php to prevent directory listing
            file_put_contents($backup_dir . '/index.php', "<?php\n// Silence is golden.");
            
            // Create .htaccess to protect the directory
            file_put_contents($backup_dir . '/.htaccess', "Deny from all");
        }
        
        // Create a backup path that maintains the original structure
        $relative_path = str_replace(trailingslashit($upload_dir['basedir']), '', $file_path);
        $backup_path = trailingslashit($backup_dir) . $relative_path;
        
        // Create directory structure if needed
        $backup_dir_path = dirname($backup_path);
        if (!file_exists($backup_dir_path)) {
            wp_mkdir_p($backup_dir_path);
        }
        
        // Copy the file
        if (copy($file_path, $backup_path)) {
            return $backup_path;
        }
        
        return false;
    }
    
    /**
     * Restore original file from backup.
     * 
     * @since    1.0.0
     * @param    string    $file_path    Path to the current file.
     * @return   bool                    True if restored, false otherwise.
     */
    public function restore_original($file_path) {
        $upload_dir = wp_upload_dir();
        $backup_dir = trailingslashit($upload_dir['basedir']) . 'wpio-backups';
        
        $relative_path = str_replace(trailingslashit($upload_dir['basedir']), '', $file_path);
        $backup_path = trailingslashit($backup_dir) . $relative_path;
        
        if (!file_exists($backup_path)) {
            return false;
        }
        
        return copy($backup_path, $file_path);
    }
    
    /**
     * Get the file extension.
     * 
     * @since    1.0.0
     * @param    string    $file_path    File path.
     * @return   string                  File extension without dot.
     */
    public function get_extension($file_path) {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if file is an image.
     * 
     * @since    1.0.0
     * @param    string    $file_path    File path.
     * @return   bool                    True if image, false otherwise.
     */
    public function is_image($file_path) {
        $ext = $this->get_extension($file_path);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        
        return in_array($ext, $allowed_extensions);
    }
    
    /**
     * Generate destination path for converted images.
     * 
     * @since    1.0.0
     * @param    string    $file_path    Original file path.
     * @param    string    $format       Destination format (webp, avif).
     * @return   string                  Destination file path.
     */
    public function get_destination_path($file_path, $format) {
        $info = pathinfo($file_path);
        return $info['dirname'] . '/' . $info['filename'] . '.' . $format;
    }
    
    /**
     * Get file size in a human-readable format.
     * 
     * @since    1.0.0
     * @param    string    $file_path    File path.
     * @return   string                  Human-readable file size.
     */
    public function get_human_filesize($file_path) {
        if (!file_exists($file_path)) {
            return '0 B';
        }
        
        $bytes = filesize($file_path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Calculate size savings between original and optimized file.
     * 
     * @since    1.0.0
     * @param    string    $original_path     Original file path.
     * @param    string    $optimized_path    Optimized file path.
     * @return   array                        Array with size information.
     */
    public function calculate_savings($original_path, $optimized_path) {
        if (!file_exists($original_path) || !file_exists($optimized_path)) {
            return [
                'original_size' => 0,
                'optimized_size' => 0,
                'saved_bytes' => 0,
                'saved_percent' => 0,
                'original_human' => '0 B',
                'optimized_human' => '0 B',
                'saved_human' => '0 B',
            ];
        }
        
        $original_size = filesize($original_path);
        $optimized_size = filesize($optimized_path);
        $saved_bytes = $original_size - $optimized_size;
        $saved_percent = ($original_size > 0) ? round(($saved_bytes / $original_size) * 100, 2) : 0;
        
        return [
            'original_size' => $original_size,
            'optimized_size' => $optimized_size,
            'saved_bytes' => $saved_bytes,
            'saved_percent' => $saved_percent,
            'original_human' => $this->get_human_filesize($original_path),
            'optimized_human' => $this->get_human_filesize($optimized_path),
            'saved_human' => $this->format_bytes($saved_bytes),
        ];
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
     * Log optimization results.
     * 
     * @since    1.0.0
     * @param    array     $data    Optimization data to log.
     */
    public function log_optimization($data) {
        if (!isset($this->settings['log_optimizations']) || !$this->settings['log_optimizations']) {
            return;
        }
        
        $log = get_option('wpio_optimization_log', []);
        
        // Limit log size to 1000 entries
        if (count($log) >= 1000) {
            array_shift($log);
        }
        
        $log[] = array_merge($data, ['time' => time()]);
        
        update_option('wpio_optimization_log', $log);
    }
} 