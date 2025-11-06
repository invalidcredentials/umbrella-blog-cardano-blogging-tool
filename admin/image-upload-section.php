<!-- Featured Images Section -->
<div class="sidebar-section">
    <div class="section-header collapsed" onclick="toggleSection(this)">
        <span><span style="color: var(--umb-cyan);">▢</span> Featured Images</span>
        <span class="toggle-icon">▼</span>
    </div>
    <div class="section-content collapsed">
        <p class="section-description">Drag and drop images into the zones below. Each zone shows the optimal size.</p>

        <!-- OG Image (Social Sharing) -->
        <div class="image-upload-zone" data-image-type="og_image">
            <div class="upload-zone-header">
                <strong>Open Graph Image</strong>
                <span class="recommended-size">1200 x 630px</span>
            </div>
            <div class="upload-dropzone" id="og-image-dropzone">
                <input type="hidden" name="og_image" id="og_image_url" value="<?php echo $post ? esc_attr($post->og_image) : ''; ?>">
                <div class="dropzone-content">
                    <?php if ($post && $post->og_image): ?>
                        <img src="<?php echo esc_url($post->og_image); ?>" class="preview-image" id="og-image-preview">
                        <button type="button" class="remove-image-btn" onclick="removeImage('og_image')">✕ Remove</button>
                    <?php else: ?>
                        <div class="dropzone-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <p>Drag & Drop or Click to Upload</p>
                            <small>Best for Facebook, LinkedIn, Discord</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="upload-progress" id="og-image-progress" style="display: none;">
                <div class="progress-bar"></div>
            </div>
            <div class="alt-text-field" id="og-image-alt-field" style="display: <?php echo ($post && $post->og_image) ? 'block' : 'none'; ?>;">
                <label for="og_image_alt">Alt Text <span style="color: #999; font-weight: normal;">(for accessibility & SEO)</span></label>
                <input type="text" name="og_image_alt" id="og_image_alt" placeholder="Describe what's in the image..." value="<?php echo $post ? esc_attr($post->og_image_alt ?? '') : ''; ?>">
                <small>Be descriptive: "Cardano blockchain diagram showing transaction flow"</small>
            </div>
        </div>

        <!-- Twitter Card Image -->
        <div class="image-upload-zone" data-image-type="twitter_image">
            <div class="upload-zone-header">
                <strong>Twitter Card Image</strong>
                <span class="recommended-size">1200 x 675px</span>
            </div>
            <div class="upload-dropzone" id="twitter-image-dropzone">
                <input type="hidden" name="twitter_image" id="twitter_image_url" value="<?php echo $post ? esc_attr($post->twitter_image) : ''; ?>">
                <div class="dropzone-content">
                    <?php if ($post && $post->twitter_image): ?>
                        <img src="<?php echo esc_url($post->twitter_image); ?>" class="preview-image" id="twitter-image-preview">
                        <button type="button" class="remove-image-btn" onclick="removeImage('twitter_image')">✕ Remove</button>
                    <?php else: ?>
                        <div class="dropzone-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <p>Drag & Drop or Click to Upload</p>
                            <small>Optimized for Twitter/X sharing</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="upload-progress" id="twitter-image-progress" style="display: none;">
                <div class="progress-bar"></div>
            </div>
            <div class="alt-text-field" id="twitter-image-alt-field" style="display: <?php echo ($post && $post->twitter_image) ? 'block' : 'none'; ?>;">
                <label for="twitter_image_alt">Alt Text <span style="color: #999; font-weight: normal;">(for accessibility & SEO)</span></label>
                <input type="text" name="twitter_image_alt" id="twitter_image_alt" placeholder="Describe what's in the image..." value="<?php echo $post ? esc_attr($post->twitter_image_alt ?? '') : ''; ?>">
                <small>Be descriptive for screen readers and SEO</small>
            </div>
        </div>

        <div class="image-tips">
            <strong><span style="color: var(--umb-cyan);">◉</span> Pro Tips:</strong>
            <ul>
                <li>Use high-quality JPG or PNG images</li>
                <li>Keep file size under 5MB for fast loading</li>
                <li>Include text/branding for better recognition</li>
                <li>If you only upload OG image, it'll be used for Twitter too</li>
            </ul>
        </div>
    </div>
