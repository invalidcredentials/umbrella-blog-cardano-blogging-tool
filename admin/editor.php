<?php
// Get existing categories and tags for dropdowns
global $wpdb;
$categories_table = $wpdb->prefix . 'umbrella_blog_categories';
$tags_table = $wpdb->prefix . 'umbrella_blog_tags';

$all_categories = $wpdb->get_results("SELECT * FROM {$categories_table} ORDER BY name");
$all_tags = $wpdb->get_results("SELECT * FROM {$tags_table} ORDER BY name");

// Get post's existing categories and tags
$post_categories = array();
$post_tags = array();
if ($post) {
    $post_categories_table = $wpdb->prefix . 'umbrella_blog_post_categories';
    $post_tags_table = $wpdb->prefix . 'umbrella_blog_post_tags';

    $post_categories = $wpdb->get_col($wpdb->prepare(
        "SELECT category_id FROM {$post_categories_table} WHERE post_id = %d",
        $post->id
    ));

    $post_tag_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT tag_id FROM {$post_tags_table} WHERE post_id = %d",
        $post->id
    ));

    // Get tag names
    if (!empty($post_tag_ids)) {
        $placeholders = implode(',', array_fill(0, count($post_tag_ids), '%d'));
        $post_tags = $wpdb->get_results($wpdb->prepare(
            "SELECT name FROM {$tags_table} WHERE id IN ($placeholders)",
            $post_tag_ids
        ));
    }
}

// Auto-generate defaults from post data
$meta_title = $post ? ($post->meta_title ?: $post->title) : '';
$meta_description = $post ? ($post->meta_description ?: $post->excerpt) : '';
?>

