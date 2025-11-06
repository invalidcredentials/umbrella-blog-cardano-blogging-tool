<?php
/**
 * Blog Signing Wallet Settings Page
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/WalletLoader.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/BlockfrostHelper.php';

// Handle form submission
if (isset($_POST['save_blog_signer_settings'])) {
    check_admin_referer('blog_signer_settings', 'blog_signer_nonce');

    $mode = sanitize_text_field($_POST['signer_mode']);
    update_option('cardano_blog_signer_mode', $mode);

    // Save selected handle
    if (isset($_POST['selected_handle'])) {
        update_option('cardano_blog_signer_selected_handle', sanitize_text_field($_POST['selected_handle']));
    }

    // Save dev mode settings
    $dev_mode = isset($_POST['dev_mode']) ? 1 : 0;
    update_option('cardano_blog_signer_dev_mode', $dev_mode);

    if ($dev_mode && !empty($_POST['dev_mainnet_address'])) {
        update_option('cardano_blog_signer_dev_mainnet_address', sanitize_text_field($_POST['dev_mainnet_address']));
    }

    if ($mode === 'custom') {
        $mnemonic = sanitize_textarea_field($_POST['custom_mnemonic']);
        $network = sanitize_text_field($_POST['custom_network']);

        if (!empty($mnemonic)) {
            // Encrypt and save
            if (class_exists('\CardanoMintPay\Helpers\EncryptionHelper')) {
                $encrypted = \CardanoMintPay\Helpers\EncryptionHelper::encrypt($mnemonic);
            } else {
                $encrypted = base64_encode($mnemonic);
            }

            update_option('cardano_blog_signer_custom_mnemonic_encrypted', $encrypted);
            update_option('cardano_blog_signer_custom_network', $network);

            echo '<div class="notice notice-success is-dismissible"><p>Custom wallet imported successfully!</p></div>';
        }
    }

    if ($mode === 'generated') {
        $network = sanitize_text_field($_POST['custom_network']);

        // Generate new wallet
        $result = UmbrellaBlog_WalletLoader::generateNewWallet($network);

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error is-dismissible"><p>Generation failed: ' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            // Show mnemonic once
            set_transient('blog_signer_new_mnemonic_' . get_current_user_id(), $result['mnemonic'], 300);
            echo '<div class="notice notice-success is-dismissible"><p>New wallet generated! Save your mnemonic below.</p></div>';
        }
    }

    if ($mode === 'mint_manager') {
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved! Using Mint Manager wallet.</p></div>';
    }
}

// Test current configuration
$mode = get_option('cardano_blog_signer_mode', 'mint_manager');
$dev_mode = get_option('cardano_blog_signer_dev_mode', 0);
$dev_mainnet_address = get_option('cardano_blog_signer_dev_mainnet_address', '');
$test_result = UmbrellaBlog_WalletLoader::testWalletConnection();

// Get ALL handles if wallet is connected
$all_handles = [];
if ($dev_mode && !empty($dev_mainnet_address)) {
    // DEV MODE: Fetch handles from specified mainnet address
    $all_handles = UmbrellaBlog_BlockfrostHelper::getAllHandlesFromAddress(
        $dev_mainnet_address,
        'mainnet'
    );
} else if ($test_result['success'] && !empty($test_result['address'])) {
    // NORMAL MODE: Fetch handles from wallet
    $all_handles = UmbrellaBlog_BlockfrostHelper::getAllHandlesFromAddress(
        $test_result['address'],
        $test_result['network']
    );
}

// Get currently selected handle
$selected_handle = get_option('cardano_blog_signer_selected_handle', '');

// Check for one-time mnemonic display
$new_mnemonic = get_transient('blog_signer_new_mnemonic_' . get_current_user_id());
if ($new_mnemonic) {
    delete_transient('blog_signer_new_mnemonic_' . get_current_user_id());
}
?>

<div class="wrap">
    <h1>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="vertical-align: middle;">
            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
        </svg>
        Blog Signing Wallet
    </h1>

    <p>Configure the Cardano wallet used to sign your blog posts on the blockchain.</p>

    <?php if ($new_mnemonic): ?>
        <div class="notice notice-warning" style="border-left-color: #ff6b00;">
            <h3 style="margin-top: 0;">⚠️ SAVE YOUR MNEMONIC - THIS WILL ONLY BE SHOWN ONCE!</h3>
            <p style="background: #fff; padding: 15px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">
                <?php echo esc_html($new_mnemonic); ?>
            </p>
            <p><strong>Write this down and store it securely. You will NOT be able to see this again!</strong></p>
        </div>
    <?php endif; ?>

    <?php if ($test_result['success']): ?>
        <div class="notice notice-success" style="border-left-color: #00E6FF;">
            <h3 style="margin-top: 0; color: #00E6FF;">
                ✅ Wallet Connected
                <?php if ($dev_mode && !empty($dev_mainnet_address)): ?>
                    <span style="background: #ff6b00; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; margin-left: 10px;">
                        🔧 DEV MODE
                    </span>
                <?php endif; ?>
            </h3>
            <table class="form-table" style="margin: 0;">
                <tr>
                    <th style="padding-left: 0;">Source:</th>
                    <td><code><?php echo esc_html($test_result['source']); ?></code></td>
                </tr>
                <?php if (!empty($test_result['wallet_name'])): ?>
                <tr>
                    <th style="padding-left: 0;">Wallet:</th>
                    <td><?php echo esc_html($test_result['wallet_name']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th style="padding-left: 0;">Address:</th>
                    <td><code style="font-size: 11px;"><?php echo esc_html($test_result['address']); ?></code></td>
                </tr>
                <tr>
                    <th style="padding-left: 0;">Network:</th>
                    <td><strong><?php echo esc_html(ucfirst($test_result['network'])); ?></strong></td>
                </tr>
                <?php if ($dev_mode && !empty($dev_mainnet_address)): ?>
                <tr style="background: rgba(255, 107, 0, 0.05);">
                    <th style="padding-left: 0;">Dev Lookup Address:</th>
                    <td>
                        <code style="font-size: 11px; color: #ff6b00;"><?php echo esc_html($dev_mainnet_address); ?></code>
                        <br><small style="opacity: 0.7;">Fetching handles from mainnet production wallet</small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($all_handles)): ?>
                <tr>
                    <th style="padding-left: 0;">ADA Handles:</th>
                    <td>
                        <strong style="color: #00E6FF; font-size: 14px;">
                            <?php echo count($all_handles); ?> handle<?php echo count($all_handles) > 1 ? 's' : ''; ?> detected
                            <?php if ($dev_mode): ?>
                                <span style="color: #ff6b00;">(from mainnet)</span>
                            <?php endif; ?>
                        </strong>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <th style="padding-left: 0;">ADA Handle:</th>
                    <td><em>None detected (optional - posts will be signed anonymously)</em></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="notice notice-warning">
            <h3 style="margin-top: 0;">⚠️ Wallet Not Configured</h3>
            <p><?php echo esc_html($test_result['error']); ?></p>
        </div>
    <?php endif; ?>

    <hr>

    <form method="post" style="max-width: 800px;">
        <?php wp_nonce_field('blog_signer_settings', 'blog_signer_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label>Wallet Source</label>
                </th>
                <td>
                    <fieldset>
                        <label style="display: block; margin-bottom: 20px;">
                            <input type="radio" name="signer_mode" value="mint_manager"
                                   <?php checked($mode, 'mint_manager'); ?>>
                            <strong style="font-size: 14px;">Use Mint Manager Wallet (Recommended)</strong>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-top: -15px; margin-bottom: 25px;">
                            Reuse the policy wallet from <strong>Cardano Mint Pay</strong> plugin.
                            <br>This wallet already has your ADA Handle and is securely encrypted.
                            <br><em>Requires: Cardano Mint Pay plugin to be active</em>
                        </p>

                        <label style="display: block; margin-bottom: 20px;">
                            <input type="radio" name="signer_mode" value="custom"
                                   <?php checked($mode, 'custom'); ?>>
                            <strong style="font-size: 14px;">Import Existing Wallet</strong>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-top: -15px; margin-bottom: 15px;">
                            Enter your own 24-word mnemonic phrase.
                        </p>

                        <div id="custom-wallet-fields" style="display: <?php echo $mode === 'custom' ? 'block' : 'none'; ?>; margin-left: 35px; margin-bottom: 25px;">
                            <label style="display: block; margin-bottom: 10px;">
                                <strong>Mnemonic Phrase (24 words):</strong><br>
                                <textarea name="custom_mnemonic" rows="3" cols="60" class="large-text code"
                                          placeholder="word1 word2 word3 ..."></textarea>
                            </label>
                            <label style="display: block;">
                                <strong>Network:</strong><br>
                                <label><input type="radio" name="custom_network" value="mainnet" checked> Mainnet</label>
                                <label style="margin-left: 20px;"><input type="radio" name="custom_network" value="preprod"> Preprod</label>
                            </label>
                        </div>

                        <label style="display: block; margin-bottom: 20px;">
                            <input type="radio" name="signer_mode" value="generated"
                                   <?php checked($mode, 'generated'); ?>>
                            <strong style="font-size: 14px;">Generate New Wallet</strong>
                        </label>
                        <p class="description" style="margin-left: 25px; margin-top: -15px; margin-bottom: 15px;">
                            Create a fresh wallet specifically for blog signing.
                        </p>

                        <div id="generated-wallet-fields" style="display: <?php echo $mode === 'generated' ? 'block' : 'none'; ?>; margin-left: 35px; margin-bottom: 25px;">
                            <label style="display: block;">
                                <strong>Network:</strong><br>
                                <label><input type="radio" name="custom_network" value="mainnet" checked> Mainnet</label>
                                <label style="margin-left: 20px;"><input type="radio" name="custom_network" value="preprod"> Preprod</label>
                            </label>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <tr style="border-top: 2px solid #ff6b00;">
                <th scope="row">
                    <label>🔧 Dev Mode (Testing)</label>
                </th>
                <td>
                    <fieldset>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <input type="checkbox" name="dev_mode" value="1" <?php checked($dev_mode, 1); ?>>
                            <strong style="font-size: 14px;">Enable Dev Mode - Use Mainnet Wallet for Handle Lookup</strong>
                        </label>
                        <p class="description" style="margin-bottom: 15px;">
                            When enabled, you can paste a mainnet wallet address to fetch handles from production.
                            <br><strong>Perfect for testing:</strong> Use real handles on preprod/testnet!
                        </p>

                        <div id="dev-mode-fields" style="display: <?php echo $dev_mode ? 'block' : 'none'; ?>; margin-top: 15px; padding: 15px; background: rgba(255, 107, 0, 0.05); border: 1px solid rgba(255, 107, 0, 0.2); border-radius: 8px;">
                            <label style="display: block; margin-bottom: 10px;">
                                <strong>Mainnet Wallet Address:</strong><br>
                                <input type="text" name="dev_mainnet_address" class="large-text code"
                                       value="<?php echo esc_attr($dev_mainnet_address); ?>"
                                       placeholder="addr1q...">
                            </label>
                            <p class="description" style="margin: 0; font-size: 11px; opacity: 0.8;">
                                Example: addr1qxdrr4ryyckrtxkesvedw53n99ac7s3dh5a7xc3jn53awn03fse50dj55rt5vvqc29apuxfkhpk2qff6a5v69h2lhf8q8wqnzy
                            </p>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <?php if (!empty($all_handles)): ?>
            <tr>
                <th scope="row">
                    <label for="selected_handle">Select Handle for Signing</label>
                </th>
                <td>
                    <select name="selected_handle" id="selected_handle" class="regular-text" style="max-width: 400px;">
                        <option value="">Anonymous (no handle)</option>
                        <?php foreach ($all_handles as $handle): ?>
                            <option value="<?php echo esc_attr($handle['name']); ?>"
                                    <?php selected($selected_handle, $handle['name']); ?>
                                    data-image="<?php echo esc_attr($handle['image']); ?>">
                                <?php echo esc_html($handle['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="handle-preview" style="margin-top: 15px; padding: 15px; background: rgba(0, 230, 255, 0.05); border: 1px solid rgba(0, 230, 255, 0.2); border-radius: 8px; display: none;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img id="handle-preview-image" src="" style="width: 80px; height: 80px; border-radius: 12px; border: 2px solid #00E6FF; object-fit: cover;">
                            <div>
                                <div style="font-size: 18px; font-weight: 600; color: #00E6FF;" id="handle-preview-name"></div>
                                <div style="font-size: 12px; opacity: 0.7;">This handle will appear on signed blog posts</div>
                            </div>
                        </div>
                    </div>

                    <p class="description">
                        Choose which ADA Handle to display as the author on signed posts.
                        <br>Your wallet contains <?php echo count($all_handles); ?> handle<?php echo count($all_handles) > 1 ? 's' : ''; ?>.
                    </p>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <?php submit_button('Save Configuration', 'primary large', 'save_blog_signer_settings'); ?>
    </form>

    <script>
    document.querySelectorAll('input[name="signer_mode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('custom-wallet-fields').style.display =
                this.value === 'custom' ? 'block' : 'none';
            document.getElementById('generated-wallet-fields').style.display =
                this.value === 'generated' ? 'block' : 'none';
        });
    });

    // Dev mode toggle
    const devModeCheckbox = document.querySelector('input[name="dev_mode"]');
    const devModeFields = document.getElementById('dev-mode-fields');
    if (devModeCheckbox && devModeFields) {
        devModeCheckbox.addEventListener('change', function() {
            devModeFields.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Handle preview on selection
    const handleSelect = document.getElementById('selected_handle');
    const handlePreview = document.getElementById('handle-preview');
    const handlePreviewImage = document.getElementById('handle-preview-image');
    const handlePreviewName = document.getElementById('handle-preview-name');

    if (handleSelect) {
        // Show preview on page load if handle is selected
        if (handleSelect.value) {
            const selectedOption = handleSelect.options[handleSelect.selectedIndex];
            const imageUrl = selectedOption.getAttribute('data-image');
            if (imageUrl) {
                handlePreviewImage.src = imageUrl;
                handlePreviewName.textContent = handleSelect.value;
                handlePreview.style.display = 'block';
            }
        }

        // Update preview on change
        handleSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                const imageUrl = selectedOption.getAttribute('data-image');
                if (imageUrl) {
                    handlePreviewImage.src = imageUrl;
                    handlePreviewName.textContent = this.value;
                    handlePreview.style.display = 'block';
                } else {
                    handlePreview.style.display = 'none';
                }
            } else {
                handlePreview.style.display = 'none';
            }
        });
    }
    </script>

    <hr style="margin-top: 40px;">

    <h2>About Blog Signing</h2>
    <p>When you sign a blog post:</p>
    <ul>
        <li>A transaction is created with <strong>CIP-20 metadata</strong> containing the post title, URL, and your ADA Handle</li>
        <li>The transaction is signed with your wallet and submitted to the Cardano blockchain</li>
        <li>This provides <strong>immutable proof of authorship</strong> and publication date</li>
        <li>The signature appears at the bottom of the published post with your handle image and transaction link</li>
        <li>Cost: ~0.17 ADA per signature (~$0.10 USD)</li>
    </ul>

    <p>
        <a href="<?php echo admin_url('admin.php?page=umbrella-blog'); ?>" class="button">
            ← Back to Blog Posts
        </a>
    </p>
</div>

<style>
.wrap h1 svg {
    stroke: #00E6FF;
}
</style>
