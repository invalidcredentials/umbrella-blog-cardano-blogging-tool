<?php
/**
 * Single Blog Post
 *
 * Note: SEO meta tags are automatically added via wp_head hook in umbrella-blog.php
 */

get_header();

// Parse markdown if needed
$content = stripslashes($post->content); // Remove escaped quotes from database
if ($post->editor_mode === 'markdown') {
    // Convert Markdown to HTML using Parsedown
    if (!class_exists('Parsedown')) {
        // Fallback to basic conversion if Parsedown isn't available
        $content = wpautop($content);
    } else {
        $parsedown = new Parsedown();
        $content = $parsedown->text($content);
    }
} else {
    // For rich text, just apply WordPress auto-paragraph
    $content = wpautop($content);
}
?>

<div class="container single-post-container">
    <article class="blog-post glass-card">
        <header class="post-header">
            <div class="post-meta">
                <a href="<?php echo home_url('/blog/'); ?>" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Back to Blog
                </a>
                <span class="post-date">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php echo date('F j, Y', strtotime($post->created_at)); ?>
                </span>
            </div>

            <h1 class="post-title gradient-text"><?php echo esc_html(stripslashes($post->title)); ?></h1>

            <?php if ($post->excerpt): ?>
                <div class="post-excerpt">
                    <?php echo esc_html(stripslashes($post->excerpt)); ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="post-content">
            <?php echo wp_kses_post($content); ?>
        </div>

        <?php if (!empty($post->signature_tx_hash)): ?>
        <!-- Cardano Signature Block -->
        <div class="cardano-signature-block">
            <div class="signature-header">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#00E6FF" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                </svg>
                <h3>Signed on Cardano</h3>
            </div>

            <div class="signature-content">
                <?php if (!empty($post->signature_handle_image)): ?>
                    <div class="handle-avatar">
                        <img src="<?php echo esc_url($post->signature_handle_image); ?>"
                             alt="<?php echo esc_attr($post->signature_handle ?? 'Author'); ?>">
                    </div>
                <?php endif; ?>

                <div class="signature-details">
                    <?php if (!empty($post->signature_handle)): ?>
                        <div class="author-handle">
                            <?php echo esc_html($post->signature_handle); ?>
                        </div>
                    <?php endif; ?>

                    <div class="signature-meta">
                        <span class="signed-date">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Signed: <?php echo date('F j, Y \a\t g:i A', strtotime($post->signed_at)); ?>
                        </span>
                        <span class="tx-hash">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            TX:
                            <a href="https://<?php echo (strpos($post->signature_wallet_address, 'addr1') === 0) ? 'cardanoscan.io' : 'preprod.cardanoscan.io'; ?>/transaction/<?php echo esc_attr($post->signature_tx_hash); ?>"
                               target="_blank" rel="noopener">
                                <?php echo substr($post->signature_tx_hash, 0, 16); ?>...
                            </a>
                        </span>
                    </div>

                    <p class="signature-explanation">
                        This post has been cryptographically signed and published to the Cardano blockchain,
                        providing immutable proof of authorship and publication date.
                    </p>

                    <!-- On-Chain Metadata Toggle -->
                    <?php if (!empty($post->signature_metadata)): ?>
                        <button class="metadata-toggle" onclick="toggleMetadata()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                            View On-Chain Metadata
                        </button>
                        <div class="metadata-content" id="metadataContent" style="display: none;">
                            <?php
                            $metadata = json_decode($post->signature_metadata, true);
                            if ($metadata && isset($metadata['674']['msg'])):
                                $msg = $metadata['674']['msg'];
                            ?>
                                <div class="metadata-header">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00E6FF" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    <strong>CIP-20 Transaction Metadata (Label 674)</strong>
                                </div>
                                <div class="metadata-grid">
                                    <?php foreach ($msg as $key => $value): ?>
                                        <div class="metadata-row">
                                            <span class="metadata-key"><?php echo esc_html($key); ?>:</span>
                                            <span class="metadata-value">
                                                <?php
                                                if ($key === 'url' && filter_var($value, FILTER_VALIDATE_URL)) {
                                                    echo '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($value) . '</a>';
                                                } else {
                                                    echo esc_html($value);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="metadata-footer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                    This metadata is permanently stored on the Cardano blockchain and cannot be altered.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <script>
        function toggleMetadata() {
            const content = document.getElementById('metadataContent');
            const button = document.querySelector('.metadata-toggle');
            const svg = button.querySelector('svg polyline');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg> Hide On-Chain Metadata';
            } else {
                content.style.display = 'none';
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg> View On-Chain Metadata';
            }
        }
        </script>

        <footer class="post-footer">
            <a href="<?php echo home_url('/blog/'); ?>" class="btn-back-to-blog">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to All Posts
            </a>
        </footer>
    </article>
