<?php
/**
 * Theme Manager
 * Injects CSS variables based on user settings
 * Preserves glassmorphic design as default
 */

class UmbrellaBlog_ThemeManager {

    /**
     * Initialize theme hooks
     */
    public static function init() {
        add_action('wp_head', [self::class, 'injectFrontendStyles'], 5);
        add_action('admin_head', [self::class, 'injectAdminStyles'], 5);
    }

    /**
     * Inject frontend styles
     */
    public static function injectFrontendStyles() {
        self::injectStyles();
    }

    /**
     * Inject admin styles
     */
    public static function injectAdminStyles() {
        // Only on blog admin pages
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'umbrella-blog') !== false) {
            self::injectStyles();
        }
    }

    /**
     * Generate and inject CSS variables
     */
    private static function injectStyles() {
        // Get user settings (with defaults)
        $primary = get_option('umbrella_blog_primary_color', '#00E6FF');
        $secondary = get_option('umbrella_blog_secondary_color', '#FF00E6');
        $glass_enabled = get_option('umbrella_blog_enable_glass', 1);
        $custom_css = get_option('umbrella_blog_custom_css', '');

        // Calculate glassmorphic values
        $glass_bg = $glass_enabled ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.8)';
        $glass_border = $glass_enabled ? 'rgba(255, 255, 255, 0.1)' : 'rgba(255, 255, 255, 0.2)';
        $glass_blur = $glass_enabled ? '20px' : '0px';
        $glass_shadow = '0 8px 32px rgba(0, 0, 0, 0.3)';

        ?>
        <style id="umbrella-blog-theme">
        :root {
            /* Primary Colors */
            --ub-primary: <?php echo esc_attr($primary); ?>;
            --ub-secondary: <?php echo esc_attr($secondary); ?>;

            /* Glassmorphic Design */
            --ub-glass-bg: <?php echo esc_attr($glass_bg); ?>;
            --ub-glass-border: <?php echo esc_attr($glass_border); ?>;
            --ub-glass-blur: <?php echo esc_attr($glass_blur); ?>;
            --ub-glass-shadow: <?php echo esc_attr($glass_shadow); ?>;

            /* Derived Colors (with transparency) */
            --ub-primary-10: <?php echo self::hexToRgba($primary, 0.1); ?>;
            --ub-primary-20: <?php echo self::hexToRgba($primary, 0.2); ?>;
            --ub-primary-30: <?php echo self::hexToRgba($primary, 0.3); ?>;
            --ub-primary-50: <?php echo self::hexToRgba($primary, 0.5); ?>;

            --ub-secondary-10: <?php echo self::hexToRgba($secondary, 0.1); ?>;
            --ub-secondary-20: <?php echo self::hexToRgba($secondary, 0.2); ?>;
            --ub-secondary-30: <?php echo self::hexToRgba($secondary, 0.3); ?>;
        }

        /* Glassmorphic Card Base */
        .glass-card,
        .blog-post,
        .cardano-signature-block {
            background: var(--ub-glass-bg) !important;
            backdrop-filter: blur(var(--ub-glass-blur)) !important;
            -webkit-backdrop-filter: blur(var(--ub-glass-blur)) !important;
            border: 1px solid var(--ub-glass-border) !important;
            box-shadow: var(--ub-glass-shadow) !important;
        }

        /* Primary Color Applications */
        .gradient-text,
        .author-handle,
        .signature-header h3 {
            background: linear-gradient(135deg, var(--ub-primary), var(--ub-secondary)) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }

        /* Custom CSS Override */
        <?php echo wp_kses_post($custom_css); ?>
        </style>
        <?php
    }

    /**
     * Convert hex color to rgba
     *
     * @param string $hex Hex color (#RRGGBB)
     * @param float $alpha Alpha transparency (0-1)
     * @return string rgba() CSS value
     */
    private static function hexToRgba($hex, $alpha = 1.0) {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "rgba($r, $g, $b, $alpha)";
    }
}