<div class="wrap umbrella-admin-page umbrella-blog-editor">
    <h1 style="font-size: 32px; font-weight: 700; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xl);">
        <span style="color: var(--umb-cyan);"><?php echo $post ? '✎' : '✎'; ?></span> <?php echo $post ? 'Edit Post' : 'New Post'; ?>
    </h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=umbrella-blog&action=' . ($post ? 'edit&id=' . $post->id : 'new'))); ?>">
        <?php wp_nonce_field('umbrella_blog_save', 'umbrella_blog_nonce'); ?>
        <input type="hidden" name="umbrella_blog_action" value="<?php echo $post ? 'update' : 'create'; ?>">
        <?php if ($post): ?>
            <input type="hidden" name="post_id" value="<?php echo $post->id; ?>">
        <?php endif; ?>
        <input type="hidden" name="editor_mode" id="editor_mode" value="<?php echo $post ? esc_attr($post->editor_mode) : 'richtext'; ?>">
        <input type="hidden" name="word_count" id="word_count" value="<?php echo $post ? $post->word_count : 0; ?>">
        <input type="hidden" name="reading_time" id="reading_time" value="<?php echo $post ? $post->reading_time : 0; ?>">

        <div class="editor-layout">
            <!-- Main Content Area -->
            <div class="editor-main">
                <!-- Title -->
                <div class="form-field">
                    <input type="text" id="title" name="title" placeholder="Enter your post title..." value="<?php echo $post ? esc_attr($post->title) : ''; ?>" required>
                </div>

                <!-- Excerpt -->
                <div class="form-field">
                    <label for="excerpt">Excerpt <span class="hint">(Short summary for listings and meta description)</span></label>
                    <textarea id="excerpt" name="excerpt" rows="2" placeholder="A compelling summary of your post..."><?php echo $post ? esc_textarea($post->excerpt) : ''; ?></textarea>
                    <div class="char-counter" id="excerpt-counter">0 / 160 characters</div>
                </div>

                <!-- Editor Mode Toggle -->
                <div class="form-field">
                    <label>Editor Mode</label>
                    <div class="editor-mode-toggle">
                        <button type="button" class="mode-btn" data-mode="richtext" onclick="switchEditorMode('richtext')">
                            <span style="color: var(--umb-cyan);">✎</span> Minimal Rich Text
                        </button>
                        <button type="button" class="mode-btn" data-mode="markdown" onclick="switchEditorMode('markdown')">
                            <span style="color: var(--umb-cyan);">◈</span> Markdown
                        </button>
                    </div>
                </div>

                <!-- Content Editor -->
                <div class="form-field">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required><?php echo $post ? esc_textarea($post->content) : ''; ?></textarea>
                    <div class="stats-display" id="content-stats">
                        <div class="stat-item">
                            <span>Words:</span>
                            <span class="stat-value" id="word-count-display">0</span>
                        </div>
                        <div class="stat-item">
                            <span>Reading Time:</span>
                            <span class="stat-value" id="reading-time-display">0 min</span>
                        </div>
                    </div>
                </div>

                <!-- Markdown Preview -->
                <div id="markdown-preview-container" style="display: none;">
                    <div class="preview-label">Preview</div>
                    <div id="markdown-preview" class="markdown-preview"></div>
                </div>

                <!-- Action Buttons -->
                <div class="button-group">
                    <button type="submit" name="status" value="draft" class="umb-btn umb-btn-secondary"><span style="color: var(--umb-cyan);">▭</span> Save Draft</button>
                    <button type="submit" name="status" value="published" class="umb-btn umb-btn-primary"><span style="color: var(--umb-cyan);">◉</span> Publish</button>
                    <a href="?page=umbrella-blog" class="umb-btn umb-btn-secondary">Cancel</a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="editor-sidebar">
                <!-- Cardano Signature Section -->
                <?php if ($post && $post->status === 'published'): ?>
                <div class="sidebar-section" style="border-color: #00E6FF;">
                    <div class="section-header" style="background: rgba(0, 230, 255, 0.05); border-bottom-color: #00E6FF;">
                        <span><span style="color: var(--umb-cyan);">⚿</span> Cardano Signature</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <?php if (!empty($post->signature_tx_hash)): ?>
                            <!-- Already signed -->
                            <div class="signature-status-signed">
                                <p style="color: #00E6FF; font-weight: 600; margin-bottom: 10px;">✅ Signed on Cardano</p>
                                <?php if (!empty($post->signature_handle)): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0; padding: 10px; background: rgba(0, 230, 255, 0.05); border-radius: 6px;">
                                        <?php if (!empty($post->signature_handle_image)): ?>
                                            <img src="<?php echo esc_url($post->signature_handle_image); ?>"
                                                 style="width: 48px; height: 48px; border-radius: 8px; border: 2px solid #00E6FF; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-size: 16px; font-weight: 600; color: #00E6FF;">
                                                <?php echo esc_html($post->signature_handle); ?>
                                            </div>
                                            <div style="font-size: 11px; opacity: 0.7;">Author Handle</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <p style="margin: 5px 0;"><strong>Signed:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($post->signed_at)); ?></p>
                                <p style="margin: 5px 0;">
                                    <strong>TX:</strong>
                                    <a href="https://<?php echo $post->signature_wallet_address && strpos($post->signature_wallet_address, 'addr1') === 0 ? 'cardanoscan.io' : 'preprod.cardanoscan.io'; ?>/transaction/<?php echo esc_attr($post->signature_tx_hash); ?>"
                                       target="_blank" rel="noopener" style="color: #00E6FF; word-break: break-all;">
                                        <?php echo substr($post->signature_tx_hash, 0, 16); ?>...
                                    </a>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Not yet signed -->
                            <div class="signature-status-unsigned">
                                <?php
                                // Show preview of selected handle
                                $selected_handle = get_option('cardano_blog_signer_selected_handle', '');
                                if (!empty($selected_handle)):
                                ?>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding: 10px; background: rgba(0, 230, 255, 0.05); border-radius: 6px;">
                                        <div style="width: 32px; height: 32px; background: rgba(0, 230, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                            👤
                                        </div>
                                        <div>
                                            <div style="font-size: 14px; font-weight: 600; color: #00E6FF;">
                                                <?php echo esc_html($selected_handle); ?>
                                            </div>
                                            <div style="font-size: 10px; opacity: 0.7;">Will sign as this handle</div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <p style="margin-bottom: 15px; color: var(--umb-text-secondary);">
                                    Sign this post on the Cardano blockchain to prove authorship.
                                </p>
                                <button type="button" id="sign-post-btn" class="umb-btn umb-btn-primary" style="width: 100%;">
                                    <span style="color: var(--umb-cyan);">⚿</span> Sign This Post
                                </button>
                                <p style="margin-top: 10px; font-size: 11px; color: var(--umb-text-muted);">
                                    Cost: ~0.17 ADA (~$0.10 USD)
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Categories & Tags Section -->
                <div class="sidebar-section">
                    <div class="section-header" onclick="toggleSection(this)">
                        <span><span style="color: var(--umb-cyan);">▤</span> Categories & Tags</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-field">
                            <label>Categories</label>
                            <div class="category-list">
                                <?php if (empty($all_categories)): ?>
                                    <p style="color: #666; font-size: 12px;">No categories yet. Enter category names below.</p>
                                <?php else: ?>
                                    <?php foreach ($all_categories as $cat): ?>
                                        <div class="category-item">
                                            <input type="checkbox" name="categories[]" value="<?php echo $cat->id; ?>"
                                                <?php echo in_array($cat->id, $post_categories) ? 'checked' : ''; ?>
                                                id="cat-<?php echo $cat->id; ?>">
                                            <label for="cat-<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-field">
                            <label>New Categories <span class="hint">(comma-separated)</span></label>
                            <input type="text" name="new_categories" placeholder="Cardano Development, Tutorials">
                        </div>

                        <div class="form-field">
                            <label>Tags <span class="hint">(comma-separated)</span></label>
                            <div class="tags-input-wrapper">
                                <input type="text" id="tags-input" name="tags"
                                    value="<?php echo !empty($post_tags) ? implode(', ', array_column($post_tags, 'name')) : ''; ?>"
                                    placeholder="PHP, Cardano, Ed25519, Smart Contracts">
                                <div class="tag-suggestions" id="tag-suggestions"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Basics Section -->
                <div class="sidebar-section">
                    <div class="section-header collapsed" onclick="toggleSection(this)">
                        <span><span style="color: var(--umb-cyan);">⌕</span> SEO Basics</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content collapsed">
                        <div class="form-field">
                            <label>Meta Title <span class="auto-generated" id="meta-title-auto" style="display: <?php echo empty($meta_title) ? 'inline-block' : 'none'; ?>">Auto</span></label>
                            <input type="text" name="meta_title" id="meta-title" value="<?php echo esc_attr($meta_title); ?>" maxlength="60" placeholder="Leave blank to use post title">
                            <div class="char-counter" id="meta-title-counter">0 / 60 characters</div>
                        </div>

                        <div class="form-field">
                            <label>Meta Description <span class="auto-generated" id="meta-desc-auto" style="display: <?php echo empty($meta_description) ? 'inline-block' : 'none'; ?>">Auto</span></label>
                            <textarea name="meta_description" id="meta-description" rows="3" maxlength="160" placeholder="Leave blank to use excerpt"><?php echo esc_textarea($meta_description); ?></textarea>
                            <div class="char-counter" id="meta-desc-counter">0 / 160 characters</div>
                        </div>

                        <div class="form-field">
                            <label>Focus Keyword <span class="hint">(Primary SEO keyword)</span></label>
                            <input type="text" name="focus_keyword" value="<?php echo $post ? esc_attr($post->focus_keyword) : ''; ?>" placeholder="cardano php wallet">
                        </div>

                        <div class="form-field">
                            <label>Slug <span class="auto-generated">Auto</span></label>
                            <input type="text" name="slug" id="slug" value="<?php echo $post ? esc_attr($post->slug) : ''; ?>" placeholder="auto-generated-from-title">
                        </div>

                        <div class="form-field">
                            <label>Meta Robots</label>
                            <select name="meta_robots">
                                <option value="index,follow" <?php echo ($post && $post->meta_robots === 'index,follow') || !$post ? 'selected' : ''; ?>>Index, Follow (Default)</option>
                                <option value="noindex,follow" <?php echo $post && $post->meta_robots === 'noindex,follow' ? 'selected' : ''; ?>>No Index, Follow</option>
                                <option value="index,nofollow" <?php echo $post && $post->meta_robots === 'index,nofollow' ? 'selected' : ''; ?>>Index, No Follow</option>
                                <option value="noindex,nofollow" <?php echo $post && $post->meta_robots === 'noindex,nofollow' ? 'selected' : ''; ?>>No Index, No Follow</option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php include plugin_dir_path(__FILE__) . 'image-upload-section.php'; ?>

                <!-- Open Graph / Social Section -->
                <div class="sidebar-section">
                    <div class="section-header collapsed" onclick="toggleSection(this)">
                        <span><span style="color: var(--umb-cyan);">▭</span> Open Graph / Social Text</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content collapsed">
                        <div class="form-field">
                            <label>OG Title <span class="auto-generated">Auto</span></label>
                            <input type="text" name="og_title" value="<?php echo $post ? esc_attr($post->og_title) : ''; ?>" placeholder="Defaults to meta title">
                        </div>

                        <div class="form-field">
                            <label>OG Description <span class="auto-generated">Auto</span></label>
                            <textarea name="og_description" rows="2" placeholder="Defaults to meta description"><?php echo $post ? esc_textarea($post->og_description) : ''; ?></textarea>
                        </div>

                        <div class="form-field">
                            <label>Twitter Title <span class="auto-generated">Auto</span></label>
                            <input type="text" name="twitter_title" value="<?php echo $post ? esc_attr($post->twitter_title) : ''; ?>" placeholder="Defaults to OG title">
                        </div>

                        <div class="form-field">
                            <label>Twitter Description <span class="auto-generated">Auto</span></label>
                            <textarea name="twitter_description" rows="2" placeholder="Defaults to OG description"><?php echo $post ? esc_textarea($post->twitter_description) : ''; ?></textarea>
                        </div>

                        <p style="font-size: 11px; color: #666; margin-top: 10px;">
                            <strong>Note:</strong> Images are uploaded in the "Featured Images" section above.
                        </p>
                    </div>
                </div>

                <!-- Advanced Section -->
                <div class="sidebar-section">
                    <div class="section-header collapsed" onclick="toggleSection(this)">
                        <span><span style="color: var(--umb-cyan);">◎</span> Advanced</span>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content collapsed">
                        <div class="form-field">
                            <label>Canonical URL <span class="hint">(Leave blank for auto)</span></label>
                            <input type="text" name="canonical_url" value="<?php echo $post ? esc_attr($post->canonical_url) : ''; ?>" placeholder="https://your-site.com/blog/post-slug">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        // Initialize editor mode
        const editorModeField = document.getElementById('editor_mode');
        if (editorModeField) {
            const currentMode = editorModeField.value;
            switchEditorMode(currentMode);
        }

        // Toggle collapsible sections
        function toggleSection(header) {
            header.classList.toggle('collapsed');
            const content = header.nextElementSibling;
            content.classList.toggle('collapsed');
        }

        // Switch editor mode
        function switchEditorMode(mode) {
            const editorModeEl = document.getElementById('editor_mode');
            if (!editorModeEl) return;

            editorModeEl.value = mode;

            // Update button states
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            const modeBtn = document.querySelector(`[data-mode="${mode}"]`);
            if (modeBtn) {
                modeBtn.classList.add('active');
            }

            const content = document.getElementById('content');
            const previewContainer = document.getElementById('markdown-preview-container');

            if (!content || !previewContainer) return;

            if (mode === 'markdown') {
                previewContainer.style.display = 'block';
                content.style.fontFamily = "'Courier New', monospace";
                content.addEventListener('input', updateMarkdownPreview);
                updateMarkdownPreview();
            } else {
                previewContainer.style.display = 'none';
                content.style.fontFamily = 'inherit';
                content.removeEventListener('input', updateMarkdownPreview);
            }
        }

        // Update markdown preview
        function updateMarkdownPreview() {
            const content = document.getElementById('content').value;
            const preview = document.getElementById('markdown-preview');

            if (typeof marked !== 'undefined') {
                preview.innerHTML = marked.parse(content);
            } else {
                preview.textContent = 'Markdown parser loading...';
            }
        }

        // Calculate word count and reading time
        function updateContentStats() {
            const contentEl = document.getElementById('content');
            const wordCountEl = document.getElementById('word_count'); // Fixed: underscore not hyphen
            const readingTimeEl = document.getElementById('reading_time'); // Fixed: underscore not hyphen
            const wordCountDisplayEl = document.getElementById('word-count-display');
            const readingTimeDisplayEl = document.getElementById('reading-time-display');

            console.log('updateContentStats called');
            console.log('Elements found:', {
                content: !!contentEl,
                wordCount: !!wordCountEl,
                readingTime: !!readingTimeEl,
                display: !!wordCountDisplayEl,
                displayTime: !!readingTimeDisplayEl
            });

            if (!contentEl || !wordCountEl || !readingTimeEl || !wordCountDisplayEl || !readingTimeDisplayEl) {
                console.error('Missing elements for updateContentStats');
                return; // Elements not ready yet
            }

            const content = contentEl.value;

            // Strip HTML tags and count words
            const strippedContent = content.replace(/<[^>]*>/g, '').trim();
            const wordCount = strippedContent.length > 0
                ? strippedContent.split(/\s+/).filter(word => word.length > 0).length
                : 0;
            const readingTime = Math.max(1, Math.ceil(wordCount / 200)); // 200 words per minute, min 1

            console.log('Calculated:', { wordCount, readingTime, contentLength: content.length });

            wordCountEl.value = wordCount;
            readingTimeEl.value = readingTime;
            wordCountDisplayEl.textContent = wordCount.toLocaleString(); // Add comma formatting
            readingTimeDisplayEl.textContent = readingTime + ' min';
        }

        // Character counters
        function updateCharCounter(input, counterId, max) {
            if (!input) return;
            const counter = document.getElementById(counterId);
            if (!counter) return;

            const length = input.value.length;
            counter.textContent = length + ' / ' + max + ' characters';

            if (length > max * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
        }

        // Auto-generated badge toggle
        function toggleAutoLabel(input, labelId) {
            const label = document.getElementById(labelId);
            if (label) {
                label.style.display = input.value ? 'none' : 'inline-block';
            }
        }

        // Tag auto-complete
        const tagsInput = document.getElementById('tags-input');
        const tagSuggestions = document.getElementById('tag-suggestions');
        const existingTags = <?php echo json_encode(array_column($all_tags, 'name')); ?>;

        if (tagsInput && tagSuggestions) {
            tagsInput.addEventListener('input', function() {
                const value = this.value;
                const lastComma = value.lastIndexOf(',');
                const currentTag = value.substring(lastComma + 1).trim().toLowerCase();

                if (currentTag.length < 2) {
                    tagSuggestions.classList.remove('active');
                    return;
                }

                const matches = existingTags.filter(tag =>
                    tag.toLowerCase().includes(currentTag)
                );

                if (matches.length > 0) {
                    tagSuggestions.innerHTML = matches.map(tag =>
                        `<div class="tag-suggestion" onclick="selectTag('${tag}')">${tag}</div>`
                    ).join('');
                    tagSuggestions.classList.add('active');
                } else {
                    tagSuggestions.classList.remove('active');
                }
            });
        }

        function selectTag(tag) {
            if (!tagsInput || !tagSuggestions) return;

            const value = tagsInput.value;
            const lastComma = value.lastIndexOf(',');
            const newValue = value.substring(0, lastComma + 1) + (lastComma >= 0 ? ' ' : '') + tag + ', ';
            tagsInput.value = newValue;
            tagSuggestions.classList.remove('active');
            tagsInput.focus();
        }

        // Close suggestions when clicking outside
        if (tagSuggestions) {
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.tags-input-wrapper')) {
                    tagSuggestions.classList.remove('active');
                }
            });
        }

        // Event listeners
        const contentEl = document.getElementById('content');
        const excerptEl = document.getElementById('excerpt');
        const metaTitleEl = document.getElementById('meta-title');
        const metaDescEl = document.getElementById('meta-description');

        if (contentEl) {
            contentEl.addEventListener('input', updateContentStats);
        }
        if (excerptEl) {
            excerptEl.addEventListener('input', function() {
                updateCharCounter(this, 'excerpt-counter', 160);
            });
        }
        if (metaTitleEl) {
            metaTitleEl.addEventListener('input', function() {
                updateCharCounter(this, 'meta-title-counter', 60);
                toggleAutoLabel(this, 'meta-title-auto');
            });
        }
        if (metaDescEl) {
            metaDescEl.addEventListener('input', function() {
                updateCharCounter(this, 'meta-desc-counter', 160);
                toggleAutoLabel(this, 'meta-desc-auto');
            });
        }

        // Initialize on page load with small delay to ensure DOM is ready
        setTimeout(function() {
            console.log('Initializing content stats...');
            updateContentStats();
            updateCharCounter(excerptEl, 'excerpt-counter', 160);
            updateCharCounter(metaTitleEl, 'meta-title-counter', 60);
            updateCharCounter(metaDescEl, 'meta-desc-counter', 160);
            console.log('Content stats initialized');
        }, 100);

        // Cardano signature functionality
        const signPostBtn = document.getElementById('sign-post-btn');
        if (signPostBtn) {
            signPostBtn.addEventListener('click', async function() {
                if (!confirm('Sign this post on the Cardano blockchain?\n\nThis will create a transaction with post metadata (~0.17 ADA fee) and provide immutable proof of authorship.')) {
                    return;
                }

                const btn = this;
                const originalText = btn.innerHTML;

                // Disable button and show loading state
                btn.disabled = true;
                btn.innerHTML = '⏳ Signing...';
                btn.style.opacity = '0.6';

                try {
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'umbrella_blog_sign_post',
                            post_id: <?php echo $post ? $post->id : 0; ?>,
                            nonce: '<?php echo $post ? wp_create_nonce('sign_post_' . $post->id) : ''; ?>'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ Post signed successfully!\n\nTransaction Hash: ' + data.data.tx_hash + '\n\nThe page will now reload to show your signature.');
                        location.reload();
                    } else {
                        alert('❌ Signing failed:\n\n' + data.data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        btn.style.opacity = '1';
                    }
                } catch (error) {
                    alert('❌ Error signing post:\n\n' + error.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    btn.style.opacity = '1';
                }
            });
        }
    </script>
</div>