</div>

<style>
.image-upload-zone {
    margin-bottom: var(--umb-space-lg);
    padding: var(--umb-space-md);
    background: rgba(0, 0, 0, 0.2);
    border-radius: var(--umb-radius-md);
    border: 1px solid var(--umb-glass-border);
}

.upload-zone-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--umb-space-sm);
}

.upload-zone-header strong {
    color: var(--umb-text-primary);
    font-size: 13px;
}

.recommended-size {
    font-size: 11px;
    color: var(--umb-cyan);
    background: rgba(0, 230, 255, 0.1);
    padding: 3px 8px;
    border-radius: var(--umb-radius-sm);
    font-weight: 600;
    border: 1px solid rgba(0, 230, 255, 0.2);
}

.upload-dropzone {
    position: relative;
    border: 2px dashed var(--umb-glass-border);
    border-radius: var(--umb-radius-md);
    background: rgba(0, 0, 0, 0.3);
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all var(--umb-transition-base);
    overflow: hidden;
}

.upload-dropzone:hover {
    border-color: var(--umb-cyan);
    background: rgba(0, 230, 255, 0.05);
    box-shadow: 0 0 12px rgba(0, 230, 255, 0.2);
}

.upload-dropzone.dragover {
    border-color: var(--umb-cyan);
    background: rgba(0, 230, 255, 0.1);
    border-style: solid;
    box-shadow: 0 0 20px rgba(0, 230, 255, 0.3);
}

.dropzone-content {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--umb-space-lg);
}

.dropzone-placeholder {
    text-align: center;
    color: var(--umb-text-secondary);
}

.dropzone-placeholder svg {
    color: var(--umb-text-muted);
    margin-bottom: var(--umb-space-sm);
}

.dropzone-placeholder p {
    margin: var(--umb-space-sm) 0 var(--umb-space-xs) 0;
    font-weight: 600;
    color: var(--umb-text-primary);
    font-size: 14px;
}

.dropzone-placeholder small {
    color: var(--umb-text-muted);
    font-size: 12px;
}

.preview-image {
    max-width: 100%;
    max-height: 160px;
    border-radius: var(--umb-radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    border: 1px solid var(--umb-glass-border);
}

.remove-image-btn {
    margin-top: var(--umb-space-sm);
    padding: var(--umb-space-xs) var(--umb-space-md);
    background: var(--umb-glass-bg);
    color: var(--umb-danger);
    border: 1px solid var(--umb-danger);
    border-radius: var(--umb-radius-sm);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--umb-transition-base);
}

.remove-image-btn:hover {
    background: rgba(255, 51, 102, 0.1);
    box-shadow: 0 0 12px rgba(255, 51, 102, 0.3);
}

.upload-progress {
    margin-top: var(--umb-space-sm);
    height: 4px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: var(--umb-radius-sm);
    overflow: hidden;
    border: 1px solid var(--umb-glass-border);
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--umb-cyan), var(--umb-magenta));
    width: 0%;
    transition: width 0.3s ease;
}

.image-tips {
    margin-top: var(--umb-space-md);
    padding: var(--umb-space-md);
    background: rgba(0, 230, 255, 0.05);
    border-left: 3px solid var(--umb-cyan);
    border-radius: var(--umb-radius-sm);
}

.image-tips strong {
    color: var(--umb-cyan);
    display: block;
    margin-bottom: var(--umb-space-sm);
    font-size: 12px;
}

.image-tips ul {
    margin: 0;
    padding-left: var(--umb-space-lg);
}

.image-tips li {
    font-size: 11px;
    color: var(--umb-text-secondary);
    line-height: 1.6;
    margin-bottom: var(--umb-space-xs);
}

.section-description {
    font-size: 12px;
    color: var(--umb-text-secondary);
    margin-bottom: var(--umb-space-md);
}

.alt-text-field {
    margin-top: var(--umb-space-md);
    padding: var(--umb-space-md);
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--umb-glass-border);
    border-radius: var(--umb-radius-md);
}

.alt-text-field label {
    display: block;
    font-weight: 600;
    font-size: 12px;
    color: var(--umb-text-primary);
    margin-bottom: var(--umb-space-xs);
}

