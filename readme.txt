=== WeirdoPress Image Optimizer ===
Contributors: shubhwadekar,weirdopress
Tags: image, compression, webp, avif, performance, optimization
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

A privacy-first, blazing fast, 100% local image compression plugin for WordPress — powered by Squoosh-quality compression. No APIs. No tracking. No bloat.

== Description ==

**WeirdoPress Image Optimizer** is a lightweight, privacy-focused image compression plugin that automatically processes your images using local binaries like cwebp, avifenc, and jpegoptim.

Unlike most WordPress image optimizers, WeirdoPress runs completely locally using system binaries and is 100% open-source and privacy-respecting. No data leaves your server, making it perfect for GDPR compliance.

= Key Features =

* **Automatic compression** on image upload
* **WebP conversion** for better performance
* **AVIF conversion** for even smaller file sizes
* **JPEG optimization** using mozjpeg or jpegoptim
* **100% local processing** - no data sent to external APIs
* **Privacy-first** - no tracking, no data collection
* **Smart fallbacks** when binaries aren't available
* **Backup originals** for safe optimization

= Why Choose WeirdoPress Image Optimizer? =

Most WordPress image optimizers are:
* Bloated
* Pushy with upsells
* Dependent on slow, paid APIs
* Leaky in terms of privacy

**WeirdoPress** is different:
* ✅ Compresses locally on your server  
* ✅ Uses AVIF, WebP, and MozJPEG (Squoosh-level formats)  
* ✅ Built in pure PHP — no Node.js or 3rd-party calls  
* ✅ Forever free and open-source (MIT license)

= Format Support =

| Format | Compressed | Codec Used |
|--------|------------|------------|
| `.jpg`, `.jpeg` | Yes | MozJPEG / jpegoptim |
| `.png` | Yes | cwebp / avifenc |
| `.webp` | Yes | Recompressed if needed |
| `.avif` | Yes | Via libavif tools |

= Requirements =

* PHP 7.4+
* `exec()` access on server
* Optional installed binaries:
  * `cwebp` (for WebP)
  * `avifenc` (for AVIF)
  * `jpegoptim` or `mozjpeg`

Don't worry — the plugin auto-detects which binaries are available and gracefully falls back to using PHP's built-in image handling if needed.

= Privacy =

This plugin:
* Does NOT collect any data about you or your site
* Does NOT send images to external services
* Does NOT include any tracking or telemetry
* Does NOT have any paid upgrades or upsells

= Open Source =

WeirdoPress Image Optimizer is 100% open source and developed on GitHub. Contributions, bug reports, and feature requests are welcome.

== Installation ==

= From WordPress.org =

1. Visit 'Plugins > Add New'
2. Search for 'WeirdoPress Image Optimizer'
3. Activate 'WeirdoPress Image Optimizer' from your Plugins page.

= From GitHub =

1. Download the latest release from GitHub
2. Upload the plugin files to the `/wp-content/plugins/weirdopress-image-optimizer` directory
3. Activate the plugin through the 'Plugins' screen in WordPress

= After Activation =

1. Go to 'Settings > Image Optimizer'
2. Configure your compression options
3. That's it! All future image uploads will be automatically optimized.

== Frequently Asked Questions ==

= Does this plugin require an API key or account? =

No! WeirdoPress Image Optimizer works 100% locally on your server with no external dependencies.

= Will this plugin slow down my site? =

No. The compression happens during upload and won't affect your site's loading speed. In fact, it will make your site faster by reducing image file sizes.

= Does this work with my existing images? =

Currently, the plugin only processes new uploads. Support for existing media library images is planned for a future release.

= What if the required binaries aren't installed on my server? =

The plugin will automatically fall back to using PHP's built-in GD or Imagick libraries for basic compression. However, for best results, we recommend installing the binaries. See the "Installation" section for instructions.

= Is this plugin compatible with other image-related plugins? =

Yes, generally it should work with most image galleries, sliders, and other image-related plugins. If you experience any compatibility issues, please report them on our GitHub page.

= Will this plugin work on shared hosting? =

Yes, as long as your host allows the PHP `exec()` function and has the necessary binaries installed (or allows you to install them). If not, the plugin will still work but with reduced functionality.

== Screenshots ==

1. Settings page with optimization options
2. Binary detection showing available compression tools
3. Simple, clean user interface

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic compression on upload
* WebP and AVIF support
* JPEG optimization with mozjpeg/jpegoptim
* Fallback to GD/Imagick when binaries are unavailable
* Binary detection for server capability assessment
* Settings page with format toggles and quality control

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Contributors & Developers ==

WeirdoPress Image Optimizer is an open-source plugin developed by the WordPress community. 