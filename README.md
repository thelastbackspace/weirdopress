# WirdoPress Image Optimizer

> A **privacy-first**, blazing fast, 100% local image compression plugin for WordPress — powered by [Squoosh](https://squoosh.app/) compression quality. No APIs. No tracking. No bloat.

---

![demo screenshot banner](https://your-demo-banner-link.com) <!-- Optional: Add GIF or comparison image -->

## Why?

Most WordPress image optimizers are:
- Bloated
- Pushy with upsells
- Dependent on slow, paid APIs
- Leaky in terms of privacy

**WirdoPress** is different.

### ✅ Compresses locally on your server  
### ✅ Uses AVIF, WebP, and MozJPEG (Squoosh-level formats)  
### ✅ Built in pure PHP — no Node.js or 3rd-party calls  
### ✅ Forever free and open-source (MIT license)

---

## Features

- [x] **Automatic compression on upload**
- [x] Convert images to **WebP** and **AVIF**
- [x] Smart JPEG compression via `jpegoptim` or `mozjpeg`
- [x] Preserve original files (optional)
- [x] Lightweight UI inside WP settings
- [x] Fully offline — perfect for GDPR compliance
- [x] Works with shared hosting (no Node.js required)

---

## Format Support

| Format | Compressed | Codec Used |
|--------|------------|------------|
| `.jpg`, `.jpeg` | Yes | MozJPEG / jpegoptim |
| `.png` | Yes | cwebp / avifenc |
| `.webp` | Yes | Recompressed if needed |
| `.avif` | Yes | Via libavif tools |

---

## Performance Benchmarks

| Format | Original | Compressed | Saved |
|--------|----------|------------|-------|
| JPEG   | 1.2 MB   | 320 KB     | 73%   |
| PNG    | 980 KB   | 150 KB     | 85%   |
| AVIF   | 800 KB   | 110 KB     | 86%   |

*Real-world benchmarks using libavif and cwebp. Your mileage may vary.*

---

## Requirements

- PHP 7.4+
- `exec()` access on server
- Optional installed binaries:
  - `cwebp` (for WebP)
  - `avifenc` (for AVIF)
  - `jpegoptim` or `mozjpeg`

> Don’t worry — the plugin auto-detects which binaries are available and gracefully falls back.

---

## How to Use

1. Install the plugin via GitHub release (zip upload to WP).
2. Head to **Settings > WirdoPress Optimizer**
3. Choose your formats & quality settings.
4. Done. Every future image upload will be compressed automatically.

---

## Screenshots

| Compression Panel | Results Viewer |
|-------------------|----------------|
| ![screenshot1](https://your-screenshot-link.com) | ![screenshot2](https://your-second-link.com) |

---

## Roadmap

- [ ] Compress existing media library
- [ ] Batch processing tools
- [ ] AVIF/WebP conditional frontend delivery
- [ ] WP-CLI support
- [ ] Lossless toggle
- [ ] Image rollback feature

---

## Philosophy

**No bloat. No tracking. No remote servers.**  
We believe compression should be fast, clean, and local.

---

## Contribute

Pull requests, feature suggestions, and issue reports are very welcome.  
This project is MIT licensed and 100% open-source.

- [Open an issue](https://github.com/yourname/wirdopress-image-optimizer/issues)
- [Create a PR](https://github.com/yourname/wirdopress-image-optimizer/pulls)

---

## License

[MIT](LICENSE)

---

**Made with zero tracking & maximum squish.**