</div>

<style>
.single-post-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 2rem;
}

.blog-post {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    padding: 3rem;
}

.post-header {
    margin-bottom: 3rem;
}

.post-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
}

.back-link:hover {
    color: var(--umbrella-secondary);
    gap: 0.75rem;
}

.post-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

.post-date svg {
    color: var(--umbrella-secondary);
}

.post-title {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 1.5rem 0;
    letter-spacing: -0.02em;
}

.gradient-text {
    background: linear-gradient(135deg, var(--umbrella-secondary), var(--umbrella-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.post-excerpt {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    font-style: italic;
    padding-left: 1rem;
    border-left: 3px solid var(--umbrella-secondary);
}

.post-content {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.125rem;
    line-height: 1.8;
}

.post-content p {
    margin-bottom: 1.5rem;
}

.post-content h2 {
    color: var(--umbrella-secondary);
    font-size: 1.875rem;
    font-weight: 700;
    margin: 2.5rem 0 1rem 0;
}

.post-content h3 {
    color: var(--umbrella-secondary);
    font-size: 1.5rem;
    font-weight: 600;
    margin: 2rem 0 1rem 0;
}

.post-content h4 {
    color: var(--umbrella-accent);
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem 0;
}

.post-content strong {
    color: var(--umbrella-secondary);
    font-weight: 700;
}

.post-content a {
    color: var(--umbrella-secondary);
    text-decoration: none;
    border-bottom: 1px solid rgba(0, 212, 255, 0.3);
    transition: all 0.2s;
}

.post-content a:hover {
    color: var(--umbrella-accent);
    border-bottom-color: var(--umbrella-accent);
}

.post-content ul,
.post-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.post-content li {
    margin-bottom: 0.5rem;
}

.post-content code {
    background: rgba(0, 0, 0, 0.4);
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    color: var(--umbrella-secondary);
}

.post-content pre {
    background: rgba(0, 0, 0, 0.4);
    padding: 1.5rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.post-content pre code {
    background: none;
    padding: 0;
    color: rgba(255, 255, 255, 0.9);
}

.post-content blockquote {
    margin: 2rem 0;
    padding-left: 1.5rem;
    border-left: 3px solid var(--umbrella-secondary);
    color: rgba(255, 255, 255, 0.8);
    font-style: italic;
}

/* Cardano Signature Block */
.cardano-signature-block {
    margin: 3rem 0;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(0, 230, 255, 0.05) 0%, rgba(0, 230, 255, 0.02) 100%);
    border: 1px solid rgba(0, 230, 255, 0.2);
    border-radius: 0.75rem;
    box-shadow: 0 4px 16px rgba(0, 230, 255, 0.1);
}

.signature-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0, 230, 255, 0.2);
}

.signature-header h3 {
    margin: 0;
    color: #00E6FF;
    font-size: 1.25rem;
    font-weight: 600;
    background: none;
    -webkit-background-clip: unset;
    -webkit-text-fill-color: unset;
}

.signature-content {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

.handle-avatar {
    flex-shrink: 0;
}

.handle-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    border: 3px solid #00E6FF;
    box-shadow: 0 0 20px rgba(0, 230, 255, 0.3);
    object-fit: cover;
}

.signature-details {
    flex: 1;
}

.author-handle {
    font-size: 1.5rem;
    font-weight: 700;
    color: #00E6FF;
    margin-bottom: 0.75rem;
    text-shadow: 0 0 10px rgba(0, 230, 255, 0.5);
}

.signature-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 1rem;
}

