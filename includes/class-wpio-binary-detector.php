<?php
/**
 * Binary detection functionality
 *
 * @link       https://github.com/weirdopress/image-optimizer
 * @since      1.0.0
 *
 * @package    WeirdoPressImageOptimizer
 */

/**
 * Binary detection class.
 *
 * This class checks for available binaries needed for image optimization.
 * It detects cwebp, avifenc, jpegoptim, and mozjpeg binaries.
 *
 * @since      1.0.0
 * @package    WeirdoPressImageOptimizer
 * @author     WeirdoPress
 */
class WPIO_Binary_Detector {

    /**
     * Array of required binaries to check.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $binaries    Array of binary names to check for.
     */
    private $binaries = [
        'cwebp' => [
            'name' => 'cwebp',
            'command' => 'cwebp -version',
            'required' => false,
            'available' => false,
            'version' => '',
            'feature' => 'WebP conversion',
        ],
        'avifenc' => [
            'name' => 'avifenc',
            'command' => 'avifenc --version',
            'required' => false,
            'available' => false,
            'version' => '',
            'feature' => 'AVIF conversion',
        ],
        'jpegoptim' => [
            'name' => 'jpegoptim',
            'command' => 'jpegoptim --version',
            'required' => false,
            'available' => false,
            'version' => '',
            'feature' => 'JPEG optimization',
        ],
        'mozjpeg' => [
            'name' => 'mozjpeg',
            'command' => 'cjpeg -version',
            'required' => false,
            'available' => false,
            'version' => '',
            'feature' => 'JPEG optimization (MozJPEG)',
        ],
    ];

    /**
     * Check the availability of required binaries.
     * 
     * @since    1.0.0
     * @return   array    Binary status array.
     */
    public function check_binaries() {
        // Skip if we've already checked recently (cached)
        $cached_status = get_transient('wpio_binary_check');
        if ($cached_status !== false) {
            // Update the stored settings
            update_option('wpio_binary_status', $cached_status);
            return $cached_status;
        }
        
        // Check for exec availability first
        if (!function_exists('exec')) {
            foreach ($this->binaries as $key => $binary) {
                $this->binaries[$key]['available'] = false;
                $this->binaries[$key]['version'] = 'exec() function disabled';
            }
            
            // Save the results
            update_option('wpio_binary_status', $this->binaries);
            set_transient('wpio_binary_check', $this->binaries, 15 * MINUTE_IN_SECONDS);
            
            return $this->binaries;
        }
        
        // Check each binary
        foreach ($this->binaries as $key => $binary) {
            $output = [];
            $return_code = 0;
            
            // Execute the command (safely)
            exec($binary['command'] . ' 2>&1', $output, $return_code);
            
            // Process the output
            if ($return_code === 0 || $return_code === 1) { // Some binaries return 1 on --version
                $this->binaries[$key]['available'] = true;
                
                // Extract version info if available
                $version = $this->extract_version($output, $key);
                $this->binaries[$key]['version'] = $version;
            } else {
                $this->binaries[$key]['available'] = false;
                $this->binaries[$key]['version'] = 'Not found or not executable';
            }
        }
        
        // Save the results
        update_option('wpio_binary_status', $this->binaries);
        set_transient('wpio_binary_check', $this->binaries, 15 * MINUTE_IN_SECONDS);
        
        return $this->binaries;
    }
    
    /**
     * Extract version information from binary output.
     *
     * @since    1.0.0
     * @param    array    $output    Output from exec command.
     * @param    string   $binary    Binary key.
     * @return   string              Version string if found, otherwise "Available".
     */
    private function extract_version($output, $binary) {
        if (empty($output)) {
            return 'Available';
        }
        
        $output_text = implode(' ', $output);
        
        // Extract version based on binary
        switch ($binary) {
            case 'cwebp':
                if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+)/', $output_text, $matches)) {
                    return $matches[1];
                }
                break;
                
            case 'avifenc':
                if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+)/', $output_text, $matches)) {
                    return $matches[1];
                }
                break;
                
            case 'jpegoptim':
                if (preg_match('/v([0-9]+\.[0-9]+\.[0-9]+)/', $output_text, $matches)) {
                    return $matches[1];
                }
                break;
                
            case 'mozjpeg':
                if (preg_match('/([0-9]+\.[0-9]+)/', $output_text, $matches)) {
                    return $matches[1];
                }
                break;
        }
        
        return 'Available';
    }
    
    /**
     * Get the status of a specific binary.
     *
     * @since    1.0.0
     * @param    string   $binary    Binary name.
     * @return   array|bool          Binary status array or false if not found.
     */
    public function get_binary_status($binary) {
        $status = get_option('wpio_binary_status', []);
        
        if (empty($status)) {
            $status = $this->check_binaries();
        }
        
        return isset($status[$binary]) ? $status[$binary] : false;
    }
    
    /**
     * Check if a specific binary is available.
     *
     * @since    1.0.0
     * @param    string   $binary    Binary name.
     * @return   bool                True if available, false otherwise.
     */
    public function is_binary_available($binary) {
        $status = $this->get_binary_status($binary);
        
        if (!$status) {
            return false;
        }
        
        return $status['available'];
    }
} 