<?php
/**
 * Blog Listing Page - Enhanced with Filters, Search, and TRON Aesthetic
 */

get_header();

global $umbrella_blog;
if (!$umbrella_blog) {
    $umbrella_blog = new UmbrellaBlog();
}
$posts = $umbrella_blog->get_published_posts();

// Get all categories and tags with counts
global $wpdb;
$categories_table = $wpdb->prefix . 'umbrella_blog_categories';
$tags_table = $wpdb->prefix . 'umbrella_blog_tags';
$post_categories_table = $wpdb->prefix . 'umbrella_blog_post_categories';
$post_tags_table = $wpdb->prefix . 'umbrella_blog_post_tags';
$posts_table = $wpdb->prefix . 'umbrella_blog_posts';

$categories = $wpdb->get_results(
    "SELECT c.*, COUNT(DISTINCT p.id) as post_count
    FROM {$categories_table} c
    INNER JOIN {$post_categories_table} pc ON c.id = pc.category_id
    INNER JOIN {$posts_table} p ON pc.post_id = p.id
    WHERE p.status = 'published'
    GROUP BY c.id
    ORDER BY c.name"
);

$tags = $wpdb->get_results(
    "SELECT t.*, COUNT(DISTINCT p.id) as post_count
    FROM {$tags_table} t
    INNER JOIN {$post_tags_table} pt ON t.id = pt.tag_id
    INNER JOIN {$posts_table} p ON pt.post_id = p.id
    WHERE p.status = 'published'
    GROUP BY t.id
    ORDER BY post_count DESC, t.name"
);

// Enhance posts with category and tag data
foreach ($posts as $post) {
    // Get categories
    $post->categories = $wpdb->get_results($wpdb->prepare(
        "SELECT c.* FROM {$categories_table} c
        INNER JOIN {$post_categories_table} pc ON c.id = pc.category_id
        WHERE pc.post_id = %d
        ORDER BY c.name",
        $post->id
    ));

    // Get tags
    $post->tags = $wpdb->get_results($wpdb->prepare(
        "SELECT t.* FROM {$tags_table} t
        INNER JOIN {$post_tags_table} pt ON t.id = pt.tag_id
        WHERE pt.post_id = %d
        ORDER BY t.name",
        $post->id
    ));

    // Get featured image (prioritize OG image)
    $post->featured_image = !empty($post->og_image) ? $post->og_image : (!empty($post->twitter_image) ? $post->twitter_image : '');
}
?>

