<?php
/**
 * SEO Meta Tags Generator
 * Generates comprehensive meta tags for blog posts
 */

class UmbrellaBlogSEO {

    /**
     * Generate all meta tags for a post
     */
    public static function generate_meta_tags($post) {
        $output = '';

        // Basic SEO
        $output .= self::generate_basic_meta($post);

        // Open Graph Protocol
        $output .= self::generate_og_meta($post);

        // Twitter Cards
        $output .= self::generate_twitter_meta($post);

        // JSON-LD Structured Data
        $output .= self::generate_json_ld($post);

        // Canonical URL
        $output .= self::generate_canonical($post);

        // Meta Robots
        $output .= self::generate_meta_robots($post);

        return $output;
    }

    /**
     * Basic meta tags
     */
    private static function generate_basic_meta($post) {
        $meta_title = !empty($post->meta_title) ? $post->meta_title : $post->title;
        $meta_description = !empty($post->meta_description) ? $post->meta_description : $post->excerpt;

        $output = "\n<!-- Basic SEO Meta Tags -->\n";
        $output .= '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";

        if (!empty($post->focus_keyword)) {
            $output .= '<meta name="keywords" content="' . esc_attr($post->focus_keyword) . '">' . "\n";
        }

        return $output;
    }

    /**
     * Open Graph Protocol meta tags
     */
    private static function generate_og_meta($post) {
        global $wpdb;

        $og_title = !empty($post->og_title) ? $post->og_title : (!empty($post->meta_title) ? $post->meta_title : $post->title);
        $og_description = !empty($post->og_description) ? $post->og_description : (!empty($post->meta_description) ? $post->meta_description : $post->excerpt);

        // Force HTTPS for URLs
        $og_url = !empty($post->canonical_url) ? $post->canonical_url : home_url('/blog/' . $post->slug, 'https');
        $og_url = str_replace('http://', 'https://', $og_url);

        // Force HTTPS for images
        $og_image = !empty($post->og_image) ? str_replace('http://', 'https://', $post->og_image) : '';

        $output = "\n<!-- Open Graph Protocol -->\n";
        $output .= '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        $output .= '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        $output .= '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
        $output .= '<meta property="og:type" content="article">' . "\n";

        if ($og_image) {
            $output .= '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
            $output .= '<meta property="og:image:secure_url" content="' . esc_url($og_image) . '">' . "\n";
            $output .= '<meta property="og:image:width" content="1200">' . "\n";
            $output .= '<meta property="og:image:height" content="630">' . "\n";
            $output .= '<meta property="og:image:type" content="image/jpeg">' . "\n";
            if (!empty($post->og_image_alt)) {
                $output .= '<meta property="og:image:alt" content="' . esc_attr($post->og_image_alt) . '">' . "\n";
            }
        }

        $output .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        $output .= '<meta property="article:published_time" content="' . date('c', strtotime($post->created_at)) . '">' . "\n";
        $output .= '<meta property="article:modified_time" content="' . date('c', strtotime($post->updated_at)) . '">' . "\n";

        // Article section (primary category)
        $categories = self::get_post_categories($post->id);
        if (!empty($categories)) {
            $output .= '<meta property="article:section" content="' . esc_attr($categories[0]->name) . '">' . "\n";
        }

        // Article tags
        $tags = self::get_post_tags($post->id);
        foreach ($tags as $tag) {
            $output .= '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n";
        }

        return $output;
    }

