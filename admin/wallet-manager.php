<?php
/**
 * Wallet Manager Admin Page
 * Generate and manage secure signing wallets for blog posts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/WalletModel.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vendor/UmbrellaBlog_EncryptionHelper.php';

// Get current network
$network = get_option('umbrella_blog_default_network', 'preprod');

// Get existing active wallet
$existing_wallet = UmbrellaBlog_WalletModel::getActiveWallet($network);

// Get archived wallets
$archived_wallets = UmbrellaBlog_WalletModel::getArchivedWallets($network);
$archived_count = UmbrellaBlog_WalletModel::countArchivedWallets($network);

// Check for one-time mnemonic display
$show_mnemonic = isset($_GET['created']) && $_GET['created'] == '1';
$mnemonic = $show_mnemonic ? get_transient('umb_wallet_mnemonic_' . get_current_user_id()) : false;

// Delete transient after retrieval (ONE-TIME VIEW)
if ($mnemonic) {
    delete_transient('umb_wallet_mnemonic_' . get_current_user_id());
}

// Decrypt extended key for display (blurred by default)
$extended_key_hex = null;
if ($existing_wallet && current_user_can('manage_options')) {
    $extended_key_hex = UmbrellaBlog_EncryptionHelper::decrypt($existing_wallet->skey_encrypted);
}
?>

<div class="wrap umbrella-admin-page">
    <h1 style="font-size: 32px; font-weight: 700; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xl); display: flex; align-items: center; gap: var(--umb-space-md);">
        <span style="color: var(--umb-cyan);">⚿</span> Wallet Manager
        <span class="umb-badge umb-badge-<?php echo $network === 'mainnet' ? 'mainnet' : 'preprod'; ?>">
            <span class="umb-badge-dot"></span>
            <?php echo strtoupper($network); ?>
        </span>
    </h1>

    <script>
    // Copy to clipboard with visual feedback
    function copyToClipboard(text, button) {
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(button);
            }).catch(function(err) {
                fallbackCopy(text, button);
            });
        } else {
            fallbackCopy(text, button);
        }
    }

    function showCopySuccess(button) {
        if (button) {
            const originalHTML = button.innerHTML;
            button.innerHTML = '✅ Copied!';
            button.classList.add('copied');
            setTimeout(function() {
                button.innerHTML = originalHTML;
                button.classList.remove('copied');
            }, 2000);
        } else {
            alert('✅ Copied to clipboard!');
        }
    }

    // Copy mnemonic
    function copyMnemonic(mnemonic) {
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(mnemonic).then(function() {
                alert('✅ Mnemonic copied! Store it safely offline.');
            }).catch(function(err) {
                fallbackCopyMnemonic(mnemonic);
            });
        } else {
            fallbackCopyMnemonic(mnemonic);
        }
    }

    // Fallback copy method using textarea (works everywhere)
    function fallbackCopy(text, button) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            alert('❌ Failed to copy. Please copy manually.');
        }
        document.body.removeChild(textarea);
    }

    // Fallback copy for mnemonic
    function fallbackCopyMnemonic(mnemonic) {
        var textarea = document.createElement('textarea');
        textarea.value = mnemonic;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('✅ Mnemonic copied! Store it safely offline.');
        } catch (err) {
            alert('❌ Failed to copy. Please copy manually.');
        }
        document.body.removeChild(textarea);
    }

    // Toggle extended key blur
    let extendedKeyRevealed = false;
    function toggleExtendedKey() {
        const display = document.getElementById('extended-key-display');
        const button = document.getElementById('toggle-extended-key');

        if (!extendedKeyRevealed) {
            display.style.filter = 'none';
            display.style.userSelect = 'text';
            button.innerHTML = '🙈 Hide Extended Key';
            extendedKeyRevealed = true;
        } else {
            display.style.filter = 'blur(8px)';
            display.style.userSelect = 'none';
            button.innerHTML = '👁️ Reveal Extended Key';
            extendedKeyRevealed = false;
        }
    }

    // Toggle collapsible sections
    function toggleCollapsible(header) {
        const collapsible = header.closest('.umb-collapsible');
        collapsible.classList.toggle('open');
    }

    // Archive wallet (AJAX)
    function archiveWallet(id, name) {
        if (!confirm('Archive wallet "' + name + '"?\n\nThis will make it inactive but keep it in the database.')) {
            return;
        }

        jQuery.post(ajaxurl, {
            action: 'umb_archive_wallet',
            nonce: '<?php echo wp_create_nonce('umb_wallet_ajax'); ?>',
            wallet_id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Wallet archived');
                location.reload();
            } else {
                alert('❌ Failed to archive wallet: ' + response.data);
            }
        });
    }

    // Unarchive wallet (AJAX)
    function unarchiveWallet(id, name) {
        if (!confirm('Activate wallet "' + name + '"?\n\nThis will make it the active signing wallet.')) {
            return;
        }

        jQuery.post(ajaxurl, {
            action: 'umb_unarchive_wallet',
            nonce: '<?php echo wp_create_nonce('umb_wallet_ajax'); ?>',
            wallet_id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Wallet activated');
                location.reload();
            } else {
                alert('❌ Failed to activate wallet: ' + response.data);
            }
        });
    }

    // Refresh wallet balance
    function refreshWalletBalance() {
        var btn = document.getElementById('refresh-balance-btn');
        var balanceDiv = document.getElementById('wallet-balance-content');

        btn.disabled = true;
        btn.textContent = '🔄 Loading...';
        balanceDiv.innerHTML = '<p>⏳ Fetching wallet data from Blockfrost...</p>';

        jQuery.post(ajaxurl, {
            action: 'umb_get_wallet_balance',
            nonce: '<?php echo wp_create_nonce('umb_wallet_ajax'); ?>'
        }, function(response) {
            btn.disabled = false;
            btn.textContent = '🔄 Refresh Balance';

            if (response.success) {
                balanceDiv.innerHTML = response.data.html;
            } else {
                balanceDiv.innerHTML = '<p style="color: #dc3545;">❌ ' + response.data + '</p>';
            }
        });
    }

    // Auto-load balance on page load
    jQuery(document).ready(function() {
        if (document.getElementById('wallet-balance-content')) {
            refreshWalletBalance();
        }
    });
    </script>

    <?php if ($mnemonic): ?>
        <!-- CRITICAL: ONE-TIME Mnemonic Display -->
        <div class="umb-card" style="border: 3px solid var(--umb-danger); background: linear-gradient(135deg, rgba(255, 51, 102, 0.15) 0%, rgba(255, 0, 230, 0.15) 100%); box-shadow: 0 0 32px rgba(255, 51, 102, 0.4), var(--umb-shadow-lg); animation: pulse-danger 2s ease-in-out infinite;">
            <div style="text-align: center; margin-bottom: var(--umb-space-xl);">
                <h2 style="font-size: 28px; font-weight: 700; color: var(--umb-danger); margin: 0 0 var(--umb-space-md) 0; text-transform: uppercase; letter-spacing: 2px;">
                    Save Your Recovery Phrase Now!
                </h2>
                <p style="font-size: 18px; color: var(--umb-text-primary); margin: 0 0 var(--umb-space-sm) 0;">
                    <strong>⚠️ THIS WILL ONLY BE SHOWN ONCE!</strong>
                </p>
                <p style="font-size: 14px; color: var(--umb-text-secondary); margin: 0;">
                    Make sure your screen is private. Write down these 24 words in order and store them securely.
                </p>
            </div>

            <div style="background: rgba(0, 0, 0, 0.6); border: 2px solid var(--umb-danger); border-radius: var(--umb-radius-lg); padding: var(--umb-space-xl); margin-bottom: var(--umb-space-xl); box-shadow: 0 0 24px rgba(255, 51, 102, 0.3);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--umb-space-sm); font-family: 'Courier New', monospace; font-size: 15px; user-select: all;">
                    <?php
                    $words = explode(' ', $mnemonic);
                    $word_num = 1;
                    foreach ($words as $word) {
                        echo '<div style="background: rgba(0, 230, 255, 0.05); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); padding: var(--umb-space-sm); display: flex; align-items: center; gap: var(--umb-space-sm);">';
                        echo '<span style="color: var(--umb-text-muted); font-size: 11px; min-width: 20px;">' . $word_num . '.</span>';
                        echo '<span style="color: var(--umb-cyan); font-weight: 600; flex: 1;">' . esc_html($word) . '</span>';
                        echo '</div>';
                        $word_num++;
                    }
                    ?>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--umb-space-md); align-items: center;">
                <button type="button" class="umb-btn umb-btn-primary umb-btn-large" onclick="copyMnemonic('<?php echo esc_js($mnemonic); ?>')" style="min-width: 250px;">
                    📋 Copy to Clipboard
                </button>
                <div style="background: rgba(255, 170, 0, 0.1); border: 1px solid var(--umb-warning); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); max-width: 600px; text-align: center;">
                    <div style="color: var(--umb-warning); font-size: 13px; font-weight: 600; margin-bottom: var(--umb-space-xs);">
                        💡 Security Tip
                    </div>
                    <div style="color: var(--umb-text-secondary); font-size: 12px; line-height: 1.6;">
                        Store this phrase offline in a secure location. Anyone with these words can access your wallet. Never share it online or take screenshots.
                    </div>
                </div>
            </div>
        </div>

        <style>
        @keyframes pulse-danger {
            0%, 100% { box-shadow: 0 0 32px rgba(255, 51, 102, 0.4), var(--umb-shadow-lg); }
            50% { box-shadow: 0 0 48px rgba(255, 51, 102, 0.6), var(--umb-shadow-lg); }
        }
        </style>
    <?php endif; ?>

    <?php if ($existing_wallet): ?>
        <!-- Hero Card - Active Wallet -->
        <div class="umb-card umb-card-hero">
            <div class="umb-card-header">
                <div>
                    <h2 class="umb-card-title">
                        <span style="color: var(--umb-success);">✓</span> <?php echo esc_html($existing_wallet->wallet_name); ?>
                        <span class="umb-badge umb-badge-active">
                            <span class="umb-badge-dot"></span>
                            Active
                        </span>
                    </h2>
                    <p class="umb-card-subtitle">
                        Created <?php echo human_time_diff(strtotime($existing_wallet->created_at), current_time('timestamp')); ?> ago
                    </p>
                </div>
                <div class="umb-flex-gap">
                    <button type="button" class="umb-btn umb-btn-secondary umb-btn-small" onclick="archiveWallet(<?php echo esc_js($existing_wallet->id); ?>, '<?php echo esc_js($existing_wallet->wallet_name); ?>')">
                        📦 Archive
                    </button>
                    <button type="button" class="umb-btn umb-btn-danger umb-btn-small" onclick="if(confirm('⚠️ Delete this wallet PERMANENTLY?\n\nThis cannot be undone!')) document.getElementById('delete-wallet-form').submit();">
                        🗑️ Delete
                    </button>
                </div>
            </div>

            <div class="umb-card-body">
                <!-- Quick Stats -->
                <div class="umb-stats-grid umb-mb-lg">
                    <div class="umb-stat-card">
                        <div class="umb-stat-label">Network</div>
                        <div class="umb-stat-value"><?php echo strtoupper($existing_wallet->network); ?></div>
                    </div>
                    <div class="umb-stat-card">
                        <div class="umb-stat-label">Status</div>
                        <div class="umb-stat-value" style="color: var(--umb-success);">Active</div>
                    </div>
                </div>

                <!-- Payment Address - Featured -->
                <div class="umb-mb-lg" style="background: linear-gradient(135deg, rgba(0, 230, 255, 0.08) 0%, rgba(255, 0, 230, 0.08) 100%); border: 2px solid var(--umb-glass-border); border-radius: var(--umb-radius-lg); padding: var(--umb-space-lg);">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--umb-cyan); text-transform: uppercase; letter-spacing: 1px; margin-bottom: var(--umb-space-md); display: flex; align-items: center; gap: var(--umb-space-sm);">
                        <span style="font-size: 20px; color: var(--umb-cyan);">▭</span>
                        Payment Address
                    </label>
                    <div style="background: rgba(0, 0, 0, 0.4); border: 1px solid var(--umb-cyan); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); font-family: 'Courier New', monospace; font-size: 14px; color: var(--umb-cyan); word-break: break-all; position: relative; box-shadow: 0 0 16px rgba(0, 230, 255, 0.2);">
                        <div style="display: flex; align-items: center; gap: var(--umb-space-md);">
                            <div style="flex: 1;"><?php echo esc_html($existing_wallet->payment_address); ?></div>
                            <button type="button" class="umb-copy-btn" onclick="copyToClipboard('<?php echo esc_js($existing_wallet->payment_address); ?>', this)" style="flex-shrink: 0;">
                                📋 Copy
                            </button>
                        </div>
                    </div>
                    <div style="margin-top: var(--umb-space-sm); font-size: 12px; color: var(--umb-text-secondary);">
                        <a href="<?php echo esc_url($network === 'mainnet' ? 'https://cardanoscan.io/address/' . $existing_wallet->payment_address : 'https://preprod.cardanoscan.io/address/' . $existing_wallet->payment_address); ?>" target="_blank" style="color: var(--umb-cyan); text-decoration: none; display: inline-flex; align-items: center; gap: var(--umb-space-xs);">
                            🔍 View on CardanoScan <span style="opacity: 0.5;">↗</span>
                        </a>
                    </div>
                </div>

                <!-- Collapsible Sections -->
                <div class="umb-collapsible">
                    <div class="umb-collapsible-header" onclick="toggleCollapsible(this)">
                        <h3 class="umb-collapsible-title">
                            <span style="color: var(--umb-cyan);">◈</span> Payment KeyHash
                        </h3>
                        <span class="umb-collapsible-icon">▼</span>
                    </div>
                    <div class="umb-collapsible-content">
                        <div class="umb-collapsible-body">
                            <div class="umb-data-display">
                                <?php echo esc_html($existing_wallet->payment_keyhash); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($extended_key_hex): ?>
                <div class="umb-collapsible">
                    <div class="umb-collapsible-header" onclick="toggleCollapsible(this)">
                        <h3 class="umb-collapsible-title">
                            <span style="color: var(--umb-cyan);">⚿</span> Extended Signing Key
                        </h3>
                        <span class="umb-collapsible-icon">▼</span>
                    </div>
                    <div class="umb-collapsible-content">
                        <div class="umb-collapsible-body">
                            <div style="margin-bottom: var(--umb-space-md); padding: var(--umb-space-md); background: rgba(255, 170, 0, 0.1); border-left: 3px solid var(--umb-warning); border-radius: var(--umb-radius-sm);">
                                <strong style="color: var(--umb-warning);">⚠️ Security Warning</strong>
                                <p style="margin: var(--umb-space-xs) 0 0 0; font-size: 13px; color: var(--umb-text-secondary);">
                                    This key can sign transactions. Keep it secret!
                                </p>
                            </div>
                            <div class="umb-data-display" id="extended-key-display" style="filter: blur(8px); user-select: none; transition: filter var(--umb-transition-base);">
                                <?php echo esc_html($extended_key_hex); ?>
                            </div>
                            <button type="button" id="toggle-extended-key" class="umb-btn umb-btn-secondary umb-btn-small umb-mt-sm" onclick="toggleExtendedKey()">
                                👁️ Reveal Extended Key
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Wallet Balance & Contents -->
        <div class="umb-card">
            <div class="umb-card-header">
                <h2 class="umb-card-title"><span style="color: var(--umb-cyan);">◎</span> Wallet Balance & Contents</h2>
                <div class="umb-flex-gap">
                    <button type="button" id="refresh-balance-btn" class="umb-btn umb-btn-secondary umb-btn-small" onclick="refreshWalletBalance()">
                        🔄 Refresh
                    </button>
                    <a href="<?php echo esc_url($network === 'mainnet' ? 'https://cardanoscan.io/address/' . $existing_wallet->payment_address : 'https://preprod.cardanoscan.io/address/' . $existing_wallet->payment_address); ?>" target="_blank" class="umb-btn umb-btn-secondary umb-btn-small">
                        🔍 Explorer
                    </a>
                </div>
            </div>

            <div class="umb-card-body">
                <div id="wallet-balance-content" style="min-height: 100px;">
                    <div class="umb-loading">
                        <div class="umb-spinner"></div>
                    </div>
                </div>
            </div>

            <form id="delete-wallet-form" method="post" style="display: none;">
                <?php wp_nonce_field('umb_delete_wallet', 'umb_wallet_nonce'); ?>
                <input type="hidden" name="umb_wallet_action" value="delete">
                <input type="hidden" name="wallet_id" value="<?php echo esc_attr($existing_wallet->id); ?>">
            </form>
        </div>

    <?php else: ?>
        <!-- Generate New Wallet Form -->
        <div class="umb-card" style="max-width: 700px;">
            <div class="umb-card-header">
                <h2 class="umb-card-title">
                    <span style="color: var(--umb-cyan);">⚿</span> Generate Signing Wallet
                    <span class="umb-badge umb-badge-<?php echo $network === 'mainnet' ? 'mainnet' : 'preprod'; ?>">
                        <span class="umb-badge-dot"></span>
                        <?php echo strtoupper($network); ?>
                    </span>
                </h2>
            </div>

            <div class="umb-card-body">
                <!-- Mode Toggle -->
                <div style="margin-bottom: var(--umb-space-xl);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--umb-space-md);">
                        <button type="button" class="wallet-mode-btn active" data-mode="generate" onclick="switchWalletMode('generate')" style="padding: var(--umb-space-md); background: rgba(0, 230, 255, 0.1); border: 2px solid var(--umb-cyan); border-radius: var(--umb-radius-md); color: var(--umb-text-primary); font-weight: 600; cursor: pointer; transition: all var(--umb-transition-base);">
                            <span style="color: var(--umb-cyan);">◉</span> Generate New Wallet
                        </button>
                        <button type="button" class="wallet-mode-btn" data-mode="import" onclick="switchWalletMode('import')" style="padding: var(--umb-space-md); background: rgba(0, 0, 0, 0.2); border: 2px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); color: var(--umb-text-secondary); font-weight: 600; cursor: pointer; transition: all var(--umb-transition-base);">
                            <span style="color: var(--umb-cyan);">↓</span> Import from Mnemonic
                        </button>
                    </div>
                </div>

                <!-- Generate Mode -->
                <div id="generate-mode-content">
                    <div style="background: rgba(0, 230, 255, 0.05); border-left: 3px solid var(--umb-info); border-radius: var(--umb-radius-sm); padding: var(--umb-space-md); margin-bottom: var(--umb-space-xl);">
                        <div style="color: var(--umb-info); font-size: 13px; font-weight: 600; margin-bottom: var(--umb-space-xs);">
                            ℹ️ What you're creating
                        </div>
                        <div style="color: var(--umb-text-secondary); font-size: 13px; line-height: 1.6;">
                            A secure HD wallet for the <strong style="color: var(--umb-text-primary);"><?php echo esc_html(strtoupper($network)); ?></strong> network. This wallet will be used to cryptographically sign your blog posts and link them to your Cardano identity.
                        </div>
                    </div>

                    <form method="post" id="generate-wallet-form">
                        <?php wp_nonce_field('umb_generate_wallet', 'umb_wallet_nonce'); ?>
                        <input type="hidden" name="umb_wallet_action" value="generate">

                        <div style="margin-bottom: var(--umb-space-xl);">
                            <label for="wallet_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-sm);">
                                Wallet Name
                            </label>
                            <input type="text" id="wallet_name" name="wallet_name" value="<?php echo esc_attr(ucfirst($network) . ' Blog Signing Wallet'); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px; font-family: inherit; transition: all var(--umb-transition-base);" onfocus="this.style.borderColor='var(--umb-cyan)'; this.style.boxShadow='0 0 8px rgba(0, 230, 255, 0.2)';" onblur="this.style.borderColor='var(--umb-glass-border)'; this.style.boxShadow='none';">
                            <div style="font-size: 12px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                                Friendly name for your reference (e.g., "Preprod Blog Signing Wallet")
                            </div>
                        </div>

                        <div style="background: rgba(255, 170, 0, 0.1); border: 1px solid var(--umb-warning); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); margin-bottom: var(--umb-space-xl);">
                            <div style="color: var(--umb-warning); font-size: 13px; font-weight: 600; margin-bottom: var(--umb-space-sm); display: flex; align-items: center; gap: var(--umb-space-sm);">
                                ⚠️ Important Security Information
                            </div>
                            <ul style="margin: 0; padding-left: var(--umb-space-lg); color: var(--umb-text-secondary); font-size: 12px; line-height: 1.6;">
                                <li style="margin-bottom: var(--umb-space-xs);">You'll receive a <strong style="color: var(--umb-text-primary);">24-word recovery phrase</strong> shown only once</li>
                                <li style="margin-bottom: var(--umb-space-xs);">Write it down and store it securely offline</li>
                                <li style="margin-bottom: var(--umb-space-xs);">The wallet will be encrypted in your database with your WordPress secret keys</li>
                                <li>Anyone with the recovery phrase can access your wallet</li>
                            </ul>
                        </div>

                        <div style="text-align: center;">
                            <button type="submit" class="umb-btn umb-btn-primary umb-btn-large" id="generate-btn" style="min-width: 280px;">
                                <span style="color: var(--umb-cyan);">◉</span> Generate Secure Wallet
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Import Mode -->
                <div id="import-mode-content" style="display: none;">
                    <div style="background: rgba(0, 230, 255, 0.05); border-left: 3px solid var(--umb-info); border-radius: var(--umb-radius-sm); padding: var(--umb-space-md); margin-bottom: var(--umb-space-xl);">
                        <div style="color: var(--umb-info); font-size: 13px; font-weight: 600; margin-bottom: var(--umb-space-xs);">
                            ℹ️ Import Existing Wallet
                        </div>
                        <div style="color: var(--umb-text-secondary); font-size: 13px; line-height: 1.6;">
                            Import a wallet you already have by entering your 24-word recovery phrase. This is perfect for using your existing mainnet wallet or moving between environments.
                        </div>
                    </div>

                    <form method="post" id="import-wallet-form">
                        <?php wp_nonce_field('umb_generate_wallet', 'umb_wallet_nonce'); ?>
                        <input type="hidden" name="umb_wallet_action" value="import">

                        <div style="margin-bottom: var(--umb-space-xl);">
                            <label for="import_wallet_name" style="display: block; font-size: 13px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-sm);">
                                Wallet Name
                            </label>
                            <input type="text" id="import_wallet_name" name="wallet_name" value="<?php echo esc_attr(ucfirst($network) . ' Imported Wallet'); ?>" style="width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); color: var(--umb-text-primary); font-size: 14px; font-family: inherit; transition: all var(--umb-transition-base);" onfocus="this.style.borderColor='var(--umb-cyan)'; this.style.boxShadow='0 0 8px rgba(0, 230, 255, 0.2)';" onblur="this.style.borderColor='var(--umb-glass-border)'; this.style.boxShadow='none';">
                            <div style="font-size: 12px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                                Friendly name for your reference
                            </div>
                        </div>

                        <div style="margin-bottom: var(--umb-space-xl);">
                            <label for="mnemonic" style="display: block; font-size: 13px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-sm);">
                                24-Word Recovery Phrase
                            </label>
                            <textarea id="mnemonic" name="mnemonic" rows="4" required style="width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); color: var(--umb-text-primary); font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.8; resize: vertical; transition: all var(--umb-transition-base);" placeholder="Enter your 24 words separated by spaces..." onfocus="this.style.borderColor='var(--umb-cyan)'; this.style.boxShadow='0 0 8px rgba(0, 230, 255, 0.2)';" onblur="this.style.borderColor='var(--umb-glass-border)'; this.style.boxShadow='none';"></textarea>
                            <div style="font-size: 12px; color: var(--umb-text-muted); margin-top: var(--umb-space-xs);">
                                Paste your 24-word mnemonic phrase (space-separated)
                            </div>
                        </div>

                        <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--umb-danger); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); margin-bottom: var(--umb-space-xl);">
                            <div style="color: var(--umb-danger); font-size: 13px; font-weight: 600; margin-bottom: var(--umb-space-sm); display: flex; align-items: center; gap: var(--umb-space-sm);">
                                🔐 Security Warning
                            </div>
                            <ul style="margin: 0; padding-left: var(--umb-space-lg); color: var(--umb-text-secondary); font-size: 12px; line-height: 1.6;">
                                <li style="margin-bottom: var(--umb-space-xs);">Make sure you're on a secure, private connection</li>
                                <li style="margin-bottom: var(--umb-space-xs);">Your mnemonic will be encrypted and stored in the database</li>
                                <li style="margin-bottom: var(--umb-space-xs);">Never share your recovery phrase with anyone</li>
                                <li>This will import the wallet for the <strong style="color: var(--umb-text-primary);"><?php echo esc_html(strtoupper($network)); ?></strong> network</li>
                            </ul>
                        </div>

                        <div style="text-align: center;">
                            <button type="submit" class="umb-btn umb-btn-primary umb-btn-large" id="import-btn" style="min-width: 280px;">
                                <span style="color: var(--umb-cyan);">↓</span> Import Wallet
                            </button>
                        </div>
                    </form>
                </div>

                <script>
                // Wallet mode switcher
                function switchWalletMode(mode) {
                    // Update button states
                    document.querySelectorAll('.wallet-mode-btn').forEach(btn => {
                        if (btn.dataset.mode === mode) {
                            btn.classList.add('active');
                            btn.style.background = 'rgba(0, 230, 255, 0.1)';
                            btn.style.borderColor = 'var(--umb-cyan)';
                            btn.style.color = 'var(--umb-text-primary)';
                        } else {
                            btn.classList.remove('active');
                            btn.style.background = 'rgba(0, 0, 0, 0.2)';
                            btn.style.borderColor = 'var(--umb-glass-border)';
                            btn.style.color = 'var(--umb-text-secondary)';
                        }
                    });

                    // Toggle content
                    document.getElementById('generate-mode-content').style.display = mode === 'generate' ? 'block' : 'none';
                    document.getElementById('import-mode-content').style.display = mode === 'import' ? 'block' : 'none';
                }

                // Generate form handler
                document.getElementById('generate-wallet-form').addEventListener('submit', function() {
                    document.getElementById('generate-btn').disabled = true;
                    document.getElementById('generate-btn').innerHTML = '<span style="color: var(--umb-cyan);">⏳</span> Generating wallet...';
                    document.getElementById('generate-btn').style.opacity = '0.6';
                    document.getElementById('generate-btn').style.cursor = 'not-allowed';
                });

                // Import form handler
                document.getElementById('import-wallet-form').addEventListener('submit', function(e) {
                    const mnemonic = document.getElementById('mnemonic').value.trim();
                    const words = mnemonic.split(/\s+/);

                    if (words.length !== 24) {
                        e.preventDefault();
                        alert('Please enter exactly 24 words for your recovery phrase.');
                        return false;
                    }

                    document.getElementById('import-btn').disabled = true;
                    document.getElementById('import-btn').innerHTML = '<span style="color: var(--umb-cyan);">⏳</span> Importing wallet...';
                    document.getElementById('import-btn').style.opacity = '0.6';
                    document.getElementById('import-btn').style.cursor = 'not-allowed';
                });
                </script>
            </div>
        </div>
    <?php endif; ?>

    <!-- Archived Wallets -->
    <?php if ($archived_count > 0): ?>
        <div class="umb-card">
            <div class="umb-card-header">
                <h2 class="umb-card-title">
                    <span style="color: var(--umb-cyan);">▤</span> Archived Wallets
                    <span class="umb-badge umb-badge-archived">
                        <?php echo $archived_count; ?>
                    </span>
                </h2>
            </div>

            <div class="umb-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: var(--umb-space-md);">
                    <?php foreach ($archived_wallets as $wallet): ?>
                        <div class="umb-card" style="margin-bottom: 0; transition: all var(--umb-transition-base);" onmouseover="this.style.borderColor='var(--umb-cyan)'; this.style.boxShadow='0 0 16px rgba(0, 230, 255, 0.3)';" onmouseout="this.style.borderColor='var(--umb-glass-border)'; this.style.boxShadow='var(--umb-shadow-md)';">
                            <div style="margin-bottom: var(--umb-space-md);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--umb-space-sm);">
                                    <h3 style="font-size: 16px; font-weight: 600; color: var(--umb-text-primary); margin: 0; display: flex; align-items: center; gap: var(--umb-space-sm);">
                                        <?php echo esc_html($wallet->wallet_name); ?>
                                    </h3>
                                    <span class="umb-badge umb-badge-archived" style="font-size: 11px;">
                                        Archived
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: var(--umb-text-muted); margin-bottom: var(--umb-space-md);">
                                    Archived <?php echo human_time_diff(strtotime($wallet->archived_at), current_time('timestamp')); ?> ago
                                </div>
                            </div>

                            <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-sm); padding: var(--umb-space-sm); margin-bottom: var(--umb-space-md);">
                                <div style="font-size: 11px; color: var(--umb-text-muted); margin-bottom: var(--umb-space-xs); text-transform: uppercase; letter-spacing: 0.5px;">Address</div>
                                <div style="font-family: 'Courier New', monospace; font-size: 11px; color: var(--umb-text-secondary); word-break: break-all;">
                                    <?php echo esc_html(substr($wallet->payment_address, 0, 35)); ?>...
                                </div>
                            </div>

                            <button type="button" class="umb-btn umb-btn-primary umb-btn-small" style="width: 100%;" onclick="unarchiveWallet(<?php echo esc_js($wallet->id); ?>, '<?php echo esc_js($wallet->wallet_name); ?>')">
                                ♻️ Activate Wallet
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