<div class="container blog-listing-container">
    <!-- Enhanced Hero with Search -->
    <div class="blog-hero">
        <div class="hero-content">
            <h1 class="gradient-text">📝 Blog</h1>
            <p class="hero-subtitle">Thoughts on Cardano, blockchain tech, and building cool shit.</p>

            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-wrapper">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="blog-search" class="search-input" placeholder="Search posts..." />
                    <button class="clear-search" id="clear-search" style="display: none;">✕</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats and Controls -->
    <div class="blog-controls">
        <div class="blog-stats">
            <span id="post-count"><?php echo count($posts); ?> posts</span>
        </div>
        <div class="view-controls">
            <button class="view-toggle active" data-view="grid" title="Grid View">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </button>
            <button class="view-toggle" data-view="list" title="List View">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
            </button>
            <select id="sort-select" class="sort-select">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="title">A-Z</option>
            </select>
        </div>
    </div>

    <!-- Category Filters -->
    <?php if (!empty($categories)): ?>
    <div class="filter-section">
        <div class="filter-label">Categories:</div>
        <div class="filter-pills">
            <?php foreach ($categories as $category): ?>
                <button class="filter-pill" data-type="category" data-value="<?php echo esc_attr($category->slug); ?>">
                    <?php echo esc_html($category->name); ?>
                    <span class="pill-count"><?php echo $category->post_count; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tag Filters -->
    <?php if (!empty($tags)): ?>
    <div class="filter-section">
        <div class="filter-label">Tags:</div>
        <div class="filter-pills tag-pills">
            <?php foreach ($tags as $tag): ?>
                <button class="filter-pill tag-pill" data-type="tag" data-value="<?php echo esc_attr($tag->slug); ?>">
                    #<?php echo esc_html(strtolower($tag->name)); ?>
                    <span class="pill-count"><?php echo $tag->post_count; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Filters -->
    <div class="active-filters" id="active-filters" style="display: none;">
        <span class="active-filters-label">Active filters:</span>
        <div id="active-filters-list"></div>
        <button class="clear-all-filters" id="clear-all-filters">Clear All</button>
    </div>

    <!-- Blog Posts Grid -->
    <div class="blog-posts-grid" id="blog-posts-grid" data-view="grid">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No blog posts yet</h3>
                <p>Check back soon for updates!</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post):
                $excerpt = stripslashes($post->excerpt);
                if (strlen($excerpt) > 150) {
                    $excerpt = substr($excerpt, 0, 150) . '...';
                }
            ?>
                <article class="blog-post-card"
                         data-title="<?php echo esc_attr(strtolower(stripslashes($post->title))); ?>"
                         data-excerpt="<?php echo esc_attr(strtolower($excerpt)); ?>"
                         data-categories="<?php echo esc_attr(implode(',', array_map(function($c) { return $c->slug; }, $post->categories))); ?>"
                         data-tags="<?php echo esc_attr(implode(',', array_map(function($t) { return $t->slug; }, $post->tags))); ?>"
                         data-date="<?php echo esc_attr($post->created_at); ?>">

                    <?php if ($post->featured_image): ?>
                    <div class="post-image">
                        <a href="<?php echo home_url('/blog/' . $post->slug); ?>">
                            <img src="<?php echo esc_url($post->featured_image); ?>"
                                 alt="<?php echo esc_attr(stripslashes($post->title)); ?>"
                                 loading="lazy" />
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="post-content">
                        <div class="post-header">
                            <h2 class="post-title">
                                <a href="<?php echo home_url('/blog/' . $post->slug); ?>">
                                    <?php echo esc_html(stripslashes($post->title)); ?>
                                </a>
                            </h2>

                            <div class="post-meta">
                                <span class="meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?php echo date('M j, Y', strtotime($post->created_at)); ?>
                                </span>
                                <?php if ($post->reading_time > 0): ?>
                                <span class="meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                    <?php echo $post->reading_time; ?> min read
                                </span>
                                <?php endif; ?>
                                <?php if ($post->word_count > 0): ?>
                                <span class="meta-item">
                                    <?php echo number_format($post->word_count); ?> words
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($post->excerpt): ?>
                            <div class="post-excerpt">
                                <?php echo esc_html($excerpt); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Tags -->
                        <?php if (!empty($post->tags)): ?>
                            <div class="post-tags">
                                <?php foreach (array_slice($post->tags, 0, 4) as $tag): ?>
                                    <span class="post-tag">
                                        #<?php echo esc_html(strtolower($tag->name)); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($post->tags) > 4): ?>
                                    <span class="post-tag">+<?php echo count($post->tags) - 4; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?php echo home_url('/blog/' . $post->slug); ?>" class="read-more">
                            Read More
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty State for No Results -->
    <div class="empty-state" id="no-results" style="display: none;">
        <div class="empty-icon">🔍</div>
        <h3>No posts found</h3>
        <p>Try adjusting your filters or search terms</p>
    </div>
</div>

<style>
/* Container */
.blog-listing-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 2rem;
    box-sizing: border-box;
    width: 100%;
}

/* Hero Section */
.blog-hero {
    text-align: center;
    padding: 3rem 2rem 2rem;
    margin-bottom: 2rem;
}

