<?php
/**
 * Migration: Add Cardano signature fields to blog posts
 * Run this once to add blockchain signing capabilities
 */

function umbrella_blog_add_signature_fields() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'umbrella_blog_posts';

    // Check if columns already exist
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    $existing_columns = array_column($columns, 'Field');

    $migrations_needed = [];

    if (!in_array('signature_tx_hash', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signature_tx_hash VARCHAR(64) DEFAULT NULL AFTER reading_time";
    }

    if (!in_array('signature_wallet_address', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signature_wallet_address VARCHAR(255) DEFAULT NULL AFTER signature_tx_hash";
    }

    if (!in_array('signature_handle', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signature_handle VARCHAR(100) DEFAULT NULL AFTER signature_wallet_address";
    }

    if (!in_array('signature_handle_image', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signature_handle_image TEXT DEFAULT NULL AFTER signature_handle";
    }

    if (!in_array('signed_at', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signed_at DATETIME DEFAULT NULL AFTER signature_handle_image";
    }

    if (!in_array('signature_metadata', $existing_columns)) {
        $migrations_needed[] = "ADD COLUMN signature_metadata TEXT DEFAULT NULL AFTER signed_at";
    }

    // Execute migrations
    if (!empty($migrations_needed)) {
        $sql = "ALTER TABLE {$table_name} " . implode(', ', $migrations_needed);
        $result = $wpdb->query($sql);

        if ($result === false) {
            return [
                'success' => false,
                'error' => $wpdb->last_error
            ];
        }

        // Add indexes
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_signature_tx_hash (signature_tx_hash)");
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_signature_handle (signature_handle)");

        return [
            'success' => true,
            'columns_added' => count($migrations_needed),
            'message' => 'Signature fields added successfully!'
        ];
    }

    return [
        'success' => true,
        'columns_added' => 0,
        'message' => 'Signature fields already exist.'
    ];
}

// Run migration if accessed directly (for manual execution)
if (defined('ABSPATH') && isset($_GET['run_migration']) && $_GET['run_migration'] === 'signature_fields') {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $result = umbrella_blog_add_signature_fields();

    if ($result['success']) {
        echo '<div style="padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">';
        echo '<strong>✅ Success!</strong><br>';
        echo esc_html($result['message']);
        if ($result['columns_added'] > 0) {
            echo '<br>Columns added: ' . $result['columns_added'];
        }
        echo '</div>';
    } else {
        echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">';
        echo '<strong>❌ Error!</strong><br>';
        echo esc_html($result['error']);
        echo '</div>';
    }

    echo '<br><a href="' . admin_url('admin.php?page=umbrella-blog') . '">← Back to Blog</a>';
}
