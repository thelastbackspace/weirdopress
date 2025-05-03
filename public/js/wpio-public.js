/**
 * WebP and AVIF image replacement script
 * 
 * This script detects browser support for WebP and AVIF 
 * and replaces eligible image sources with these formats if available.
 */
(function() {
    'use strict';

    // Check for WebP support
    function checkWebpSupport(callback) {
        var webpTest = new Image();
        webpTest.onload = function() { callback(true); };
        webpTest.onerror = function() { callback(false); };
        webpTest.src = 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=';
    }

    // Check for AVIF support
    function checkAvifSupport(callback) {
        var avifTest = new Image();
        avifTest.onload = function() { callback(true); };
        avifTest.onerror = function() { callback(false); };
        avifTest.src = 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A=';
    }

    // Keep track of failed image URLs to avoid retrying
    var failedUrls = new Set();

    // Replace image sources with WebP or AVIF
    function replaceImageSources(webpSupported, avifSupported) {
        // If neither format is supported, exit
        if (!webpSupported && !avifSupported) {
            return;
        }

        // Determine preferred format (AVIF first if supported)
        var preferredFormat = avifSupported ? 'avif' : (webpSupported ? 'webp' : null);
        
        if (!preferredFormat) {
            return;
        }

        // Get all images on the page
        var images = document.querySelectorAll('img');
        
        images.forEach(function(img) {
            if (!img.src) return;
            
            // Skip if not jpg/jpeg/png images
            if (!/\.(jpe?g|png)$/i.test(img.src)) return;
            
            // Create a test URL for the WebP/AVIF version
            var newSrc = img.src.replace(/\.(jpe?g|png)$/i, '.' + preferredFormat);
            
            // Skip if we've already tried and failed with this URL
            if (failedUrls.has(newSrc)) return;
            
            // Skip external images for safety
            if (img.src.indexOf(window.location.origin) !== 0) return;
            
            // Create a test image to check if the WebP/AVIF version exists
            var testImage = new Image();
            testImage.onload = function() {
                // If the WebP/AVIF version loaded successfully, replace the source
                img.src = newSrc;
            };
            testImage.onerror = function() {
                // Add this URL to the failed set to avoid future attempts
                failedUrls.add(newSrc);
            };
            testImage.src = newSrc;
        });
        
        // Handle srcset as well
        var pictureElements = document.querySelectorAll('picture');
        pictureElements.forEach(function(picture) {
            var sources = picture.querySelectorAll('source');
            var format = preferredFormat;
            
            // Check if there's already a source with proper type
            var hasModernFormat = false;
            sources.forEach(function(source) {
                if (source.type === 'image/webp' || source.type === 'image/avif') {
                    hasModernFormat = true;
                }
            });
            
            // If there's already a source with WebP or AVIF, skip this picture
            if (hasModernFormat) return;
            
            // Add a new source for WebP/AVIF
            var img = picture.querySelector('img');
            if (img && img.src && /\.(jpe?g|png)$/i.test(img.src)) {
                var newSrcset = img.src.replace(/\.(jpe?g|png)$/i, '.' + format);
                
                // Skip if we've already tried and failed with this URL
                if (failedUrls.has(newSrcset)) return;
                
                // Create a test image to check if the WebP/AVIF version exists
                var testImage = new Image();
                testImage.onload = function() {
                    // Only add the source if the file actually exists
                    var newSource = document.createElement('source');
                    newSource.srcset = newSrcset;
                    newSource.type = 'image/' + format;
                    picture.insertBefore(newSource, picture.firstChild);
                };
                testImage.onerror = function() {
                    // Add this URL to the failed set
                    failedUrls.add(newSrcset);
                };
                testImage.src = newSrcset;
            }
        });
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        checkWebpSupport(function(webpSupported) {
            checkAvifSupport(function(avifSupported) {
                replaceImageSources(webpSupported, avifSupported);
                
                // Also listen for AJAX-loaded content
                if (window.MutationObserver) {
                    var observer = new MutationObserver(function(mutations) {
                        // Check if any nodes were added
                        var hasNewNodes = mutations.some(function(mutation) {
                            return mutation.addedNodes.length > 0;
                        });
                        
                        if (hasNewNodes) {
                            // Delay slightly to ensure images are fully loaded
                            setTimeout(function() {
                                replaceImageSources(webpSupported, avifSupported);
                            }, 100);
                        }
                    });
                    
                    // Observe changes to the body and its descendants
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        });
    });
})(); 