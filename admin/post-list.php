<div class="wrap umbrella-admin-page">
    <h1 style="font-size: 32px; font-weight: 700; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xl); display: flex; align-items: center; justify-content: space-between;">
        <span><span style="color: var(--umb-cyan);">✎</span> Blog Posts</span>
        <a href="?page=umbrella-blog&action=new" class="umb-btn umb-btn-primary">
            <span style="color: var(--umb-cyan);">+</span> Add New Post
        </a>
    </h1>

    <?php if (empty($posts)): ?>
        <div class="umb-card" style="text-align: center; padding: var(--umb-space-2xl);">
            <div style="font-size: 64px; margin-bottom: var(--umb-space-lg); opacity: 0.5; color: var(--umb-cyan);">▭</div>
            <h2 style="font-size: 24px; color: var(--umb-text-primary); margin-bottom: var(--umb-space-md);">No posts yet</h2>
            <p style="color: var(--umb-text-secondary); margin-bottom: var(--umb-space-lg);">
                Get started by creating your first blog post!
            </p>
            <a href="?page=umbrella-blog&action=new" class="umb-btn umb-btn-primary umb-btn-large">
                <span style="color: var(--umb-cyan);">✎</span> Create Your First Post
            </a>
        </div>
    <?php else: ?>
        <div class="umb-card">
            <div style="display: grid; gap: var(--umb-space-md);">
                <?php foreach ($posts as $post): ?>
                    <div class="umb-card" style="margin-bottom: 0; transition: all var(--umb-transition-base);" onmouseover="this.style.borderColor='var(--umb-cyan)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 0 16px rgba(0, 230, 255, 0.3)';" onmouseout="this.style.borderColor='var(--umb-glass-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='var(--umb-shadow-md)';">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: var(--umb-space-lg); align-items: start;">
                            <!-- Post Info -->
                            <div>
                                <div style="display: flex; align-items: center; gap: var(--umb-space-md); margin-bottom: var(--umb-space-sm);">
                                    <h3 style="font-size: 18px; font-weight: 600; color: var(--umb-text-primary); margin: 0;">
                                        <?php echo esc_html($post->title); ?>
                                    </h3>
                                    <span class="umb-badge umb-badge-<?php echo $post->status; ?>" style="flex-shrink: 0;">
                                        <?php if ($post->status === 'published'): ?>
                                            <span class="umb-badge-dot"></span>
                                        <?php endif; ?>
                                        <?php echo ucfirst($post->status); ?>
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--umb-space-lg); font-size: 13px; color: var(--umb-text-secondary);">
                                    <span><span style="color: var(--umb-cyan);">◷</span> <?php echo date('F j, Y', strtotime($post->created_at)); ?></span>
                                    <span><?php echo $post->editor_mode === 'markdown' ? '<span style="color: var(--umb-cyan);">◈</span> Markdown' : '<span style="color: var(--umb-cyan);">✎</span> Rich Text'; ?></span>
                                    <?php if (!empty($post->word_count)): ?>
                                        <span><span style="color: var(--umb-cyan);">▤</span> <?php echo number_format($post->word_count); ?> words</span>
                                    <?php endif; ?>
                                    <?php if (!empty($post->signature_tx_hash)): ?>
                                        <span style="color: var(--umb-cyan);"><span style="color: var(--umb-cyan);">⚿</span> Signed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div style="display: flex; gap: var(--umb-space-sm); flex-shrink: 0;">
                                <a href="?page=umbrella-blog&action=edit&id=<?php echo $post->id; ?>" class="umb-btn umb-btn-primary umb-btn-small">
                                    <span style="color: var(--umb-cyan);">✎</span> Edit
                                </a>
                                <?php if ($post->status === 'published'): ?>
                                    <a href="<?php echo home_url('/blog/' . $post->slug); ?>" target="_blank" class="umb-btn umb-btn-secondary umb-btn-small">
                                        <span style="color: var(--umb-cyan);">◉</span> View
                                    </a>
                                <?php endif; ?>
                                <form method="post" style="display: inline; margin: 0; padding: 0;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                    <?php wp_nonce_field('delete_post_' . $post->id, 'delete_nonce'); ?>
                                    <input type="hidden" name="delete_post_id" value="<?php echo $post->id; ?>">
                                    <button type="submit" class="umb-btn umb-btn-danger umb-btn-small" style="margin: 0;">
                                        <span style="color: var(--umb-danger);">✕</span> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
