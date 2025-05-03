# WeirdoPress Image Optimizer 🚀

A **privacy-first, open-source image compression plugin** for WordPress that processes images locally on your server without sending data to external services.

## 🔒 Privacy-First Approach

Unlike most WordPress image optimizers that send your images to cloud services:

* **100% Local Processing** - Images never leave your server
* **No API Keys Required** - Works immediately after installation
* **No Data Collection** - Zero tracking or telemetry
* **Forever Free** - No premium tiers or hidden costs
* **Open Source** - Full transparency in how your images are processed

## ✨ Features

* **Automatic Compression** on image upload
* **WebP & AVIF Conversion** for modern browsers
* **Preserves Originals** (optional)
* **Media Library Integration** with optimization stats
* **Detailed Logs** for transparency
* **Browser Detection** for serving the right format
* **Compatible** with most gallery and media plugins

## 🛠️ How It Works

WeirdoPress Image Optimizer works by:

1. **Detecting available system binaries** (cwebp, avifenc, jpegoptim, etc.)
2. **Compressing new uploads** automatically using these powerful tools
3. **Creating modern formats** (WebP/AVIF) alongside original images
4. **Serving optimized versions** to compatible browsers
5. **Tracking optimization results** in your media library

## 📊 Compression Results

| Original Format | Size Reduction | Formats Created |
|-----------------|----------------|-----------------|
| JPEG | 40-80% | WebP, AVIF |
| PNG | 50-90% | WebP, AVIF |

## 📋 Requirements

* WordPress 5.6+
* PHP 7.4+
* `exec()` function available on server
* For best results: cwebp, avifenc, jpegoptim binaries (optional)

## 🚀 Installation

### From WordPress.org (Coming Soon)

1. Visit 'Plugins > Add New'
2. Search for 'WeirdoPress Image Optimizer'
3. Click 'Install Now' and then 'Activate'

### Manual Installation

1. Download the latest `.zip` file from [Releases](https://github.com/weirdopress/image-optimizer/releases)
2. In your WordPress admin, go to Plugins > Add New > Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Visit Settings > Image Optimizer to configure

### From Source

```bash
# Clone the repository
git clone https://github.com/weirdopress/image-optimizer.git

# Create a zip file
cd image-optimizer
./package.sh

# Upload the resulting ZIP through the WordPress admin
```

## ⚙️ Configuration

After installation:

1. Go to Settings > Image Optimizer
2. Enable or disable WebP and AVIF conversion
3. Set quality levels for each format
4. Choose whether to preserve original files
5. Save changes

All new uploads will be automatically optimized!

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

* [libwebp](https://developers.google.com/speed/webp/docs/cwebp) for WebP conversion
* [libavif](https://github.com/AOMediaCodec/libavif) for AVIF conversion
* [mozjpeg](https://github.com/mozilla/mozjpeg) for JPEG optimization
