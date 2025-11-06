<?php
/**
 * Plugin Settings Page
 * Appearance, API configuration, and network settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['umb_settings_submit'])) {
    check_admin_referer('umb_settings_save', 'umb_settings_nonce');

    if (current_user_can('manage_options')) {
        // Appearance settings
        update_option('umbrella_blog_primary_color', sanitize_hex_color($_POST['primary_color'] ?? '#00E6FF'));
        update_option('umbrella_blog_secondary_color', sanitize_hex_color($_POST['secondary_color'] ?? '#FF00E6'));
        update_option('umbrella_blog_enable_glass', isset($_POST['enable_glass']) ? 1 : 0);
        update_option('umbrella_blog_custom_css', wp_kses_post($_POST['custom_css'] ?? ''));

        // API settings
        update_option('umbrella_blog_anvil_preprod_api_key', sanitize_text_field($_POST['anvil_preprod_api_key'] ?? ''));
        update_option('umbrella_blog_anvil_mainnet_api_key', sanitize_text_field($_POST['anvil_mainnet_api_key'] ?? ''));
        update_option('umbrella_blog_blockfrost_preprod_key', sanitize_text_field($_POST['blockfrost_preprod_key'] ?? ''));
        update_option('umbrella_blog_blockfrost_mainnet_key', sanitize_text_field($_POST['blockfrost_mainnet_key'] ?? ''));

        // Network setting
        update_option('umbrella_blog_default_network', sanitize_text_field($_POST['default_network'] ?? 'preprod'));

        // Dev mode settings (for testing with different mainnet address)
        update_option('cardano_blog_signer_dev_mode', isset($_POST['dev_mode']) ? 1 : 0);
        update_option('cardano_blog_signer_dev_mainnet_address', sanitize_text_field($_POST['dev_mainnet_address'] ?? ''));

        echo '<div class="umb-card" style="border: 2px solid var(--umb-success); margin-bottom: var(--umb-space-lg);"><div class="umb-card-body" style="padding: var(--umb-space-md);"><p style="color: var(--umb-success); margin: 0;"><span style="color: var(--umb-success);">✓</span> Settings saved successfully!</p></div></div>';
    }
}

// Get current values
$primary_color = get_option('umbrella_blog_primary_color', '#00E6FF');
$secondary_color = get_option('umbrella_blog_secondary_color', '#FF00E6');
$enable_glass = get_option('umbrella_blog_enable_glass', 1);
$custom_css = get_option('umbrella_blog_custom_css', '');

$anvil_preprod_key = get_option('umbrella_blog_anvil_preprod_api_key', '');
$anvil_mainnet_key = get_option('umbrella_blog_anvil_mainnet_api_key', '');
$bf_preprod = get_option('umbrella_blog_blockfrost_preprod_key', '');
$bf_mainnet = get_option('umbrella_blog_blockfrost_mainnet_key', '');

$default_network = get_option('umbrella_blog_default_network', 'preprod');

// Dev mode settings
$dev_mode = get_option('cardano_blog_signer_dev_mode', 0);
$dev_mainnet_address = get_option('cardano_blog_signer_dev_mainnet_address', '');

// Color presets
$presets = [
    'cyberpunk' => ['primary' => '#00E6FF', 'secondary' => '#FF00E6', 'name' => 'Cyberpunk (Default)'],
    'matrix' => ['primary' => '#00ff41', 'secondary' => '#00ff41', 'name' => 'Matrix Green'],
    'sunset' => ['primary' => '#FF6B6B', 'secondary' => '#FFD93D', 'name' => 'Sunset Vibes'],
    'ocean' => ['primary' => '#4ECDC4', 'secondary' => '#556FB5', 'name' => 'Deep Ocean'],
    'purple' => ['primary' => '#A78BFA', 'secondary' => '#C084FC', 'name' => 'Purple Dream'],
    'fire' => ['primary' => '#FF6B35', 'secondary' => '#F7931E', 'name' => 'Fire & Ice'],
];
?>

<div class="wrap umbrella-admin-page">
    <h1 style="font-size: 32px; font-weight: 700; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xl);">
        <span style="color: var(--umb-cyan);">◎</span> Settings
    </h1>

    <form method="post" action="" id="settings-form">
        <?php wp_nonce_field('umb_settings_save', 'umb_settings_nonce'); ?>

        <!-- Appearance Settings -->
        <div class="umb-card">
            <div class="umb-card-header">
                <h2 class="umb-card-title"><span style="color: var(--umb-cyan);">◈</span> Appearance</h2>
            </div>
            <div class="umb-card-body">
                <!-- Color Presets -->
                <div style="margin-bottom: var(--umb-space-xl);">
                    <label style="display: block; font-weight: 600; margin-bottom: var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;">
                        Color Presets
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: var(--umb-space-md);">
                        <?php foreach ($presets as $key => $preset): ?>
                            <button type="button" class="preset-btn" data-primary="<?php echo esc_attr($preset['primary']); ?>" data-secondary="<?php echo esc_attr($preset['secondary']); ?>" style="background: linear-gradient(135deg, <?php echo esc_attr($preset['primary']); ?> 0%, <?php echo esc_attr($preset['secondary']); ?> 100%); border: 2px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); color: white; font-weight: 600; cursor: pointer; transition: all var(--umb-transition-base); text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                                <?php echo esc_html($preset['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size: 12px; color: var(--umb-text-muted); margin-top: var(--umb-space-sm);">
                        Click a preset to apply, or customize colors below
                    </p>
                </div>

                <!-- Custom Colors -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--umb-space-lg); margin-bottom: var(--umb-space-lg);">
                    <div>
                        <label for="primary_color" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Primary Color
                        </label>
                        <div style="display: flex; align-items: center; gap: var(--umb-space-md);">
                            <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>" style="width: 60px; height: 40px; border: 2px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); background: transparent; cursor: pointer;">
                            <input type="text" id="primary_color_text" value="<?php echo esc_attr($primary_color); ?>" readonly style="flex: 1; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); padding: var(--umb-space-sm); color: var(--umb-cyan); font-family: monospace; font-size: 14px;">
                        </div>
                        <p style="font-size: 11px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                            Main accent color for highlights
                        </p>
                    </div>
                    <div>
                        <label for="secondary_color" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Secondary Color
                        </label>
                        <div style="display: flex; align-items: center; gap: var(--umb-space-md);">
                            <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>" style="width: 60px; height: 40px; border: 2px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); background: transparent; cursor: pointer;">
                            <input type="text" id="secondary_color_text" value="<?php echo esc_attr($secondary_color); ?>" readonly style="flex: 1; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); padding: var(--umb-space-sm); color: var(--umb-magenta); font-family: monospace; font-size: 14px;">
                        </div>
                        <p style="font-size: 11px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                            Secondary accent for gradients
                        </p>
                    </div>
                </div>

                <!-- Live Preview -->
                <div style="background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.2) 100%); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-lg); padding: var(--umb-space-lg); margin-bottom: var(--umb-space-lg);">
                    <div style="font-size: 12px; color: var(--umb-text-muted); margin-bottom: var(--umb-space-md); text-transform: uppercase; letter-spacing: 1px;">Live Preview</div>
                    <div style="display: flex; gap: var(--umb-space-md); margin-bottom: var(--umb-space-md);">
                        <div id="preview-primary" style="flex: 1; padding: var(--umb-space-lg); background: <?php echo esc_attr($primary_color); ?>; color: white; border-radius: var(--umb-radius-md); text-align: center; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                            Primary
                        </div>
                        <div id="preview-secondary" style="flex: 1; padding: var(--umb-space-lg); background: <?php echo esc_attr($secondary_color); ?>; color: white; border-radius: var(--umb-radius-md); text-align: center; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                            Secondary
                        </div>
                    </div>
                    <div id="preview-gradient" style="padding: var(--umb-space-lg); background: linear-gradient(135deg, <?php echo esc_attr($primary_color); ?> 0%, <?php echo esc_attr($secondary_color); ?> 100%); color: white; border-radius: var(--umb-radius-md); text-align: center; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        Gradient Blend
                    </div>
                </div>

                <!-- Glassmorphic Toggle -->
                <div style="margin-bottom: var(--umb-space-lg);">
                    <label style="display: flex; align-items: center; gap: var(--umb-space-md); cursor: pointer; padding: var(--umb-space-md); background: rgba(0, 0, 0, 0.2); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); transition: all var(--umb-transition-base);" onmouseover="this.style.borderColor='var(--umb-cyan)'" onmouseout="this.style.borderColor='var(--umb-glass-border)'">
                        <input type="checkbox" id="enable_glass" name="enable_glass" value="1" <?php checked($enable_glass, 1); ?> style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xs);">
                                Enable Glassmorphic Theme
                            </div>
                            <div style="font-size: 12px; color: var(--umb-text-secondary);">
                                Beautiful translucent backgrounds with frosted glass blur effect
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Custom CSS -->
                <div>
                    <label for="custom_css" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                        Custom CSS
                    </label>
                    <textarea id="custom_css" name="custom_css" rows="8" style="width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); color: var(--umb-text-primary); font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; resize: vertical;" placeholder="/* Add your custom CSS here */"><?php echo esc_textarea($custom_css); ?></textarea>
                    <p style="font-size: 11px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                        Override default styles with your own CSS rules
                    </p>
                </div>
            </div>
        </div>

        <!-- Cardano API Settings -->
        <div class="umb-card">
            <div class="umb-card-header">
                <h2 class="umb-card-title"><span style="color: var(--umb-cyan);">⚿</span> Cardano API Configuration</h2>
            </div>
            <div class="umb-card-body">
                <div style="display: grid; gap: var(--umb-space-lg);">
                    <!-- Anvil Preprod -->
                    <div>
                        <label for="anvil_preprod_api_key" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Anvil Preprod API Key
                        </label>
                        <input type="password" id="anvil_preprod_api_key" name="anvil_preprod_api_key" value="<?php echo esc_attr($anvil_preprod_key); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-sm) var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;" placeholder="Enter your Anvil preprod API key">
                        <p style="font-size: 11px; color: var(--umb-text-secondary); margin-top: var(--umb-space-xs);">
                            For preprod testnet transactions • <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px;">https://preprod.api.ada-anvil.app/v2/services</code><br>
                            Get your key from <a href="https://ada-anvil.io" target="_blank" style="color: var(--umb-cyan); text-decoration: none;">ada-anvil.io ↗</a>
                        </p>
                    </div>

                    <!-- Anvil Mainnet -->
                    <div>
                        <label for="anvil_mainnet_api_key" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Anvil Mainnet API Key
                        </label>
                        <input type="password" id="anvil_mainnet_api_key" name="anvil_mainnet_api_key" value="<?php echo esc_attr($anvil_mainnet_key); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-sm) var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;" placeholder="Enter your Anvil mainnet API key">
                        <p style="font-size: 11px; color: var(--umb-text-secondary); margin-top: var(--umb-space-xs);">
                            For mainnet production transactions • <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 3px;">https://mainnet.api.ada-anvil.app/v2/services</code><br>
                            Get your key from <a href="https://ada-anvil.io" target="_blank" style="color: var(--umb-cyan); text-decoration: none;">ada-anvil.io ↗</a>
                        </p>
                    </div>

                    <!-- Blockfrost Preprod -->
                    <div>
                        <label for="blockfrost_preprod_key" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Blockfrost Preprod Key
                        </label>
                        <input type="password" id="blockfrost_preprod_key" name="blockfrost_preprod_key" value="<?php echo esc_attr($bf_preprod); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-sm) var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;" placeholder="Enter your Blockfrost preprod key">
                        <p style="font-size: 11px; color: var(--umb-text-secondary); margin-top: var(--umb-space-xs);">
                            For fetching ADA Handle metadata on preprod testnet
                        </p>
                    </div>

                    <!-- Blockfrost Mainnet -->
                    <div>
                        <label for="blockfrost_mainnet_key" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Blockfrost Mainnet Key
                        </label>
                        <input type="password" id="blockfrost_mainnet_key" name="blockfrost_mainnet_key" value="<?php echo esc_attr($bf_mainnet); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-sm) var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;" placeholder="Enter your Blockfrost mainnet key">
                        <p style="font-size: 11px; color: var(--umb-text-secondary); margin-top: var(--umb-space-xs);">
                            For fetching ADA Handle metadata on mainnet
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Settings -->
        <div class="umb-card">
            <div class="umb-card-header">
                <h2 class="umb-card-title"><span style="color: var(--umb-cyan);">◎</span> Network Configuration</h2>
            </div>
            <div class="umb-card-body">
                <!-- Default Network -->
                <div style="margin-bottom: var(--umb-space-xl);">
                    <label style="display: block; font-weight: 600; margin-bottom: var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px;">
                        Default Network
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--umb-space-md);">
                        <label style="display: flex; align-items: center; padding: var(--umb-space-lg); background: rgba(255, 170, 0, 0.05); border: 2px solid <?php echo $default_network === 'preprod' ? 'var(--umb-warning)' : 'var(--umb-glass-border)'; ?>; border-radius: var(--umb-radius-md); cursor: pointer; transition: all var(--umb-transition-base);" onmouseover="if(!this.querySelector('input').checked) this.style.borderColor='var(--umb-warning)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='var(--umb-glass-border)'">
                            <input type="radio" name="default_network" value="preprod" <?php checked($default_network, 'preprod'); ?> style="width: 20px; height: 20px; margin-right: var(--umb-space-md); cursor: pointer;">
                            <div>
                                <div style="font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xs);">Preprod</div>
                                <div style="font-size: 12px; color: var(--umb-text-secondary);">Testnet (Recommended)</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; padding: var(--umb-space-lg); background: rgba(0, 255, 136, 0.05); border: 2px solid <?php echo $default_network === 'mainnet' ? 'var(--umb-success)' : 'var(--umb-glass-border)'; ?>; border-radius: var(--umb-radius-md); cursor: pointer; transition: all var(--umb-transition-base);" onmouseover="if(!this.querySelector('input').checked) this.style.borderColor='var(--umb-success)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='var(--umb-glass-border)'">
                            <input type="radio" name="default_network" value="mainnet" <?php checked($default_network, 'mainnet'); ?> style="width: 20px; height: 20px; margin-right: var(--umb-space-md); cursor: pointer;">
                            <div>
                                <div style="font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xs);">Mainnet</div>
                                <div style="font-size: 12px; color: var(--umb-text-secondary);">Production</div>
                            </div>
                        </label>
                    </div>
                    <div style="margin-top: var(--umb-space-md); padding: var(--umb-space-md); background: rgba(255, 170, 0, 0.1); border-left: 3px solid var(--umb-warning); border-radius: var(--umb-radius-sm);">
                        <p style="font-size: 12px; color: var(--umb-text-secondary); margin: 0;">
                            <strong style="color: var(--umb-warning);">⚠️ Important:</strong> Wallets are network-specific. Changing this will switch which wallet you're using.
                        </p>
                    </div>
                </div>

                <!-- Dev Mode -->
                <div>
                    <label style="display: flex; align-items: center; gap: var(--umb-space-md); cursor: pointer; padding: var(--umb-space-md); background: rgba(0, 0, 0, 0.2); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); margin-bottom: var(--umb-space-md); transition: all var(--umb-transition-base);" onmouseover="this.style.borderColor='var(--umb-cyan)'" onmouseout="this.style.borderColor='var(--umb-glass-border)'">
                        <input type="checkbox" id="dev_mode" name="dev_mode" value="1" <?php checked($dev_mode, 1); ?> style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xs);">
                                <span style="color: var(--umb-cyan);">◈</span> Dev Mode (Handle Testing)
                            </div>
                            <div style="font-size: 12px; color: var(--umb-text-secondary);">
                                Use different mainnet address for ADA Handle lookup while testing on preprod
                            </div>
                        </div>
                    </label>

                    <div id="dev_address_row" style="<?php echo $dev_mode ? '' : 'display: none;'; ?>">
                        <label for="dev_mainnet_address" style="display: block; font-weight: 600; margin-bottom: var(--umb-space-sm); color: var(--umb-text-primary); font-size: 13px;">
                            Dev Mode Mainnet Address
                        </label>
                        <input type="text" id="dev_mainnet_address" name="dev_mainnet_address" value="<?php echo esc_attr($dev_mainnet_address); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-sm) var(--umb-space-md); color: var(--umb-text-primary); font-family: monospace; font-size: 13px;" placeholder="addr1...">
                        <p style="font-size: 11px; color: var(--umb-text-secondary); margin-top: var(--umb-space-xs);">
                            Mainnet address to fetch ADA Handles from when dev mode is enabled.<br>
                            <strong style="color: var(--umb-warning);">⚠️</strong> Only affects handle lookup - transactions still use your signing wallet!
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div style="text-align: center; margin-top: var(--umb-space-xl);">
            <button type="submit" name="umb_settings_submit" class="umb-btn umb-btn-primary umb-btn-large" style="min-width: 200px;">
                <span style="color: var(--umb-cyan);">▭</span> Save Settings
            </button>
        </div>
    </form>
