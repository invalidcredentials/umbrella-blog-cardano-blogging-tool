<?php
/**
 * Wallet Loader for Blog Signing
 * Simple wallet loading from database
 */

class UmbrellaBlog_WalletLoader {

    /**
     * Get the active signing wallet for current network
     *
     * @return array|WP_Error Wallet data or error
     */
    public static function getSigningWallet() {
        // Load dependencies
        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';
        require_once plugin_dir_path(__FILE__) . 'vendor/UmbrellaBlog_EncryptionHelper.php';
        require_once plugin_dir_path(__FILE__) . 'vendor/CardanoWalletPHP.php';

        // Get current network
        $network = get_option('umbrella_blog_default_network', 'preprod');

        // Get active wallet from database
        $wallet_db = UmbrellaBlog_WalletModel::getActiveWallet($network);

        if (!$wallet_db) {
            return new WP_Error(
                'no_wallet',
                'No active wallet found for network: ' . $network . '. Please create one in Wallet Manager.'
            );
        }

        // Decrypt sensitive data
        $mnemonic = UmbrellaBlog_EncryptionHelper::decrypt($wallet_db->mnemonic_encrypted);
        $skey_extended = UmbrellaBlog_EncryptionHelper::decrypt($wallet_db->skey_encrypted);

        if (!$mnemonic || !$skey_extended) {
            return new WP_Error('decrypt_failed', 'Failed to decrypt wallet credentials');
        }

        // Derive full wallet from mnemonic
        // fromMnemonic($mnemonic, $passphrase = '', $network = 'preprod')
        $wallet = CardanoWalletPHP::fromMnemonic($mnemonic, '', $network);

        if (!$wallet) {
            return new WP_Error('derive_failed', 'Failed to derive wallet from mnemonic');
        }

        // Return wallet data in expected format
        return [
            'wallet_id' => $wallet_db->id,
            'wallet_name' => $wallet_db->wallet_name,
            'network' => $network,
            'payment_address' => $wallet_db->payment_address,
            'payment_keyhash' => $wallet_db->payment_keyhash,
            'payment_skey_extended' => $skey_extended,
            'mnemonic' => $mnemonic, // Only for internal use, never exposed
            'account_xprv' => $wallet['account_xprv'] ?? null,
            'account_xpub' => $wallet['account_xpub'] ?? null
        ];
    }

    /**
     * Check if a wallet is configured
     *
     * @return bool True if wallet exists
     */
    public static function hasWallet() {
        require_once plugin_dir_path(__FILE__) . 'WalletModel.php';

        $network = get_option('umbrella_blog_default_network', 'preprod');
        $wallet = UmbrellaBlog_WalletModel::getActiveWallet($network);

        return !empty($wallet);
    }
}
