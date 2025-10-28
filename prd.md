# Product Requirements Document (PRD)

## Plugin Name (Working Title)
**"WirdoPress Image Optimizer"**
_A privacy-first, open-source image compression plugin using modern formats like WebP and AVIF._

---

## 1. Overview

This plugin automatically compresses and converts images uploaded to a WordPress website using the most modern and efficient image codecs available (WebP, AVIF, MozJPEG). The goal is to improve page load times, SEO scores, and Core Web Vitals without relying on any external APIs or services.

Unlike most compression plugins, **WirdoPress runs completely locally** using system binaries (e.g., `cwebp`, `avifenc`, `jpegoptim`) and is **100% open-source and privacy-respecting**.

---

## 2. Objectives

- Deliver **Squoosh-level image compression** using local binaries and PHP
- Automatically compress images upon upload
- Support advanced formats like **WebP and AVIF**
- Ensure plugin is **lightweight**, **self-contained**, and easy to use
- Make the plugin **fully open-source**, with potential for community contributions
- Maintain **GDPR compliance** by avoiding any 3rd-party API calls

---

## 3. Target Audience

- WordPress developers and site owners
- Privacy-conscious users
- Performance-focused SEO professionals
- Agencies managing multiple client sites
- Open-source enthusiasts looking for Squoosh-quality compression in WordPress

---

## 4. Features

### 4.1 Core Features (V1)
| Feature | Description |
|--------|-------------|
| Image Auto-Compression | On image upload, compress and/or convert to optimized format |
| WebP Support | Convert JPEG/PNG to `.webp` using `cwebp` |
| AVIF Support | Convert JPEG/PNG to `.avif` using `avifenc` (if available) |
| JPEG Optimization | Lossy or lossless compression using `jpegoptim` or `mozjpeg` |
| PHP-Only | Uses PHP `exec()` to call local binaries (no Node.js) |
| Plugin Settings Page | Toggle features (enable WebP, AVIF, set quality, etc.) |
| Fallbacks | Graceful fallback if certain binaries are missing |

---

### 4.2 Planned (Post-V1)
| Feature | Description |
|--------|-------------|
| Convert Existing Media Library | Scan and compress previously uploaded media |
| Frontend Format Switching | Serve AVIF/WebP conditionally via `<picture>` or rewrite rules |
| Batch Compression | Button to compress existing images in bulk |
| Multi-Site Support | Work seamlessly on WordPress multisite installs |
| Logging | See how much space is saved per image |
| Compression Modes | Lossless vs lossy toggle per format |
| WP CLI Support | Run compressions via command line |

---

## 5. Technical Requirements

### PHP
- Minimum: PHP 7.4
- Recommended: PHP 8.0+

### Server Binaries
- `cwebp` (Google WebP encoder)
- `avifenc` (from libavif)
- `jpegoptim` or `mozjpeg`

> Note: Plugin will check for these binaries and fallback gracefully if any are unavailable.

---

## 6. Architecture

### Workflow
1. User uploads an image.
2. Hook into `wp_handle_upload` or `wp_generate_attachment_metadata`.
3. Check file type (JPEG, PNG).
4. Run relevant binary:
   - `cwebp` for `.webp`
   - `avifenc` for `.avif`
   - `jpegoptim` or `mozjpeg` for JPEG compression
5. Replace or attach optimized versions in media library (settings dependent).
6. Optionally store original for rollback (optional toggle).

---

## 7. UX & Settings

### Settings Page (under Settings > Image Optimizer)
- [ ] Enable WebP conversion (checkbox)
- [ ] Enable AVIF conversion (checkbox)
- [ ] Set JPEG quality (slider/input)
- [ ] Preserve original files (checkbox)
- [ ] Auto-replace in media library (checkbox)
- [ ] Log optimization results (checkbox)
- [ ] Check binary availability (status panel)

---

## 8. Branding & Community

- **GitHub Repo:** Public with clean README, issue templates, roadmap
- **Logo & Identity:** Minimal, modern (optional mascot — maybe a squished camera?)
- **Promotion Plan:**
  - Launch on GitHub
  - Submit to wp.org
  - Reddit (r/WordPress, r/selfhosted)
  - Hacker News + Product Hunt
  - YouTube demo/tutorial
  - Post from Karwade’s LinkedIn and blog

---

## 9. Open Source License

- **License:** MIT or GPLv3
- Contributions welcome via GitHub
- Encourage forks and community pull requests

---

## 10. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Missing binaries on server | Graceful fallback with warnings on settings page |
| Host blocks `exec()` | Fallback to GD compression (lower quality) |
| AVIF not supported everywhere | Option to disable and fallback to WebP |
| Plugin bloat | Keep JS and CSS minimal, load only on settings page |
| User confusion over formats | Add docs, tooltips, "Why AVIF?" explanation in UI |

---

## 11. Success Metrics

- [ ] 1,000 GitHub stars in 3 months
- [ ] 10k+ downloads on WordPress.org in 6 months
- [ ] 20+ community contributors
- [ ] Benchmarks: 30-50% average image size reduction
- [ ] Featured in 5+ WordPress newsletters/blogs

---

## 12. Timeline

| Milestone | Date |
|-----------|------|
| V1 Plugin Core | May 2025 |
| WebP & AVIF support | May 2025 |
| Settings UI | June 2025 |
| GitHub Launch | June 2025 |
| WordPress.org Submission | July 2025 |

---