.alt-text-field input {
    width: 100%;
    padding: var(--umb-space-sm) var(--umb-space-md);
    border: 1px solid var(--umb-glass-border);
    border-radius: var(--umb-radius-md);
    font-size: 13px;
    box-sizing: border-box;
    background: rgba(0, 0, 0, 0.3);
    color: var(--umb-text-primary);
    transition: all var(--umb-transition-base);
}

.alt-text-field input:focus {
    border-color: var(--umb-cyan);
    outline: none;
    box-shadow: 0 0 8px rgba(0, 230, 255, 0.2);
}

.alt-text-field small {
    display: block;
    margin-top: var(--umb-space-xs);
    font-size: 11px;
    color: var(--umb-text-muted);
}
</style>

<script>
// Image upload handler
function initImageUpload() {
    const dropzones = {
        'og_image': document.getElementById('og-image-dropzone'),
        'twitter_image': document.getElementById('twitter-image-dropzone')
    };

    Object.keys(dropzones).forEach(imageType => {
        const dropzone = dropzones[imageType];
        if (!dropzone) return;

        // Click to upload
        dropzone.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-image-btn')) return;

            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/jpeg,image/jpg,image/png,image/webp';
            input.onchange = e => handleFileSelect(e.target.files, imageType);
            input.click();
        });

        // Drag and drop
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');

            const files = e.dataTransfer.files;
            handleFileSelect(files, imageType);
        });
    });
}

function handleFileSelect(files, imageType) {
    if (files.length === 0) return;

    const file = files[0];

    // Validate file type
    if (!file.type.match('image.*')) {
        alert('Please upload an image file (JPG, PNG, or WebP)');
        return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be less than 5MB');
        return;
    }

    // Show progress
    const progressEl = document.getElementById(imageType + '-progress');
    if (progressEl) {
        progressEl.style.display = 'block';
        progressEl.querySelector('.progress-bar').style.width = '30%';
    }

    // Upload via AJAX
    const formData = new FormData();
    formData.append('action', 'umbrella_blog_upload_image');
    formData.append('nonce', '<?php echo wp_create_nonce('umbrella_blog_upload'); ?>');
    formData.append('image', file);
    formData.append('image_type', imageType);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (progressEl) {
            progressEl.querySelector('.progress-bar').style.width = '100%';
            setTimeout(() => {
                progressEl.style.display = 'none';
                progressEl.querySelector('.progress-bar').style.width = '0%';
            }, 500);
        }

        if (data.success) {
            // Update hidden input
            document.getElementById(imageType + '_url').value = data.data.url;

            // Update preview
            const dropzone = document.getElementById(imageType.replace('_', '-') + '-dropzone');
            const content = dropzone.querySelector('.dropzone-content');

            content.innerHTML = `
                <img src="${data.data.url}" class="preview-image" id="${imageType}-preview">
                <button type="button" class="remove-image-btn" onclick="removeImage('${imageType}')">✕ Remove</button>
            `;

            // Show alt text field
            const altField = document.getElementById(imageType + '-alt-field');
            if (altField) {
                altField.style.display = 'block';
            }
        } else {
            alert('Upload failed: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        if (progressEl) {
            progressEl.style.display = 'none';
        }
        console.error('Upload error:', error);
        alert('Upload failed. Please try again.');
    });
}

function removeImage(imageType) {
    if (!confirm('Remove this image?')) return;

    // Clear hidden input
    document.getElementById(imageType + '_url').value = '';

    // Clear alt text
    const altInput = document.getElementById(imageType + '_alt');
    if (altInput) {
        altInput.value = '';
    }

    // Hide alt text field
    const altField = document.getElementById(imageType + '-alt-field');
    if (altField) {
        altField.style.display = 'none';
    }

    // Reset dropzone
    const dropzone = document.getElementById(imageType.replace('_', '-') + '-dropzone');
    const content = dropzone.querySelector('.dropzone-content');

    const imageLabels = {
        'og_image': 'Best for Facebook, LinkedIn, Discord',
        'twitter_image': 'Optimized for Twitter/X sharing'
    };

    content.innerHTML = `
        <div class="dropzone-placeholder">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <p>Drag & Drop or Click to Upload</p>
            <small>${imageLabels[imageType]}</small>
        </div>
    `;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initImageUpload);
</script>
