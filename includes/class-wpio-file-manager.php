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
     * Log optimization results
     *
     * @since    1.0.0
     * @param    array     $data    Optimization data
     */
    public function log_optimization($data) {
        // Skip logging if disabled
        if (empty($this->settings['log_optimizations'])) {
            return;
        }
        
        // Make sure we have all required data
        if (empty($data) || empty($data['file_path'])) {
            return;
        }
        
        // Get file information
        $file_info = pathinfo($data['file_path']);
        $original_extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';
        
        // Check for existing WebP and AVIF versions
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        $avif_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.avif';
        $original_path = $data['file_path'];
        
        // Track available formats
        $data['has_webp'] = file_exists($webp_path);
        $data['has_avif'] = file_exists($avif_path);
        
        // Get original file size
        if (!isset($data['original_size']) && file_exists($original_path)) {
            $data['original_size'] = filesize($original_path);
        }
        
        // Add original file type and MIME type
        $data['original_type'] = strtoupper($original_extension);
        if (!isset($data['original_mime_type'])) {
            $data['original_mime_type'] = $this->get_mime_type($original_path);
        }
        
        // Process each available format
        if ($data['has_webp']) {
            // Track WebP file size and MIME type
            $webp_size = filesize($webp_path);
            $data['webp_size'] = $webp_size;
            $data['webp_size_human'] = $this->format_bytes($webp_size);
            $data['webp_mime_type'] = 'image/webp';
            
            // Calculate WebP savings
            if (isset($data['original_size']) && $data['original_size'] > 0) {
                $webp_saved = $data['original_size'] - $webp_size;
                $data['webp_saved_bytes'] = $webp_saved;
                $data['webp_saved_percent'] = round(($webp_saved / $data['original_size']) * 100, 1);
                $data['webp_saved_human'] = $this->format_bytes($webp_saved);
                
                // If WebP is better than original, mark it as primary conversion
                if ($webp_saved > 0) {
                    $data['primary_conversion'] = 'webp';
                    $data['primary_path'] = $webp_path;
                }
            }
            
            // Add a separate log entry for the WebP conversion if not present
            $this->log_specific_format('webp', $webp_path, $original_path, $data);
        }
        
        if ($data['has_avif']) {
            // Track AVIF file size and MIME type
            $avif_size = filesize($avif_path);
            $data['avif_size'] = $avif_size;
            $data['avif_size_human'] = $this->format_bytes($avif_size);
            $data['avif_mime_type'] = 'image/avif';
            
            // Calculate AVIF savings
            if (isset($data['original_size']) && $data['original_size'] > 0) {
                $avif_saved = $data['original_size'] - $avif_size;
                $data['avif_saved_bytes'] = $avif_saved;
                $data['avif_saved_percent'] = round(($avif_saved / $data['original_size']) * 100, 1);
                $data['avif_saved_human'] = $this->format_bytes($avif_saved);
                
                // If AVIF is better than WebP (or there's no WebP), mark it as primary conversion
                if (!isset($data['primary_conversion']) || 
                    ($data['primary_conversion'] === 'webp' && $avif_saved > $data['webp_saved_bytes'])) {
                    $data['primary_conversion'] = 'avif';
                    $data['primary_path'] = $avif_path;
                }
            }
            
            // Add a separate log entry for the AVIF conversion if not present
            $this->log_specific_format('avif', $avif_path, $original_path, $data);
        }
        
        // If neither WebP nor AVIF is better, use original format as primary
        if (!isset($data['primary_conversion'])) {
            $data['primary_conversion'] = 'original';
            $data['primary_path'] = $original_path;
            // Use the optimized size if present, otherwise use original size
            $optimized_size = isset($data['optimized_size']) ? $data['optimized_size'] : $data['original_size'];
            $data['optimized_size'] = $optimized_size;
        } else {
            // Use the primary conversion's size as the optimized size
            $primary_format = $data['primary_conversion'];
            $data['optimized_size'] = $data[$primary_format . '_size'];
        }
        
        // Calculate overall savings based on primary conversion
        if (isset($data['original_size']) && isset($data['optimized_size']) && $data['original_size'] > 0) {
            $saved_bytes = $data['original_size'] - $data['optimized_size'];
            $data['saved_bytes'] = $saved_bytes;
            $data['savings_percent'] = round(($saved_bytes / $data['original_size']) * 100, 1);
            $data['saved_human'] = $this->format_bytes($saved_bytes);
        }
        
        // Add timestamp and formatted date
        $data['timestamp'] = time();
        $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
        
        // Add file information for UI display
        $data['filename'] = basename($data['file_path']);
        $data['file_path_relative'] = str_replace(wp_upload_dir()['basedir'] . '/', '', $data['file_path']);
        
        // Check if attachment ID is available
        if (!isset($data['attachment_id'])) {
            $data['attachment_id'] = $this->get_attachment_id_from_path($data['file_path']);
        }
        
        if ($data['attachment_id']) {
            // Store optimization data in post meta
            update_post_meta($data['attachment_id'], '_wpio_optimization_data', $data);
        }
        
        // Add log entry
        $logs = get_option('wpio_optimization_logs', array());
        array_unshift($logs, $data); // Add to beginning of array
        
        // Limit the number of log entries
        $max_logs = isset($this->settings['max_log_entries']) ? intval($this->settings['max_log_entries']) : 100;
        if (count($logs) > $max_logs) {
            $logs = array_slice($logs, 0, $max_logs);
        }
        
        update_option('wpio_optimization_logs', $logs);
    }
    
    /**
     * Log specific format conversion if not already present in logs
     * 
     * @since    1.0.0
     * @param    string    $format          Format name (webp, avif)
     * @param    string    $format_path     Path to the converted file
     * @param    string    $original_path   Path to the original file
     * @param    array     $data            Parent optimization data
     */
    private function log_specific_format($format, $format_path, $original_path, $data) {
        // Skip if already logged in recent entries
        $logs = get_option('wpio_optimization_logs', array());
        $format_filename = basename($format_path);
        
        // Check last 20 log entries to avoid duplicates
        $format_already_logged = false;
        $check_count = min(20, count($logs));
        for ($i = 0; $i < $check_count; $i++) {
            if (isset($logs[$i]['filename']) && $logs[$i]['filename'] === $format_filename) {
                $format_already_logged = true;
                break;
            }
        }
        
        if ($format_already_logged) {
            return;
        }
        
        // Create format-specific log entry
        $format_data = array(
            'file_path' => $format_path,
            'original_path' => $original_path,
            'filename' => $format_filename,
            'file_path_relative' => str_replace(wp_upload_dir()['basedir'] . '/', '', $format_path),
            'format' => $format,
            'conversion_format' => $format,
            'conversion_type' => 'converted',
            'original_type' => $data['original_type'],
            'original_size' => $data['original_size'],
            'original_mime_type' => $data['original_mime_type'],
            'optimized_size' => $data[$format . '_size'],
            'optimized_mime_type' => 'image/' . $format,
            'saved_bytes' => $data[$format . '_saved_bytes'],
            'savings_percent' => $data[$format . '_saved_percent'],
            'saved_human' => $data[$format . '_saved_human'],
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s'),
            'attachment_id' => $data['attachment_id']
        );
        
        // Add to logs
        array_unshift($logs, $format_data);
        
        // Maintain log size limit
        $max_logs = isset($this->settings['max_log_entries']) ? intval($this->settings['max_log_entries']) : 100;
        if (count($logs) > $max_logs) {
            $logs = array_slice($logs, 0, $max_logs);
        }
        
        update_option('wpio_optimization_logs', $logs);
    }
    
    /**
     * Get attachment ID from file path.
     * 
     * @since    1.0.0
     * @param    string    $file_path    Path to the file.
     * @return   int|false               Attachment ID or false if not found.
     */
    private function get_attachment_id_from_path($file_path) {
        $upload_dir = wp_upload_dir();
        
        // Make path relative to the upload directory
        $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);
        
        global $wpdb;
        
        // Find attachment by comparing the path in the metadata
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
            $relative_path
        ));
        
        // If not found, try searching in modified attachment metadata
        if (!$attachment_id) {
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like($relative_path) . '%'
            ));
        }
        
        return $attachment_id ? (int) $attachment_id : false;
    }
    
    /**
     * Get mime type of a file.
     * 
     * @since    1.0.0
     * @param    string    $file_path    Path to the file.
     * @return   string                  Mime type.
     */
    public function get_mime_type($file_path) {
        if (!file_exists($file_path)) {
            return '';
        }
        
        $mime_type = '';
        
        // Try using WordPress function first
        if (function_exists('wp_check_filetype')) {
            $file_info = wp_check_filetype($file_path);
            $mime_type = $file_info['type'];
        }
        
        // If WordPress function didn't work, try PHP's function
        if (empty($mime_type) && function_exists('mime_content_type')) {
            $mime_type = mime_content_type($file_path);
        }
        
        // If all else fails, determine by extension
        if (empty($mime_type)) {
            $ext = $this->get_extension($file_path);
            $mime_map = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                'gif' => 'image/gif',
            ];
            
            $mime_type = isset($mime_map[$ext]) ? $mime_map[$ext] : '';
        }
        
        return $mime_type;
    }
} 