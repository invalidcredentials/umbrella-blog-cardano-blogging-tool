<?php
/**
 * Wallet Model
 * Database operations for wallet management
 */

class UmbrellaBlog_WalletModel {

    /**
     * Get active wallet for network
     *
     * @param string $network Network (preprod/mainnet)
     * @return object|null Wallet data or null
     */
    public static function getActiveWallet($network) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        $wallet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE network = %s AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            $network
        ));

        return $wallet;
    }

    /**
     * Get all archived wallets for network
     *
     * @param string $network Network (preprod/mainnet)
     * @return array Archived wallets
     */
    public static function getArchivedWallets($network) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        $wallets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE network = %s AND status = 'archived' ORDER BY archived_at DESC",
            $network
        ));

        return $wallets ?: [];
    }

    /**
     * Count archived wallets
     *
     * @param string $network Network
     * @return int Count
     */
    public static function countArchivedWallets($network) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE network = %s AND status = 'archived'",
            $network
        ));
    }

    /**
     * Create new wallet
     *
     * @param string $wallet_name Friendly name
     * @param string $network Network (preprod/mainnet)
     * @param array $wallet_data Wallet data (address, keyhash, encrypted keys)
     * @return int|false Wallet ID or false
     */
    public static function createWallet($wallet_name, $network, $wallet_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        // Archive any existing active wallet for this network
        $wpdb->update(
            $table,
            [
                'status' => 'archived',
                'archived_at' => current_time('mysql')
            ],
            [
                'network' => $network,
                'status' => 'active'
            ]
        );

        // Insert new active wallet
        $result = $wpdb->insert(
            $table,
            [
                'wallet_name' => $wallet_name,
                'network' => $network,
                'payment_address' => $wallet_data['payment_address'],
                'payment_keyhash' => $wallet_data['payment_keyhash'],
                'mnemonic_encrypted' => $wallet_data['mnemonic_encrypted'],
                'skey_encrypted' => $wallet_data['skey_encrypted'],
                'status' => 'active',
                'created_at' => current_time('mysql')
            ]
        );

        if ($result === false) {
            error_log('WalletModel: Failed to create wallet - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Archive wallet
     *
     * @param int $wallet_id Wallet ID
     * @return bool Success
     */
    public static function archiveWallet($wallet_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        $result = $wpdb->update(
            $table,
            [
                'status' => 'archived',
                'archived_at' => current_time('mysql')
            ],
            ['id' => $wallet_id]
        );

        return $result !== false;
    }

    /**
     * Unarchive wallet (make it active)
     *
     * @param int $wallet_id Wallet ID
     * @return bool Success
     */
    public static function unarchiveWallet($wallet_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        // Get the wallet to check its network
        $wallet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $wallet_id
        ));

        if (!$wallet) {
            return false;
        }

        // Archive any currently active wallet for this network
        $wpdb->update(
            $table,
            [
                'status' => 'archived',
                'archived_at' => current_time('mysql')
            ],
            [
                'network' => $wallet->network,
                'status' => 'active'
            ]
        );

        // Activate this wallet
        $result = $wpdb->update(
            $table,
            [
                'status' => 'active',
                'archived_at' => null
            ],
            ['id' => $wallet_id]
        );

        return $result !== false;
    }

    /**
     * Delete wallet permanently
     *
     * @param int $wallet_id Wallet ID
     * @return bool Success
     */
    public static function deleteWallet($wallet_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        $result = $wpdb->delete($table, ['id' => $wallet_id]);

        return $result !== false;
    }

    /**
     * Get wallet by ID
     *
     * @param int $wallet_id Wallet ID
     * @return object|null Wallet or null
     */
    public static function getWalletById($wallet_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'umbrella_blog_wallets';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $wallet_id
        ));
    }
}