    /**
     * Twitter Card meta tags
     */
    private static function generate_twitter_meta($post) {
        $twitter_title = !empty($post->twitter_title) ? $post->twitter_title : (!empty($post->og_title) ? $post->og_title : (!empty($post->meta_title) ? $post->meta_title : $post->title));
        $twitter_description = !empty($post->twitter_description) ? $post->twitter_description : (!empty($post->og_description) ? $post->og_description : (!empty($post->meta_description) ? $post->meta_description : $post->excerpt));

        // Force HTTPS for Twitter images
        $twitter_image = !empty($post->twitter_image) ? $post->twitter_image : (!empty($post->og_image) ? $post->og_image : '');
        $twitter_image = str_replace('http://', 'https://', $twitter_image);

        $twitter_image_alt = !empty($post->twitter_image_alt) ? $post->twitter_image_alt : (!empty($post->og_image_alt) ? $post->og_image_alt : '');

        $output = "\n<!-- Twitter Card -->\n";
        $output .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $output .= '<meta name="twitter:site" content="@' . esc_attr(get_option('umbrella_blog_twitter_handle', 'umbrella_io')) . '">' . "\n";
        $output .= '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '">' . "\n";
        $output .= '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '">' . "\n";

        if ($twitter_image) {
            $output .= '<meta name="twitter:image" content="' . esc_url($twitter_image) . '">' . "\n";
            if ($twitter_image_alt) {
                $output .= '<meta name="twitter:image:alt" content="' . esc_attr($twitter_image_alt) . '">' . "\n";
            }
        }

        return $output;
    }

    /**
     * JSON-LD Structured Data
     */
    private static function generate_json_ld($post) {
        $categories = self::get_post_categories($post->id);
        $tags = self::get_post_tags($post->id);

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => !empty($post->meta_description) ? $post->meta_description : $post->excerpt,
            'datePublished' => date('c', strtotime($post->created_at)),
            'dateModified' => date('c', strtotime($post->updated_at)),
            'author' => array(
                '@type' => 'Person',
                'name' => get_bloginfo('name')
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            )
        );

        // Add image if available - force HTTPS
        if (!empty($post->og_image)) {
            $schema['image'] = str_replace('http://', 'https://', $post->og_image);
        }

        // Add keywords
        if (!empty($tags)) {
            $schema['keywords'] = array_map(function($tag) { return $tag->name; }, $tags);
        }

        // Add article section
        if (!empty($categories)) {
            $schema['articleSection'] = $categories[0]->name;
        }

        // Add word count and reading time
        if ($post->word_count > 0) {
            $schema['wordCount'] = $post->word_count;
        }
        if ($post->reading_time > 0) {
            $schema['timeRequired'] = 'PT' . $post->reading_time . 'M';
        }

        $output = "\n<!-- JSON-LD Structured Data -->\n";
        $output .= '<script type="application/ld+json">' . "\n";
        $output .= json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $output .= '</script>' . "\n";

        return $output;
    }

    /**
     * Canonical URL
     */
    private static function generate_canonical($post) {
        // Force HTTPS for canonical URL
        $canonical = !empty($post->canonical_url) ? $post->canonical_url : home_url('/blog/' . $post->slug, 'https');
        $canonical = str_replace('http://', 'https://', $canonical);

        $output = "\n<!-- Canonical URL -->\n";
        $output .= '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";

        return $output;
    }

    /**
     * Meta robots
     */
    private static function generate_meta_robots($post) {
        $robots = !empty($post->meta_robots) ? $post->meta_robots : 'index,follow';

        $output = "\n<!-- Meta Robots -->\n";
        $output .= '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";

        return $output;
    }

    /**
     * Get post categories
     */
    private static function get_post_categories($post_id) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'umbrella_blog_categories';
        $post_categories_table = $wpdb->prefix . 'umbrella_blog_post_categories';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM {$categories_table} c
            INNER JOIN {$post_categories_table} pc ON c.id = pc.category_id
            WHERE pc.post_id = %d
            ORDER BY c.name",
            $post_id
        ));
    }

    /**
     * Get post tags
     */
    private static function get_post_tags($post_id) {
        global $wpdb;
        $tags_table = $wpdb->prefix . 'umbrella_blog_tags';
        $post_tags_table = $wpdb->prefix . 'umbrella_blog_post_tags';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$tags_table} t
            INNER JOIN {$post_tags_table} pt ON t.id = pt.tag_id
            WHERE pt.post_id = %d
            ORDER BY t.name",
            $post_id
        ));
    }
}