.signature-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.signature-meta svg {
    flex-shrink: 0;
}

.signature-meta a {
    color: #00E6FF;
    text-decoration: none;
    word-break: break-all;
    transition: opacity 0.2s;
}

.signature-meta a:hover {
    opacity: 0.7;
    text-decoration: underline;
}

.signature-explanation {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 1rem 0;
    font-style: italic;
    line-height: 1.5;
}

/* On-Chain Metadata Toggle */
.metadata-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(0, 230, 255, 0.1);
    border: 1px solid rgba(0, 230, 255, 0.3);
    border-radius: 0.375rem;
    color: #00E6FF;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 0.5rem;
}

.metadata-toggle:hover {
    background: rgba(0, 230, 255, 0.2);
    border-color: #00E6FF;
    transform: translateY(-1px);
}

.metadata-toggle svg {
    transition: transform 0.2s;
}

.metadata-content {
    margin-top: 1rem;
    padding: 1.25rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(0, 230, 255, 0.2);
    border-radius: 0.5rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.metadata-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(0, 230, 255, 0.2);
    color: #00E6FF;
    font-size: 0.875rem;
}

.metadata-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.metadata-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1rem;
    padding: 0.5rem;
    background: rgba(0, 230, 255, 0.05);
    border-radius: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.4;
}

.metadata-key {
    font-weight: 600;
    color: rgba(0, 230, 255, 0.8);
    text-transform: capitalize;
    white-space: nowrap;
}

.metadata-value {
    color: rgba(255, 255, 255, 0.9);
    word-break: break-word;
    font-family: 'Courier New', monospace;
}

.metadata-value a {
    color: #00E6FF;
    text-decoration: none;
    border-bottom: 1px solid rgba(0, 230, 255, 0.3);
    transition: all 0.2s;
}

.metadata-value a:hover {
    border-bottom-color: #00E6FF;
    opacity: 0.8;
}

.metadata-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0, 230, 255, 0.2);
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    font-style: italic;
}

.metadata-footer svg {
    flex-shrink: 0;
    color: #00E6FF;
}

/* Mobile responsive */
@media (max-width: 600px) {
    .signature-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .handle-avatar {
        margin-bottom: 1rem;
    }

    .signature-meta {
        align-items: center;
    }

    .metadata-row {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }

    .metadata-key {
        border-bottom: 1px solid rgba(0, 230, 255, 0.2);
        padding-bottom: 0.25rem;
    }
}

.post-footer {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.btn-back-to-blog {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: rgba(0, 212, 255, 0.1);
    border: 2px solid rgba(0, 212, 255, 0.3);
    border-radius: 0.5rem;
    color: var(--umbrella-secondary);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-back-to-blog:hover {
    background: rgba(0, 212, 255, 0.2);
    border-color: var(--umbrella-secondary);
    gap: 1rem;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
}

@media (max-width: 768px) {
    .single-post-container {
        padding: 0 1rem;
        max-width: 100vw;
        overflow-x: hidden;
        box-sizing: border-box;
    }

    .blog-post {
        padding: 2rem 1.5rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
    }

    .post-title {
        font-size: 1.875rem;
    }

    .post-content {
        font-size: 1rem;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }

    .post-content pre {
        max-width: 100%;
        overflow-x: auto;
    }

    .post-meta {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
}
</style>

<?php get_footer(); ?>
