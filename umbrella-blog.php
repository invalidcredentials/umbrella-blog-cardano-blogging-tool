<?php
/**
 * Plugin Name: Umbrella Blog
 * Description: Lightweight, no-BS blogging system. Write in Markdown or use minimal rich text. Zero bloat.
 * Version: 1.1.2
 * Author: Umbrella
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'Parsedown.php';
require_once plugin_dir_path(__FILE__) . 'includes/SEOMetaTags.php';

class UmbrellaBlog {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'umbrella_blog_posts';

        // Hooks
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_umbrella_blog_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_umbrella_blog_sign_post', array($this, 'ajax_sign_post'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Plugin activation - create database tables
     */
    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Main posts table with SEO fields
        $sql_posts = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            content longtext NOT NULL,
            excerpt text,
            editor_mode varchar(20) DEFAULT 'richtext',
            status varchar(20) DEFAULT 'draft',
            meta_title varchar(255),
            meta_description text,
            focus_keyword varchar(100),
            og_title varchar(255),
            og_description text,
            og_image varchar(500),
            og_image_alt varchar(255),
            twitter_title varchar(255),
            twitter_description text,
            twitter_image varchar(500),
            twitter_image_alt varchar(255),
            canonical_url varchar(500),
            meta_robots varchar(50) DEFAULT 'index,follow',
            word_count int DEFAULT 0,
            reading_time int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY focus_keyword (focus_keyword)
        ) $charset_collate;";

        // Categories table
        $categories_table = $wpdb->prefix . 'umbrella_blog_categories';
        $sql_categories = "CREATE TABLE {$categories_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Tags table
        $tags_table = $wpdb->prefix . 'umbrella_blog_tags';
        $sql_tags = "CREATE TABLE {$tags_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Post-Categories pivot table
        $post_categories_table = $wpdb->prefix . 'umbrella_blog_post_categories';
        $sql_post_categories = "CREATE TABLE {$post_categories_table} (
            post_id bigint(20) NOT NULL,
            category_id bigint(20) NOT NULL,
            PRIMARY KEY (post_id, category_id),
            KEY post_id (post_id),
            KEY category_id (category_id)
        ) $charset_collate;";

        // Post-Tags pivot table
        $post_tags_table = $wpdb->prefix . 'umbrella_blog_post_tags';
        $sql_post_tags = "CREATE TABLE {$post_tags_table} (
            post_id bigint(20) NOT NULL,
            tag_id bigint(20) NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            KEY post_id (post_id),
            KEY tag_id (tag_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_posts);
        dbDelta($sql_categories);
        dbDelta($sql_tags);
        dbDelta($sql_post_categories);
        dbDelta($sql_post_tags);

        // Run migrations
        $this->add_signature_fields();
        $this->run_wallet_migration();

        // Initialize wallet controller
        require_once plugin_dir_path(__FILE__) . 'includes/WalletController.php';
        UmbrellaBlog_WalletController::register();

        // Initialize theme manager
        require_once plugin_dir_path(__FILE__) . 'includes/ThemeManager.php';
        UmbrellaBlog_ThemeManager::init();
    }

    /**
     * Run wallet table migration
     */
    private function run_wallet_migration() {
        require_once plugin_dir_path(__FILE__) . 'migrations/003-add-wallet-table.php';
    }

    /**
     * Add Cardano signature fields to posts table
     */
    public function add_signature_fields() {
        global $wpdb;

        // Check if columns already exist
        $columns = $wpdb->get_results("DESCRIBE {$this->table_name}");
        $existing_columns = array_column($columns, 'Field');

        $migrations_needed = [];

        if (!in_array('signature_tx_hash', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signature_tx_hash VARCHAR(64) DEFAULT NULL";
        }

        if (!in_array('signature_wallet_address', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signature_wallet_address VARCHAR(255) DEFAULT NULL";
        }

        if (!in_array('signature_handle', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signature_handle VARCHAR(100) DEFAULT NULL";
        }

        if (!in_array('signature_handle_image', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signature_handle_image TEXT DEFAULT NULL";
        }

        if (!in_array('signed_at', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signed_at DATETIME DEFAULT NULL";
        }

        if (!in_array('signature_metadata', $existing_columns)) {
            $migrations_needed[] = "ADD COLUMN signature_metadata TEXT DEFAULT NULL";
        }

        // Execute migrations
        if (!empty($migrations_needed)) {
            $sql = "ALTER TABLE {$this->table_name} " . implode(', ', $migrations_needed);
            $wpdb->query($sql);

            // Add indexes (ignore errors if they already exist)
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX idx_signature_tx_hash (signature_tx_hash)");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD INDEX idx_signature_handle (signature_handle)");
        }
    }

    /**
     * Handle form submissions early (before headers are sent)
     */
    public function handle_form_submission() {
        // Only process if we're on the umbrella-blog admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'umbrella-blog') {
            return;
        }

        // Handle POST form submissions
        if (isset($_POST['umbrella_blog_action'])) {
            error_log('UMBRELLA BLOG: Form submission detected in admin_init');
            $this->handle_admin_action();
            // handle_admin_action() will exit after redirect
        }

        // Handle delete action (POST request)
        if (isset($_POST['delete_post_id'])) {
            $post_id = intval($_POST['delete_post_id']);
            check_admin_referer('delete_post_' . $post_id, 'delete_nonce');
            $this->delete_post($post_id);
            wp_safe_redirect(admin_url('admin.php?page=umbrella-blog'));
            exit;
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Register rewrite rules for blog URLs
        add_rewrite_rule('^blog/?$', 'index.php?umbrella_blog_list=1', 'top');
        add_rewrite_rule('^blog/([^/]+)/?$', 'index.php?umbrella_blog_post=$matches[1]', 'top');
        add_rewrite_rule('^blog-sitemap\.xml$', 'index.php?umbrella_blog_sitemap=1', 'top');

        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'umbrella_blog_list';
            $vars[] = 'umbrella_blog_post';
            $vars[] = 'umbrella_blog_sitemap';
            return $vars;
        });

        // Template redirect
        add_action('template_redirect', array($this, 'template_redirect'));

        // Initialize wallet controller
        require_once plugin_dir_path(__FILE__) . 'includes/WalletController.php';
        UmbrellaBlog_WalletController::register();

        // Initialize theme manager
        require_once plugin_dir_path(__FILE__) . 'includes/ThemeManager.php';
        UmbrellaBlog_ThemeManager::init();
    }

    /**
     * Handle template routing
     */
    public function template_redirect() {
        global $wp_query;

        if (get_query_var('umbrella_blog_list')) {
            include plugin_dir_path(__FILE__) . 'templates/blog-list.php';
            exit;
        }

        if ($slug = get_query_var('umbrella_blog_post')) {
            $post = $this->get_post_by_slug($slug);
            if ($post && $post->status === 'published') {
                // Add SEO meta tags to wp_head BEFORE template loads
                add_action('wp_head', function() use ($post) {
                    echo UmbrellaBlogSEO::generate_meta_tags($post);
                }, 1);

                // Suppress WordPress default author meta tags (prevent wallet hash exposure)
                add_filter('oembed_response_data', array($this, 'remove_author_from_oembed'), 10, 1);
                remove_action('wp_head', 'rel_canonical');

                // Remove default WordPress meta generator and author links
                remove_action('wp_head', 'wp_generator');
                remove_action('wp_head', 'index_rel_link');
                remove_action('wp_head', 'start_post_rel_link');
                remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');

                // Prevent author archives from being discoverable
                add_filter('author_link', '__return_empty_string');
                add_filter('the_author', function() { return get_bloginfo('name'); });
                add_filter('get_the_author_display_name', function() { return get_bloginfo('name'); });

                include plugin_dir_path(__FILE__) . 'templates/single-post.php';
                exit;
            } else {
                wp_redirect(home_url('/blog/'));
                exit;
            }
        }

        if (get_query_var('umbrella_blog_sitemap')) {
            $this->generate_sitemap();
            exit;
        }
    }

    /**
     * Generate XML sitemap
     */
    public function generate_sitemap() {
        global $wpdb;

        header('Content-Type: application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Get all published posts
        $posts = $wpdb->get_results(
            "SELECT slug, updated_at FROM {$this->table_name}
            WHERE status = 'published'
            ORDER BY updated_at DESC"
        );

        // Add blog listing page
        echo '  <url>' . "\n";
        echo '    <loc>' . home_url('/blog/') . '</loc>' . "\n";
        echo '    <changefreq>daily</changefreq>' . "\n";
        echo '    <priority>1.0</priority>' . "\n";
        echo '  </url>' . "\n";

        // Add each post
        foreach ($posts as $post) {
            $lastmod = date('c', strtotime($post->updated_at));

            echo '  <url>' . "\n";
            echo '    <loc>' . home_url('/blog/' . $post->slug) . '</loc>' . "\n";
            echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>monthly</changefreq>' . "\n";
            echo '    <priority>0.8</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        echo '</urlset>';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Umbrella Blog',
            'Blog',
            'manage_options',
            'umbrella-blog',
            array($this, 'admin_page'),
            'dashicons-edit',
            30
        );

        // Wallet Manager (NEW!)
        add_submenu_page(
            'umbrella-blog',
            'Wallet Manager',
            '🔐 Wallet Manager',
            'manage_options',
            'umbrella-blog-wallet',
            array($this, 'wallet_manager_page')
        );

        // Plugin Settings (NEW!)
        add_submenu_page(
            'umbrella-blog',
            'Plugin Settings',
            '⚙️ Settings',
            'manage_options',
            'umbrella-blog-settings',
            array($this, 'plugin_settings_page')
        );

        // Database Setup page
        add_submenu_page(
            'umbrella-blog',
            'Database Setup',
            '◎ Database Setup',
            'manage_options',
            'umbrella-blog-setup',
            array($this, 'setup_database_page')
        );
    }

    /**
     * Setup database page (hidden, for manual activation)
     */
    public function setup_database_page() {
        ?>
        <div class="wrap umbrella-admin-page">
            <h1 style="font-size: 32px; font-weight: 700; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xl);">
                <span style="color: var(--umb-cyan);">◎</span> Database Setup
            </h1>

            <?php if (isset($_POST['run_setup'])): ?>
                <?php $this->activate(); ?>
                <div class="umb-card" style="border: 2px solid var(--umb-success); margin-bottom: var(--umb-space-lg);">
                    <div class="umb-card-body" style="padding: var(--umb-space-md);">
                        <p style="color: var(--umb-success); margin: 0;">
                            <span style="color: var(--umb-success);">✓</span> Database tables created/updated successfully!
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_POST['run_signature_migration'])): ?>
                <?php $this->add_signature_fields(); ?>
                <div class="umb-card" style="border: 2px solid var(--umb-success); margin-bottom: var(--umb-space-lg);">
                    <div class="umb-card-body" style="padding: var(--umb-space-md);">
                        <p style="color: var(--umb-success); margin: 0;">
                            <span style="color: var(--umb-success);">✓</span> Signature fields added/updated successfully!
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="umb-card" style="margin-bottom: var(--umb-space-lg);">
                <div class="umb-card-header">
                    <h2 class="umb-card-title"><span style="color: var(--umb-cyan);">▤</span> Database Troubleshooting</h2>
                </div>
                <div class="umb-card-body">
                    <div style="background: rgba(0, 230, 255, 0.05); border-left: 3px solid var(--umb-info); border-radius: var(--umb-radius-sm); padding: var(--umb-space-md); margin-bottom: var(--umb-space-lg);">
                        <p style="font-size: 13px; color: var(--umb-text-secondary); margin: 0;">
                            <strong style="color: var(--umb-info);">ℹ️ Note:</strong> Database setup runs automatically when you activate the plugin. Only use these tools if you're experiencing issues or need to manually run migrations.
                        </p>
                    </div>

                    <div style="margin-bottom: var(--umb-space-xl);">
                        <h3 style="font-size: 16px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-md);">
                            Full Database Setup
                        </h3>
                        <p style="font-size: 13px; color: var(--umb-text-secondary); margin-bottom: var(--umb-space-md);">
                            Creates/updates all database tables (posts, categories, tags, wallets, etc.)
                        </p>
                        <form method="post">
                            <button type="submit" name="run_setup" class="umb-btn umb-btn-primary">
                                <span style="color: var(--umb-cyan);">◉</span> Run Full Setup
                            </button>
                        </form>
                    </div>

                    <div>
                        <h3 style="font-size: 16px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-md);">
                            Cardano Signature Migration
                        </h3>
                        <p style="font-size: 13px; color: var(--umb-text-secondary); margin-bottom: var(--umb-space-md);">
                            Adds/updates Cardano signature fields to the posts table
                        </p>
                        <form method="post">
                            <button type="submit" name="run_signature_migration" class="umb-btn umb-btn-secondary">
                                <span style="color: var(--umb-cyan);">⚿</span> Add Signature Fields
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="umb-card" style="border: 1px solid var(--umb-warning);">
                <div class="umb-card-header" style="background: rgba(255, 170, 0, 0.05);">
                    <h2 class="umb-card-title"><span style="color: var(--umb-warning);">⚠️</span> Important Note</h2>
                </div>
                <div class="umb-card-body">
                    <p style="font-size: 13px; color: var(--umb-text-secondary); margin: 0;">
                        The database setup runs automatically when you activate the plugin. If you're experiencing issues with wallets not saving on production, run the <strong>Full Database Setup</strong> to ensure all tables exist.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Signing wallet settings page
     */
    public function signing_wallet_page() {
        include plugin_dir_path(__FILE__) . 'admin/settings.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'umbrella-blog') === false) {
            return;
        }

        // Umbrella Admin Theme CSS
        wp_enqueue_style(
            'umbrella-admin-theme',
            plugins_url('admin/css/umbrella-admin.css', __FILE__),
            array(),
            '1.0.0'
        );

        // Umbrella Editor CSS (for post editor pages)
        wp_enqueue_style(
            'umbrella-editor-theme',
            plugins_url('admin/css/umbrella-editor.css', __FILE__),
            array('umbrella-admin-theme'),
            '1.0.0'
        );

        // Simple Markdown parser
        wp_enqueue_script(
            'marked',
            'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            array(),
            '4.0.0',
            true
        );
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        // Display success message if redirected after save
        if (isset($_GET['message'])) {
            $message = $_GET['message'] === 'created' ? 'Post created successfully!' : 'Post updated successfully!';
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        switch ($action) {
            case 'new':
                $this->render_editor();
                break;
            case 'edit':
                $this->render_editor(intval($_GET['id']));
                break;
            default:
                $this->render_post_list();
                break;
        }
    }

    /**
     * Handle admin form submissions
     */
    private function handle_admin_action() {
        global $wpdb;

        error_log('=== UMBRELLA BLOG: handle_admin_action called ===');
        error_log('POST data: ' . print_r($_POST, true));

        if (!isset($_POST['umbrella_blog_nonce']) ||
            !wp_verify_nonce($_POST['umbrella_blog_nonce'], 'umbrella_blog_save')) {
            error_log('UMBRELLA BLOG: Nonce verification FAILED');
            return;
        }

        error_log('UMBRELLA BLOG: Nonce verification PASSED');

        $action = sanitize_text_field($_POST['umbrella_blog_action']);
        error_log('UMBRELLA BLOG: Action = ' . $action);

        // Basic fields - stripslashes to remove WordPress magic quotes
        $title = sanitize_text_field(stripslashes($_POST['title']));
        $content = wp_kses_post(stripslashes($_POST['content']));
        $excerpt = sanitize_textarea_field(stripslashes($_POST['excerpt']));
        $editor_mode = sanitize_text_field($_POST['editor_mode']);
        $status = sanitize_text_field($_POST['status']);
        $slug = !empty($_POST['slug']) ? sanitize_title(stripslashes($_POST['slug'])) : sanitize_title($title);

        // SEO fields - stripslashes to remove WordPress magic quotes
        $meta_title = sanitize_text_field(stripslashes($_POST['meta_title'] ?? ''));
        $meta_description = sanitize_textarea_field(stripslashes($_POST['meta_description'] ?? ''));
        $focus_keyword = sanitize_text_field(stripslashes($_POST['focus_keyword'] ?? ''));
        $og_title = sanitize_text_field(stripslashes($_POST['og_title'] ?? ''));
        $og_description = sanitize_textarea_field(stripslashes($_POST['og_description'] ?? ''));
        $og_image = esc_url_raw($_POST['og_image'] ?? '');
        $og_image_alt = sanitize_text_field(stripslashes($_POST['og_image_alt'] ?? ''));
        $twitter_title = sanitize_text_field(stripslashes($_POST['twitter_title'] ?? ''));
        $twitter_description = sanitize_textarea_field(stripslashes($_POST['twitter_description'] ?? ''));
        $twitter_image = esc_url_raw($_POST['twitter_image'] ?? '');
        $twitter_image_alt = sanitize_text_field(stripslashes($_POST['twitter_image_alt'] ?? ''));
        $canonical_url = esc_url_raw($_POST['canonical_url'] ?? '');
        $meta_robots = sanitize_text_field($_POST['meta_robots'] ?? 'index,follow');

        // Calculate word count from content
        $word_count = str_word_count(strip_tags($content));
        $reading_time = max(1, ceil($word_count / 200)); // 200 words per minute

        // Auto-fill SEO fields if empty
        if (empty($meta_title)) {
            $meta_title = $title;
        }
        if (empty($meta_description)) {
            $meta_description = $excerpt;
        }

        $data = array(
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $excerpt,
            'editor_mode' => $editor_mode,
            'status' => $status,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'focus_keyword' => $focus_keyword,
            'og_title' => $og_title,
            'og_description' => $og_description,
            'og_image' => $og_image,
            'og_image_alt' => $og_image_alt,
            'twitter_title' => $twitter_title,
            'twitter_description' => $twitter_description,
            'twitter_image' => $twitter_image,
            'twitter_image_alt' => $twitter_image_alt,
            'canonical_url' => $canonical_url,
            'meta_robots' => $meta_robots,
            'word_count' => $word_count,
            'reading_time' => $reading_time
        );

        $post_id = null;
        if ($action === 'create') {
            error_log('UMBRELLA BLOG: Attempting INSERT with data: ' . print_r($data, true));
            $result = $wpdb->insert($this->table_name, $data);
            error_log('UMBRELLA BLOG: INSERT result = ' . $result);
            error_log('UMBRELLA BLOG: Last error = ' . $wpdb->last_error);
            $post_id = $wpdb->insert_id;
            error_log('UMBRELLA BLOG: Insert ID = ' . $post_id);
        } else if ($action === 'update') {
            $post_id = intval($_POST['post_id']);
            error_log('UMBRELLA BLOG: Attempting UPDATE for post ID ' . $post_id);
            $result = $wpdb->update($this->table_name, $data, array('id' => $post_id));
            error_log('UMBRELLA BLOG: UPDATE result = ' . $result);
            error_log('UMBRELLA BLOG: Last error = ' . $wpdb->last_error);
        }

        // Handle Categories
        if ($post_id) {
            error_log('UMBRELLA BLOG: Saving categories and tags for post ' . $post_id);
            $this->save_categories($post_id);
            $this->save_tags($post_id);
        }

        // Redirect to edit page with success message
        if ($post_id) {
            // Flush rewrite rules only if slug changed or new post
            if ($action === 'create' || (isset($_POST['slug']) && !empty($_POST['slug']))) {
                flush_rewrite_rules();
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'umbrella-blog',
                    'action' => 'edit',
                    'id' => $post_id,
                    'message' => $action === 'create' ? 'created' : 'updated'
                ),
                admin_url('admin.php')
            );
            error_log('UMBRELLA BLOG: Redirecting to ' . $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            error_log('UMBRELLA BLOG: ERROR - No post_id after save!');
        }
    }

    /**
     * Save categories for a post
     */
    private function save_categories($post_id) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'umbrella_blog_categories';
        $post_categories_table = $wpdb->prefix . 'umbrella_blog_post_categories';

        // Delete existing relationships
        $wpdb->delete($post_categories_table, array('post_id' => $post_id));

        // Create new categories if specified
        if (!empty($_POST['new_categories'])) {
            $new_cats = array_map('trim', explode(',', $_POST['new_categories']));
            foreach ($new_cats as $cat_name) {
                if (empty($cat_name)) continue;

                $slug = sanitize_title($cat_name);
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$categories_table} WHERE slug = %s",
                    $slug
                ));

                if (!$existing) {
                    $wpdb->insert($categories_table, array(
                        'name' => $cat_name,
                        'slug' => $slug
                    ));
                }
            }
        }

        // Add selected categories
        if (!empty($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat_id) {
                $wpdb->insert($post_categories_table, array(
                    'post_id' => $post_id,
                    'category_id' => intval($cat_id)
                ));
            }
        }
    }

    /**
     * Save tags for a post
     */
    private function save_tags($post_id) {
        global $wpdb;
        $tags_table = $wpdb->prefix . 'umbrella_blog_tags';
        $post_tags_table = $wpdb->prefix . 'umbrella_blog_post_tags';

        // Delete existing relationships
        $wpdb->delete($post_tags_table, array('post_id' => $post_id));

        // Process tags
        if (!empty($_POST['tags'])) {
            $tags = array_map('trim', explode(',', $_POST['tags']));

            foreach ($tags as $tag_name) {
                if (empty($tag_name)) continue;

                $slug = sanitize_title($tag_name);

                // Check if tag exists
                $tag_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$tags_table} WHERE slug = %s",
                    $slug
                ));

                // Create tag if it doesn't exist
                if (!$tag_id) {
                    $wpdb->insert($tags_table, array(
                        'name' => $tag_name,
                        'slug' => $slug
                    ));
                    $tag_id = $wpdb->insert_id;
                }

                // Create relationship
                $wpdb->insert($post_tags_table, array(
                    'post_id' => $post_id,
                    'tag_id' => $tag_id
                ));
            }
        }
    }

    /**
     * Render post list
     */
    private function render_post_list() {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");

        include plugin_dir_path(__FILE__) . 'admin/post-list.php';
    }

    /**
     * Render editor
     */
    private function render_editor($post_id = null) {
        $post = null;
        if ($post_id) {
            $post = $this->get_post($post_id);
        }

        include plugin_dir_path(__FILE__) . 'admin/editor.php';
    }

    /**
     * Get all published posts
     */
    public function get_published_posts() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE status = 'published' ORDER BY created_at DESC"
        );
    }

    /**
     * Get post by ID
     */
    public function get_post($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get post by slug
     */
    public function get_post_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Delete post
     */
    private function delete_post($id) {
        global $wpdb;
        $wpdb->delete($this->table_name, array('id' => $id));
    }

    /**
     * Remove author from oEmbed data to prevent wallet hash exposure
     */
    public function remove_author_from_oembed($data) {
        if (isset($data['author_name'])) {
            $data['author_name'] = get_bloginfo('name');
        }
        if (isset($data['author_url'])) {
            $data['author_url'] = home_url();
        }
        return $data;
    }

    /**
     * Handle image upload via AJAX
     */
    public function handle_image_upload() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'umbrella_blog_upload')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['image'])) {
            wp_send_json_error('No image uploaded');
            return;
        }

        $file = $_FILES['image'];
        $image_type = sanitize_text_field($_POST['image_type']);

        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type. Please upload JPG, PNG, or WebP');
            return;
        }

        // Validate file size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error('File size must be less than 5MB');
            return;
        }

        // Use WordPress file upload handler
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $upload_overrides = array(
            'test_form' => false,
            'test_type' => true
        );

        // Create uploads subdirectory for blog images
        add_filter('upload_dir', function($dirs) {
            $dirs['subdir'] = '/umbrella-blog';
            $dirs['path'] = $dirs['basedir'] . '/umbrella-blog';
            $dirs['url'] = $dirs['baseurl'] . '/umbrella-blog';
            return $dirs;
        });

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        remove_all_filters('upload_dir');

        if (isset($uploaded_file['error'])) {
            wp_send_json_error($uploaded_file['error']);
            return;
        }

        // Return the uploaded file URL
        wp_send_json_success(array(
            'url' => $uploaded_file['url'],
            'path' => $uploaded_file['file'],
            'type' => $image_type
        ));
    }

    /**
     * AJAX handler for signing blog posts on Cardano
     */
    public function ajax_sign_post() {
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
            return;
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sign_post_' . $post_id)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Load signer
        if (!class_exists('UmbrellaBlog_Signer')) {
            require_once plugin_dir_path(__FILE__) . 'includes/BlogSigner.php';
        }

        // Sign the post
        $result = UmbrellaBlog_Signer::signBlogPost($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Wallet Manager page
     */
    public function wallet_manager_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/wallet-manager.php';
    }

    /**
     * Plugin Settings page
     */
    public function plugin_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/plugin-settings.php';
    }
}

// Initialize plugin
new UmbrellaBlog();
