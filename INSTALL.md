# WeirdoPress Image Optimizer - Installation Guide

## Requirements

Before you install the plugin, please ensure your server meets the following requirements:

- WordPress 5.6 or higher
- PHP 7.4 or higher
- PHP `exec()` function enabled
- Optional (but recommended) binaries:
  - `cwebp` for WebP conversion
  - `avifenc` for AVIF conversion
  - `jpegoptim` or `mozjpeg` for JPEG optimization

## Installation

### From ZIP File

1. Download the plugin ZIP file from the GitHub repository.
2. Go to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New**.
4. Click the **Upload Plugin** button at the top of the page.
5. Click **Choose File** and select the ZIP file you downloaded.
6. Click **Install Now**.
7. After installation is complete, click **Activate**.

### Manual Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Extract the ZIP file to your computer.
3. Upload the `weirdopress-image-optimizer` folder to the `/wp-content/plugins/` directory on your server.
4. Activate the plugin through the 'Plugins' menu in WordPress.

## Binary Installation (Optional but Recommended)

For the best performance, we recommend installing the following binaries on your server:

### Installing cwebp (WebP)

#### Ubuntu/Debian
```
sudo apt-get update
sudo apt-get install webp
```

#### CentOS/RHEL
```
sudo yum install libwebp-tools
```

#### macOS
```
brew install webp
```

### Installing avifenc (AVIF)

#### Ubuntu/Debian
```
sudo apt-get update
sudo apt-get install libavif-bin
```

#### macOS
```
brew install libavif
```

### Installing jpegoptim

#### Ubuntu/Debian
```
sudo apt-get update
sudo apt-get install jpegoptim
```

#### CentOS/RHEL
```
sudo yum install jpegoptim
```

#### macOS
```
brew install jpegoptim
```

### Installing MozJPEG

#### Ubuntu/Debian
```
sudo apt-get update
sudo apt-get install mozjpeg
```

#### macOS
```
brew install mozjpeg
```

## Configuration

1. After activating the plugin, go to **Settings > Image Optimizer**.
2. Configure the settings according to your preferences:
   - **Enable WebP conversion**: Convert images to WebP format
   - **Enable AVIF conversion**: Convert images to AVIF format
   - **JPEG Quality**: Set the quality level for JPEG compression (0-100)
   - **Preserve original files**: Keep backups of original images
   - **Auto-replace in media library**: Replace original images with optimized versions
   - **Log optimization results**: Keep track of space saved

3. The **Binary Status** section shows which binaries are available on your server.

## Usage

Once activated and configured, the plugin works automatically:

1. **New Uploads**: All new image uploads will be automatically compressed based on your settings.
2. **Existing Images**: Currently, optimization of existing images is not supported in v1.0.0 but will be added in a future update.

## Troubleshooting

### Binary Detection Issues

If the plugin cannot detect the binaries even though they're installed:

1. Check if the binaries are in the system PATH.
2. Try running the binary commands manually via SSH to ensure they work.
3. Check if PHP's `exec()` function is enabled in your PHP configuration.

### Permission Issues

If the plugin cannot create optimized images:

1. Ensure the WordPress uploads directory has proper write permissions.
2. Check if there are any file permission issues in your server logs.

### WebP/AVIF Not Working

If WebP or AVIF conversion doesn't work:

1. Check if the required binaries are installed and detected.
2. Ensure your server has enough memory and processing power for image conversion.
3. Try with smaller images first to see if there are resource constraints.

## Support

For assistance or to report bugs, please visit our GitHub issues page:
https://github.com/weirdopress/image-optimizer/issues 