</div>

<script>
// Color picker live preview
document.getElementById('primary_color').addEventListener('input', function() {
    const color = this.value;
    document.getElementById('primary_color_text').value = color;
    document.getElementById('preview-primary').style.background = color;
    updateGradientPreview();
});

document.getElementById('secondary_color').addEventListener('input', function() {
    const color = this.value;
    document.getElementById('secondary_color_text').value = color;
    document.getElementById('preview-secondary').style.background = color;
    updateGradientPreview();
});

function updateGradientPreview() {
    const primary = document.getElementById('primary_color').value;
    const secondary = document.getElementById('secondary_color').value;
    document.getElementById('preview-gradient').style.background = `linear-gradient(135deg, ${primary} 0%, ${secondary} 100%)`;
}

// Preset buttons
document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const primary = this.dataset.primary;
        const secondary = this.dataset.secondary;

        document.getElementById('primary_color').value = primary;
        document.getElementById('primary_color_text').value = primary;
        document.getElementById('secondary_color').value = secondary;
        document.getElementById('secondary_color_text').value = secondary;

        document.getElementById('preview-primary').style.background = primary;
        document.getElementById('preview-secondary').style.background = secondary;
        updateGradientPreview();
    });

    // Hover effect
    btn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.4)';
    });

    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = 'none';
    });
});

// Toggle dev address field visibility
document.getElementById('dev_mode').addEventListener('change', function() {
    document.getElementById('dev_address_row').style.display = this.checked ? 'block' : 'none';
});
</script>