.gradient-text {
    background: linear-gradient(135deg, var(--umbrella-secondary), var(--umbrella-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
}

/* Search Bar - TRON Style */
.search-container {
    max-width: 600px;
    margin: 0 auto;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 1.25rem;
    color: var(--umbrella-secondary);
    pointer-events: none;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3.5rem;
    background: rgba(0, 0, 0, 0.4);
    border: 2px solid rgba(0, 212, 255, 0.3);
    border-radius: 50px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    outline: none;
}

.search-input:focus {
    border-color: var(--umbrella-secondary);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.4), 0 0 40px rgba(0, 212, 255, 0.2);
    background: rgba(0, 0, 0, 0.6);
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.clear-search {
    position: absolute;
    right: 1rem;
    background: rgba(255, 0, 110, 0.2);
    border: 1px solid rgba(255, 0, 110, 0.3);
    color: #ff006e;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.clear-search:hover {
    background: rgba(255, 0, 110, 0.3);
    border-color: #ff006e;
}

/* Controls */
.blog-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.blog-stats {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
    font-size: 0.95rem;
}

.view-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.view-toggle {
    background: rgba(0, 212, 255, 0.1);
    border: 1px solid rgba(0, 212, 255, 0.3);
    color: var(--umbrella-secondary);
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-toggle:hover {
    background: rgba(0, 212, 255, 0.2);
    border-color: var(--umbrella-secondary);
}

.view-toggle.active {
    background: rgba(0, 212, 255, 0.3);
    border-color: var(--umbrella-secondary);
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
}

.sort-select {
    padding: 0.6rem 1rem;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(0, 212, 255, 0.3);
    border-radius: 8px;
    color: var(--umbrella-secondary);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
}

.sort-select:hover,
.sort-select:focus {
    border-color: var(--umbrella-secondary);
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.2);
}

/* Filter Section */
.filter-section {
    margin-bottom: 1.5rem;
}

.filter-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.filter-pill {
    padding: 0.5rem 1rem;
    background: rgba(0, 212, 255, 0.1);
    border: 1px solid rgba(0, 212, 255, 0.3);
    border-radius: 20px;
    color: var(--umbrella-secondary);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-pill:hover {
    background: rgba(0, 212, 255, 0.2);
    border-color: var(--umbrella-secondary);
    transform: translateY(-2px);
}

.filter-pill.active {
    background: var(--umbrella-secondary);
    color: #0a0e1a;
    border-color: var(--umbrella-secondary);
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
}

.pill-count {
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.75rem;
}

.filter-pill.active .pill-count {
    background: rgba(0, 0, 0, 0.2);
    color: #0a0e1a;
}

/* Tag Pills */
.tag-pill {
    background: rgba(138, 43, 226, 0.2);
    border-color: rgba(138, 43, 226, 0.4);
    color: #a855f7;
}

.tag-pill:hover {
    background: rgba(138, 43, 226, 0.3);
    border-color: #8a2be2;
}

.tag-pill.active {
    background: #8a2be2;
    color: white;
    border-color: #8a2be2;
    box-shadow: 0 0 15px rgba(138, 43, 226, 0.5);
}

/* Active Filters */
.active-filters {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(0, 212, 255, 0.05);
    border: 1px solid rgba(0, 212, 255, 0.2);
    border-radius: 10px;
    margin-bottom: 2rem;
}

.active-filters-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
    font-size: 0.9rem;
}

#active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    flex: 1;
}

.active-filter-tag {
    padding: 0.4rem 0.8rem;
    background: rgba(0, 212, 255, 0.2);
    border: 1px solid var(--umbrella-secondary);
    border-radius: 15px;
    color: white;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.active-filter-tag button {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    font-size: 1rem;
    padding: 0;
    line-height: 1;
}

.active-filter-tag button:hover {
    color: white;
}

.clear-all-filters {
    padding: 0.5rem 1rem;
    background: rgba(255, 0, 110, 0.2);
    border: 1px solid rgba(255, 0, 110, 0.3);
    border-radius: 15px;
    color: #ff006e;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.clear-all-filters:hover {
    background: rgba(255, 0, 110, 0.3);
    border-color: #ff006e;
}

/* Blog Posts Grid */
.blog-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
    transition: all 0.3s ease;
}

.blog-posts-grid[data-view="list"] {
    grid-template-columns: 1fr;
}

/* Blog Post Cards */
.blog-post-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    opacity: 1;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.blog-post-card.hidden {
    display: none;
}

.blog-post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(0, 212, 255, 0.2);
    border-color: rgba(0, 212, 255, 0.4);
}

/* List View */
.blog-posts-grid[data-view="list"] .blog-post-card {
    flex-direction: row;
}

.blog-posts-grid[data-view="list"] .post-image {
    width: 200px;
    height: 200px;
    flex-shrink: 0;
}

.blog-posts-grid[data-view="list"] .post-content {
    flex: 1;
}

/* Post Image */
.post-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
}

.post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.blog-post-card:hover .post-image img {
    transform: scale(1.05);
}

/* Post Content */
.post-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.post-header {
    margin-bottom: 1rem;
}

.post-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.3;
}

.post-title a {
    color: white;
    text-decoration: none;
    transition: color 0.2s;
}

