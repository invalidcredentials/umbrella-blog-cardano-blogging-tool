<?php
/**
 * Migration: Add Wallet Table
 * Creates table for storing encrypted wallet data
 */

global $wpdb;
$table_name = $wpdb->prefix . 'umbrella_blog_wallets';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_name VARCHAR(100) NOT NULL,
    network VARCHAR(20) NOT NULL,
    payment_address TEXT NOT NULL,
    payment_keyhash VARCHAR(56) NOT NULL,
    mnemonic_encrypted TEXT NOT NULL,
    skey_encrypted TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    archived_at DATETIME NULL,
    INDEX idx_network_status (network, status),
    INDEX idx_status (status)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

error_log('Umbrella Blog: Wallet table created/updated');

// Check if migration succeeded
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
if ($table_exists) {
    error_log('Umbrella Blog: Wallet table verified');
} else {
    error_log('Umbrella Blog: ERROR - Wallet table creation failed!');
}
