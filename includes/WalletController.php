<?php
/**
 * Wallet Controller
 * Handles wallet generation, archiving, and management
 */

class UmbrellaBlog_WalletController {

    /**
     * Register hooks
     */
    public static function register() {
        // Handle form submissions
        add_action('admin_init', [self::class, 'handleFormSubmissions']);

        // AJAX handlers
        add_action('wp_ajax_umb_archive_wallet', [self::class, 'ajaxArchiveWallet']);
        add_action('wp_ajax_umb_unarchive_wallet', [self::class, 'ajaxUnarchiveWallet']);
        add_action('wp_ajax_umb_get_wallet_balance', [self::class, 'ajaxGetWalletBalance']);
    }

    /**
     * Handle form submissions
     */
    public static function handleFormSubmissions() {
        if (!isset($_POST['umb_wallet_action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $action = $_POST['umb_wallet_action'];

        // Generate wallet
        if ($action === 'generate') {
            check_admin_referer('umb_generate_wallet', 'umb_wallet_nonce');
            self::generateWallet();
            return;
        }

        // Import wallet from mnemonic
        if ($action === 'import') {
            check_admin_referer('umb_generate_wallet', 'umb_wallet_nonce');
            self::importWallet();
            return;
        }

        // Delete wallet
        if ($action === 'delete') {
            check_admin_referer('umb_delete_wallet', 'umb_wallet_nonce');
            self::deleteWallet();
            return;
        }
    }

    /**
     * Generate new wallet
     */
    private static function generateWallet() {
        try {
            $wallet_name = sanitize_text_field($_POST['wallet_name'] ?? 'Blog Signing Wallet');
            $network = get_option('umbrella_blog_default_network', 'preprod');

            error_log('=== UMBRELLA BLOG WALLET GENERATION START ===');
            error_log('Wallet name: ' . $wallet_name);
            error_log('Network: ' . $network);

            // Load vendor classes (CardanoWalletPHP MUST be loaded before CardanoCLI)
            error_log('Loading vendor classes...');

            $vendor_path = plugin_dir_path(__FILE__) . 'vendor/';

            if (!file_exists($vendor_path . 'CardanoWalletPHP.php')) {
                throw new Exception('CardanoWalletPHP.php not found');
            }
            require_once $vendor_path . 'CardanoWalletPHP.php';
            error_log('✓ CardanoWalletPHP loaded');

            if (!file_exists($vendor_path . 'bip39-wordlist.php')) {
                throw new Exception('bip39-wordlist.php not found');
            }
            require_once $vendor_path . 'bip39-wordlist.php';
            error_log('✓ bip39-wordlist loaded');

            if (!file_exists($vendor_path . 'Ed25519Compat.php')) {
                throw new Exception('Ed25519Compat.php not found');
            }
            require_once $vendor_path . 'Ed25519Compat.php';
            error_log('✓ Ed25519Compat loaded');

            if (!file_exists($vendor_path . 'Ed25519Pure.php')) {
                throw new Exception('Ed25519Pure.php not found');
            }
            require_once $vendor_path . 'Ed25519Pure.php';
            error_log('✓ Ed25519Pure loaded');

            if (!file_exists($vendor_path . 'CardanoCLI.php')) {
                throw new Exception('CardanoCLI.php not found');
            }
            require_once $vendor_path . 'CardanoCLI.php';
            error_log('✓ CardanoCLI loaded');

            if (!file_exists($vendor_path . 'UmbrellaBlog_EncryptionHelper.php')) {
                throw new Exception('UmbrellaBlog_EncryptionHelper.php not found');
            }
            require_once $vendor_path . 'UmbrellaBlog_EncryptionHelper.php';
            error_log('✓ EncryptionHelper loaded');

            error_log('All vendor classes loaded successfully');
            error_log('Calling UmbrellaBlog_CardanoCLI::generateWallet()...');

            // Generate wallet using CardanoCLI
            $result = UmbrellaBlog_CardanoCLI::generateWallet($network, true);

            error_log('CardanoCLI returned: ' . print_r($result, true));

        } catch (Exception $e) {
            error_log('=== WALLET GENERATION EXCEPTION ===');
            error_log('Error: ' . $e->getMessage());
            error_log('File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());

            add_settings_error(
                'umbrella_blog_wallet',
                'generation_exception',
                'Wallet generation crashed: ' . $e->getMessage(),
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        if (!$result || !isset($result['success']) || !$result['success']) {
            $error_msg = isset($result['error']) ? $result['error'] : 'Unknown wallet generation error';
            error_log('UmbrellaBlog: Wallet generation failed: ' . $error_msg);

            add_settings_error(
                'umbrella_blog_wallet',
                'generation_failed',
                'Failed to generate wallet: ' . $error_msg,
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        error_log('UmbrellaBlog: Wallet generated successfully');

        // Validate required fields
        if (!isset($result['mnemonic']) || !isset($result['payment_skey_extended'])) {
            error_log('UmbrellaBlog: Missing required wallet data');
            add_settings_error(
                'umbrella_blog_wallet',
                'generation_failed',
                'Invalid wallet data - missing required fields',
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        // Extract payment address from nested structure
        $payment_address = $result['addresses']['payment_address'] ?? null;
        if (!$payment_address) {
            error_log('UmbrellaBlog: Missing payment address in result');
            add_settings_error(
                'umbrella_blog_wallet',
                'generation_failed',
                'Invalid wallet data - missing payment address',
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        error_log('Payment address: ' . $payment_address);
        error_log('Payment keyhash: ' . $result['payment_keyhash']);

        // Encrypt sensitive data
        $mnemonic_encrypted = UmbrellaBlog_EncryptionHelper::encrypt($result['mnemonic']);
        $skey_encrypted = UmbrellaBlog_EncryptionHelper::encrypt($result['payment_skey_extended']);

        if (empty($mnemonic_encrypted) || empty($skey_encrypted)) {
            error_log('UmbrellaBlog: Encryption failed');
            add_settings_error(
                'umbrella_blog_wallet',
                'encryption_failed',
                'Wallet encryption failed',
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        // Save to database
        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

        $wallet_data = [
            'payment_address' => $payment_address,
            'payment_keyhash' => $result['payment_keyhash'],
            'mnemonic_encrypted' => $mnemonic_encrypted,
            'skey_encrypted' => $skey_encrypted
        ];

        $wallet_id = UmbrellaBlog_WalletModel::createWallet($wallet_name, $network, $wallet_data);

        if (!$wallet_id) {
            error_log('UmbrellaBlog: Failed to save wallet to database');
            add_settings_error(
                'umbrella_blog_wallet',
                'save_failed',
                'Failed to save wallet to database',
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }

        // Store mnemonic in transient for ONE-TIME display
        set_transient('umb_wallet_mnemonic_' . get_current_user_id(), $result['mnemonic'], 300); // 5 min

        error_log('UmbrellaBlog: Wallet saved successfully (ID: ' . $wallet_id . ')');

        // Redirect with success flag
        wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet&created=1'));
        exit;
    }

    /**
     * Import wallet from existing mnemonic
     */
    private static function importWallet() {
        try {
            $wallet_name = sanitize_text_field($_POST['wallet_name'] ?? 'Imported Wallet');
            $mnemonic = sanitize_textarea_field($_POST['mnemonic'] ?? '');
            $network = get_option('umbrella_blog_default_network', 'preprod');

            error_log('=== UMBRELLA BLOG WALLET IMPORT START ===');
            error_log('Wallet name: ' . $wallet_name);
            error_log('Network: ' . $network);

            // Validate mnemonic
            $words = preg_split('/\s+/', trim($mnemonic));
            if (count($words) !== 24) {
                throw new Exception('Mnemonic must be exactly 24 words. Got ' . count($words) . ' words.');
            }

            error_log('Mnemonic word count: ' . count($words) . ' ✓');

            // Load vendor classes (same as generateWallet)
            error_log('Loading vendor classes...');

            $vendor_path = plugin_dir_path(__FILE__) . 'vendor/';

            if (!file_exists($vendor_path . 'CardanoWalletPHP.php')) {
                throw new Exception('CardanoWalletPHP.php not found');
            }
            require_once $vendor_path . 'CardanoWalletPHP.php';
            error_log('✓ CardanoWalletPHP loaded');

            if (!file_exists($vendor_path . 'bip39-wordlist.php')) {
                throw new Exception('bip39-wordlist.php not found');
            }
            require_once $vendor_path . 'bip39-wordlist.php';
            error_log('✓ bip39-wordlist loaded');

            if (!file_exists($vendor_path . 'Ed25519Compat.php')) {
                throw new Exception('Ed25519Compat.php not found');
            }
            require_once $vendor_path . 'Ed25519Compat.php';
            error_log('✓ Ed25519Compat loaded');

            if (!file_exists($vendor_path . 'Ed25519Pure.php')) {
                throw new Exception('Ed25519Pure.php not found');
            }
            require_once $vendor_path . 'Ed25519Pure.php';
            error_log('✓ Ed25519Pure loaded');

            if (!file_exists($vendor_path . 'UmbrellaBlog_EncryptionHelper.php')) {
                throw new Exception('UmbrellaBlog_EncryptionHelper.php not found');
            }
            require_once $vendor_path . 'UmbrellaBlog_EncryptionHelper.php';
            error_log('✓ EncryptionHelper loaded');

            error_log('All vendor classes loaded successfully');
            error_log('Deriving wallet from mnemonic...');

            // Derive wallet from mnemonic using CardanoWalletPHP
            $result = CardanoWalletPHP::fromMnemonic($mnemonic, '', $network);

            if (!$result || !isset($result['success']) || !$result['success']) {
                $error_msg = isset($result['error']) ? $result['error'] : 'Unknown wallet derivation error';
                error_log('UmbrellaBlog: Wallet derivation failed: ' . $error_msg);
                throw new Exception('Failed to derive wallet from mnemonic: ' . $error_msg);
            }

            error_log('UmbrellaBlog: Wallet derived successfully');

            // Validate required fields (CardanoWalletPHP::fromMnemonic returns similar structure)
            if (!isset($result['payment_skey_extended'])) {
                error_log('UmbrellaBlog: Missing required wallet data');
                throw new Exception('Invalid wallet data - missing required fields');
            }

            // Extract payment address from nested structure
            $payment_address = $result['addresses']['payment_address'] ?? null;
            if (!$payment_address) {
                error_log('UmbrellaBlog: Missing payment address in result');
                throw new Exception('Invalid wallet data - missing payment address');
            }

            error_log('Payment address: ' . $payment_address);
            error_log('Payment keyhash: ' . $result['payment_keyhash']);

            // Encrypt sensitive data
            $mnemonic_encrypted = UmbrellaBlog_EncryptionHelper::encrypt($mnemonic);
            $skey_encrypted = UmbrellaBlog_EncryptionHelper::encrypt($result['payment_skey_extended']);

            if (empty($mnemonic_encrypted) || empty($skey_encrypted)) {
                error_log('UmbrellaBlog: Encryption failed');
                throw new Exception('Wallet encryption failed');
            }

            // Save to database
            require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

            $wallet_data = [
                'payment_address' => $payment_address,
                'payment_keyhash' => $result['payment_keyhash'],
                'mnemonic_encrypted' => $mnemonic_encrypted,
                'skey_encrypted' => $skey_encrypted
            ];

            $wallet_id = UmbrellaBlog_WalletModel::createWallet($wallet_name, $network, $wallet_data);

            if (!$wallet_id) {
                error_log('UmbrellaBlog: Failed to save wallet to database');
                throw new Exception('Failed to save wallet to database');
            }

            // Store mnemonic in transient for ONE-TIME display
            set_transient('umb_wallet_mnemonic_' . get_current_user_id(), $mnemonic, 300); // 5 min

            error_log('UmbrellaBlog: Wallet imported successfully (ID: ' . $wallet_id . ')');

            // Redirect with success flag
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet&created=1'));
            exit;

        } catch (Exception $e) {
            error_log('=== WALLET IMPORT EXCEPTION ===');
            error_log('Error: ' . $e->getMessage());
            error_log('File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());

            add_settings_error(
                'umbrella_blog_wallet',
                'import_exception',
                'Wallet import failed: ' . $e->getMessage(),
                'error'
            );
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
            exit;
        }
    }

    /**
     * Delete wallet
     */
    private static function deleteWallet() {
        $wallet_id = intval($_POST['wallet_id']);

        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

        $success = UmbrellaBlog_WalletModel::deleteWallet($wallet_id);

        if ($success) {
            add_settings_error(
                'umbrella_blog_wallet',
                'wallet_deleted',
                'Wallet deleted successfully',
                'success'
            );
        } else {
            add_settings_error(
                'umbrella_blog_wallet',
                'delete_failed',
                'Failed to delete wallet',
                'error'
            );
        }

        set_transient('settings_errors', get_settings_errors(), 30);
        wp_redirect(admin_url('admin.php?page=umbrella-blog-wallet'));
        exit;
    }

    /**
     * AJAX: Archive wallet
     */
    public static function ajaxArchiveWallet() {
        check_ajax_referer('umb_wallet_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $wallet_id = intval($_POST['wallet_id']);

        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

        $success = UmbrellaBlog_WalletModel::archiveWallet($wallet_id);

        if ($success) {
            wp_send_json_success('Wallet archived');
        } else {
            wp_send_json_error('Failed to archive wallet');
        }
    }

    /**
     * AJAX: Unarchive wallet
     */
    public static function ajaxUnarchiveWallet() {
        check_ajax_referer('umb_wallet_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $wallet_id = intval($_POST['wallet_id']);

        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

        $success = UmbrellaBlog_WalletModel::unarchiveWallet($wallet_id);

        if ($success) {
            wp_send_json_success('Wallet activated');
        } else {
            wp_send_json_error('Failed to activate wallet');
        }
    }

    /**
     * AJAX: Get wallet balance from Blockfrost
     */
    public static function ajaxGetWalletBalance() {
        check_ajax_referer('umb_wallet_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';
        require_once plugin_dir_path(__FILE__) . 'BlockfrostHelper.php';

        $network = get_option('umbrella_blog_default_network', 'preprod');
        $wallet = UmbrellaBlog_WalletModel::getActiveWallet($network);

        if (!$wallet) {
            wp_send_json_error('No active wallet found');
            return;
        }

        // Fetch wallet data from Blockfrost
        $address = $wallet->payment_address;
        $result = UmbrellaBlog_BlockfrostHelper::getAddressInfo($address, $network);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        // Format balance (lovelace to ADA)
        $lovelace = intval($result['amount'][0]['quantity'] ?? 0);
        $ada = $lovelace / 1000000;

        // Count assets
        $asset_count = count($result['amount']) - 1; // Subtract 1 for ADA

        // UTxO count
        $utxo_count = count($result['utxos'] ?? []);

        // Build cyberpunk-themed HTML output
        $html = '';

        // Big ADA Balance Card
        $html .= '<div style="background: linear-gradient(135deg, rgba(0, 230, 255, 0.1) 0%, rgba(255, 0, 230, 0.1) 100%); border: 2px solid var(--umb-cyan); border-radius: var(--umb-radius-lg); padding: var(--umb-space-xl); text-align: center; margin-bottom: var(--umb-space-lg); box-shadow: 0 0 24px rgba(0, 230, 255, 0.3);">';
        $html .= '<div style="font-size: 14px; color: var(--umb-text-secondary); text-transform: uppercase; letter-spacing: 2px; margin-bottom: var(--umb-space-sm);">Total Balance</div>';
        $html .= '<div style="font-size: 56px; font-weight: 700; background: linear-gradient(135deg, var(--umb-cyan) 0%, var(--umb-magenta) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.2; margin-bottom: var(--umb-space-xs);">₳ ' . number_format($ada, 6) . '</div>';
        $html .= '<div style="font-size: 13px; color: var(--umb-text-muted); font-family: monospace;">' . number_format($lovelace) . ' lovelace</div>';
        $html .= '</div>';

        // Stats Grid
        $html .= '<div class="umb-stats-grid" style="margin-bottom: var(--umb-space-lg);">';
        $html .= '<div class="umb-stat-card">';
        $html .= '<div class="umb-stat-label">UTxOs</div>';
        $html .= '<div class="umb-stat-value">' . $utxo_count . '</div>';
        $html .= '<div class="umb-stat-subtext">Unspent outputs</div>';
        $html .= '</div>';
        $html .= '<div class="umb-stat-card">';
        $html .= '<div class="umb-stat-label">Native Assets</div>';
        $html .= '<div class="umb-stat-value">' . $asset_count . '</div>';
        $html .= '<div class="umb-stat-subtext">Tokens & NFTs</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Native assets grid
        if ($asset_count > 0) {
            $html .= '<div style="margin-top: var(--umb-space-lg);">';
            $html .= '<h3 style="font-size: 16px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-md); display: flex; align-items: center; gap: var(--umb-space-sm);"><span>🎨</span> Native Assets</h3>';
            $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: var(--umb-space-md);">';

            foreach ($result['amount'] as $asset) {
                if ($asset['unit'] === 'lovelace') continue;

                $unit = $asset['unit'];
                $quantity = $asset['quantity'];

                // Try to decode asset name
                $policy_id = substr($unit, 0, 56);
                $asset_name_hex = substr($unit, 56);
                $asset_name = !empty($asset_name_hex) ? hex2bin($asset_name_hex) : 'Unknown Asset';

                // Asset card
                $html .= '<div style="background: var(--umb-glass-bg); border: 1px solid var(--umb-glass-border); border-radius: var(--umb-radius-md); padding: var(--umb-space-md); transition: all var(--umb-transition-base);" onmouseover="this.style.borderColor=\'var(--umb-cyan)\'; this.style.boxShadow=\'0 0 12px rgba(0,230,255,0.3)\';" onmouseout="this.style.borderColor=\'var(--umb-glass-border)\'; this.style.boxShadow=\'none\';">';
                $html .= '<div style="font-size: 16px; font-weight: 600; color: var(--umb-text-primary); margin-bottom: var(--umb-space-xs); word-break: break-word;">' . esc_html($asset_name) . '</div>';
                $html .= '<div style="font-size: 20px; font-weight: 700; color: var(--umb-cyan); margin-bottom: var(--umb-space-sm);">' . esc_html(number_format(floatval($quantity))) . '</div>';
                $html .= '<div style="font-size: 11px; color: var(--umb-text-muted); font-family: monospace; word-break: break-all;">Policy: ' . esc_html(substr($policy_id, 0, 20)) . '...</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= '<div style="text-align: center; padding: var(--umb-space-xl); color: var(--umb-text-secondary);">';
            $html .= '<div style="font-size: 48px; margin-bottom: var(--umb-space-md); opacity: 0.3;">📭</div>';
            $html .= '<div>No native assets found</div>';
            $html .= '</div>';
        }

        wp_send_json_success(['html' => $html]);
    }
}