.post-title a:hover {
    color: var(--umbrella-secondary);
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.meta-item svg {
    color: var(--umbrella-secondary);
    flex-shrink: 0;
}

.post-excerpt {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

/* Post Tags */
.post-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.post-tag {
    font-size: 0.75rem;
    padding: 4px 10px;
    background: rgba(0, 212, 255, 0.15);
    color: var(--umbrella-secondary);
    border-radius: 12px;
    border: 1px solid rgba(0, 212, 255, 0.3);
    transition: all 0.2s;
}

.post-tag:hover {
    background: rgba(0, 212, 255, 0.25);
    border-color: var(--umbrella-secondary);
}

.read-more {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--umbrella-secondary);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    margin-top: auto;
}

.read-more:hover {
    gap: 0.75rem;
    color: var(--umbrella-accent);
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    grid-column: 1 / -1;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: white;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.empty-state p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .blog-listing-container {
        padding: 0 1rem;
        margin: 1rem auto;
        max-width: 100vw;
        overflow-x: hidden;
    }

    .blog-hero {
        padding: 2rem 0 1.5rem;
        margin-bottom: 1.5rem;
    }

    .gradient-text {
        font-size: 1.75rem;
    }

    .hero-subtitle {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }

    .search-container {
        padding: 0;
    }

    .blog-controls {
        flex-direction: row;
        gap: 0.75rem;
        align-items: center;
        padding: 0.75rem;
    }

    .blog-stats {
        flex: 1;
        font-size: 0.85rem;
    }

    .view-controls {
        justify-content: flex-end;
        gap: 0.5rem;
    }

    /* Hide grid/list toggles on mobile - doesn't make sense */
    .view-toggle {
        display: none;
    }

    .sort-select {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }

    .filter-section {
        margin-bottom: 1rem;
    }

    .filter-label {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .filter-pills {
        gap: 0.5rem;
    }

    .filter-pill {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }

    .blog-posts-grid {
        grid-template-columns: 1fr !important;
        gap: 1.5rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    .blog-post-card {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
    }

    .blog-posts-grid[data-view="list"] .blog-post-card {
        flex-direction: column;
    }

    .blog-posts-grid[data-view="list"] .post-image {
        width: 100%;
        height: 180px;
    }

    .active-filters {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .post-content {
        padding: 1.25rem;
    }

    .post-title {
        font-size: 1.25rem;
    }

    .post-meta {
        gap: 0.75rem;
        font-size: 0.75rem;
    }
}
</style>

<script>
(function() {
    'use strict';

    // State management
    const state = {
        activeCategories: new Set(),
        activeTags: new Set(),
        searchQuery: '',
        sortBy: 'newest',
        viewMode: 'grid'
    };

    // Elements
    const searchInput = document.getElementById('blog-search');
    const clearSearchBtn = document.getElementById('clear-search');
    const sortSelect = document.getElementById('sort-select');
    const viewToggles = document.querySelectorAll('.view-toggle');
    const filterPills = document.querySelectorAll('.filter-pill');
    const clearAllBtn = document.getElementById('clear-all-filters');
    const postCards = document.querySelectorAll('.blog-post-card');
    const postsGrid = document.getElementById('blog-posts-grid');
    const postCount = document.getElementById('post-count');
    const activeFiltersContainer = document.getElementById('active-filters');
    const activeFiltersList = document.getElementById('active-filters-list');
    const noResults = document.getElementById('no-results');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            state.searchQuery = e.target.value.toLowerCase().trim();

            if (state.searchQuery) {
                clearSearchBtn.style.display = 'flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            filterPosts();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            state.searchQuery = '';
            clearSearchBtn.style.display = 'none';
            filterPosts();
            searchInput.focus();
        });
    }

    // Filter pills functionality
    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            const type = this.dataset.type;
            const value = this.dataset.value;

            if (type === 'category') {
                if (state.activeCategories.has(value)) {
                    state.activeCategories.delete(value);
                    this.classList.remove('active');
                } else {
                    state.activeCategories.add(value);
                    this.classList.add('active');
                }
            } else if (type === 'tag') {
                if (state.activeTags.has(value)) {
                    state.activeTags.delete(value);
                    this.classList.remove('active');
                } else {
                    state.activeTags.add(value);
                    this.classList.add('active');
                }
            }

            filterPosts();
            updateActiveFilters();
        });
    });

    // Sort functionality
    if (sortSelect) {
        sortSelect.addEventListener('change', function(e) {
            state.sortBy = e.target.value;
            sortPosts();
        });
    }

    // View toggle functionality
    viewToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const view = this.dataset.view;

            viewToggles.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            state.viewMode = view;
            postsGrid.dataset.view = view;
        });
    });

    // Clear all filters
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            state.activeCategories.clear();
            state.activeTags.clear();
            state.searchQuery = '';
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';

            filterPills.forEach(pill => pill.classList.remove('active'));

            filterPosts();
            updateActiveFilters();
        });
    }

    // Main filter function
    function filterPosts() {
        let visibleCount = 0;

        postCards.forEach(card => {
            const title = card.dataset.title || '';
            const excerpt = card.dataset.excerpt || '';
            const categories = (card.dataset.categories || '').split(',').filter(c => c);
            const tags = (card.dataset.tags || '').split(',').filter(t => t);

            let show = true;

            // Search filter
            if (state.searchQuery) {
                const searchMatch = title.includes(state.searchQuery) ||
                                  excerpt.includes(state.searchQuery);
                if (!searchMatch) show = false;
            }

            // Category filter
            if (state.activeCategories.size > 0) {
                const categoryMatch = categories.some(cat =>
                    state.activeCategories.has(cat)
                );
                if (!categoryMatch) show = false;
            }

            // Tag filter
            if (state.activeTags.size > 0) {
                const tagMatch = tags.some(tag =>
                    state.activeTags.has(tag)
                );
                if (!tagMatch) show = false;
            }

            // Show/hide card
            if (show) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // Update count
        const plural = visibleCount === 1 ? 'post' : 'posts';
        postCount.textContent = `${visibleCount} ${plural}`;

        // Show/hide no results message
        if (visibleCount === 0 && postCards.length > 0) {
            noResults.style.display = 'block';
            postsGrid.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            postsGrid.style.display = 'grid';
        }
    }

    // Sorting function
    function sortPosts() {
        const cardsArray = Array.from(postCards);

        cardsArray.sort((a, b) => {
            switch (state.sortBy) {
                case 'newest':
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                case 'oldest':
                    return new Date(a.dataset.date) - new Date(b.dataset.date);
                case 'title':
                    return (a.dataset.title || '').localeCompare(b.dataset.title || '');
                default:
                    return 0;
            }
        });

        // Re-append cards in new order
        cardsArray.forEach(card => postsGrid.appendChild(card));
    }

    // Update active filters display
    function updateActiveFilters() {
        activeFiltersList.innerHTML = '';

        const hasFilters = state.activeCategories.size > 0 || state.activeTags.size > 0;

        if (!hasFilters) {
            activeFiltersContainer.style.display = 'none';
            return;
        }

        activeFiltersContainer.style.display = 'flex';

        // Add category filters
        state.activeCategories.forEach(category => {
            const tag = createActiveFilterTag(category, 'category');
            activeFiltersList.appendChild(tag);
        });

        // Add tag filters
        state.activeTags.forEach(tag => {
            const tagEl = createActiveFilterTag('#' + tag, 'tag');
            activeFiltersList.appendChild(tagEl);
        });
    }

    // Create active filter tag element
    function createActiveFilterTag(label, type) {
        const tag = document.createElement('span');
        tag.className = 'active-filter-tag';

        const text = document.createElement('span');
        text.textContent = label;

        const removeBtn = document.createElement('button');
        removeBtn.textContent = '×';
        removeBtn.addEventListener('click', function() {
            if (type === 'category') {
                state.activeCategories.delete(label);
            } else {
                state.activeTags.delete(label.replace('#', ''));
            }

            // Update pill UI
            const pill = Array.from(filterPills).find(p =>
                p.dataset.type === type && (
                    p.dataset.value === label ||
                    p.dataset.value === label.replace('#', '')
                )
            );
            if (pill) pill.classList.remove('active');

            filterPosts();
            updateActiveFilters();
        });

        tag.appendChild(text);
        tag.appendChild(removeBtn);

        return tag;
    }

    // Initialize
    filterPosts();

})();
</script>

<?php get_footer(); ?>
