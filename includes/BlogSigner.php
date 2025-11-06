<?php
/**
 * Blog Post Signer
 *
 * Signs blog posts on the Cardano blockchain with CIP-20 metadata
 * Includes post title, slug, URL, and author handle
 */

class UmbrellaBlog_Signer {

    /**
     * Sign a blog post on the Cardano blockchain
     *
     * @param int $post_id Post ID
     * @return array|WP_Error Success data or error
     */
    public static function signBlogPost($post_id) {
        global $wpdb;

        // Get post data
        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}umbrella_blog_posts WHERE id = %d",
            $post_id
        ));

        if (!$post) {
            return new WP_Error('post_not_found', 'Blog post not found');
        }

        error_log('=== POST DATA DEBUG ===');
        error_log('Post ID: ' . $post->id);
        error_log('Post Title: ' . $post->title);
        error_log('Post Word Count: ' . $post->word_count);
        error_log('Post Word Count Type: ' . gettype($post->word_count));
        error_log('Post Word Count (int): ' . (int)$post->word_count);
        error_log('======================');

        // Check if already signed
        if (!empty($post->signature_tx_hash)) {
            return new WP_Error('already_signed', 'Post already signed. TX: ' . $post->signature_tx_hash);
        }

        // Load dependencies
        if (!class_exists('UmbrellaBlog_WalletLoader')) {
            require_once plugin_dir_path(__FILE__) . 'WalletLoader.php';
        }

        if (!class_exists('UmbrellaBlog_BlockfrostHelper')) {
            require_once plugin_dir_path(__FILE__) . 'BlockfrostHelper.php';
        }

        // 1. Load signing wallet
        $signer_wallet = UmbrellaBlog_WalletLoader::getSigningWallet();

        if (is_wp_error($signer_wallet)) {
            return $signer_wallet;
        }

        error_log('=== BLOG SIGNER: Starting signature for post ' . $post_id . ' ===');
        error_log('Wallet address: ' . $signer_wallet['payment_address']);
        error_log('Network: ' . $signer_wallet['network']);

        // 2. Get selected ADA Handle (or fetch first one if none selected)
        $selected_handle_name = get_option('cardano_blog_signer_selected_handle', '');
        $dev_mode = get_option('cardano_blog_signer_dev_mode', 0);
        $dev_mainnet_address = get_option('cardano_blog_signer_dev_mainnet_address', '');
        $handle = null;

        if (!empty($selected_handle_name)) {
            // User selected a specific handle - fetch all and find the matching one
            if ($dev_mode && !empty($dev_mainnet_address)) {
                // DEV MODE: Fetch from mainnet address
                error_log('DEV MODE: Fetching handles from mainnet address: ' . $dev_mainnet_address);
                $all_handles = UmbrellaBlog_BlockfrostHelper::getAllHandlesFromAddress(
                    $dev_mainnet_address,
                    'mainnet'
                );
            } else {
                // NORMAL: Fetch from signing wallet
                $all_handles = UmbrellaBlog_BlockfrostHelper::getAllHandlesFromAddress(
                    $signer_wallet['payment_address'],
                    $signer_wallet['network']
                );
            }

            foreach ($all_handles as $h) {
                if ($h['name'] === $selected_handle_name) {
                    $handle = $h;
                    break;
                }
            }

            error_log('Selected handle: ' . $selected_handle_name . ($handle ? ' (found)' : ' (not found, using anonymous)'));
        } else {
            // No handle selected - try to fetch first one
            $handle = UmbrellaBlog_BlockfrostHelper::getHandleFromAddress(
                $signer_wallet['payment_address'],
                $signer_wallet['network']
            );
            error_log('No handle selected, fetched first: ' . ($handle ? $handle['name'] : 'none'));
        }

        $handle_name = $handle ? $handle['name'] : 'anonymous';
        error_log('Using handle: ' . $handle_name);

        // 3. Build CIP-20 metadata (Anvil format)
        // Build message array with post details
        $post_url = home_url('/blog/' . $post->slug);
        $message_parts = [
            'Blog Post Signature',
            'Title: ' . stripslashes($post->title),
            'URL: ' . $post_url,
            'Author: ' . $handle_name,
            'Published: ' . $post->created_at,
            'Word Count: ' . (int)$post->word_count,
            'Signed with Umbrella Blog v1.0'
        ];

        // Store full metadata for database
        $metadata_for_db = [
            '674' => [
                'msg' => [
                    'type' => 'blog_post_signature',
                    'version' => '1.0',
                    'post_id' => (string)$post_id,
                    'title' => stripslashes($post->title),
                    'slug' => $post->slug,
                    'url' => $post_url,
                    'published_at' => $post->created_at,
                    'author_handle' => $handle_name,
                    'word_count' => (int)$post->word_count,
                    'excerpt' => stripslashes($post->excerpt),
                    'signed_with' => 'Umbrella Blog v1.0'
                ]
            ]
        ];

        error_log('Metadata message: ' . wp_json_encode($message_parts, JSON_PRETTY_PRINT));

        // 4. Build transaction (1 ADA to self with CIP-20 metadata via Anvil message parameter)
        $tx_request = [
            'changeAddress' => $signer_wallet['payment_address'],
            'outputs' => [
                [
                    'address' => $signer_wallet['payment_address'],
                    'lovelace' => 1000000 // 1 ADA
                ]
            ],
            'message' => $message_parts // Anvil automatically creates CIP-20 metadata (label 674)
        ];

        error_log('Building transaction...');
        error_log('🔍 TRANSACTION WALLET CHECK:');
        error_log('  - changeAddress: ' . $signer_wallet['payment_address']);
        error_log('  - output address: ' . $signer_wallet['payment_address']);
        error_log('  - Network: ' . $signer_wallet['network']);
        if ($dev_mode && !empty($dev_mainnet_address)) {
            error_log('  - Dev mode ENABLED (for handle lookup only)');
            error_log('  - Dev address: ' . $dev_mainnet_address . ' (NOT used for transaction)');
        } else {
            error_log('  - Dev mode: disabled');
        }
        error_log('Transaction request: ' . wp_json_encode($tx_request, JSON_PRETTY_PRINT));

        // Load Anvil API helper (standalone version)
        if (!class_exists('UmbrellaBlog_AnvilHelper')) {
            require_once plugin_dir_path(__FILE__) . 'vendor/AnvilHelper.php';
        }

        $build_response = UmbrellaBlog_AnvilHelper::call('transactions/build', $tx_request);

        if (is_wp_error($build_response)) {
            error_log('Build failed: ' . $build_response->get_error_message());
            return $build_response;
        }

        if (!isset($build_response['complete'])) {
            error_log('Build response missing "complete" field: ' . wp_json_encode($build_response));
            return new WP_Error('build_failed', 'Transaction build failed - invalid response');
        }

        error_log('Transaction built successfully');
        error_log('CBOR (first 100 chars): ' . substr($build_response['complete'], 0, 100) . '...');

        // 5. Sign transaction with standalone signer
        if (!class_exists('CardanoTransactionSignerPHP')) {
            require_once plugin_dir_path(__FILE__) . 'vendor/CardanoTransactionSignerPHP.php';
        }

        error_log('Signing transaction...');

        $sign_result = CardanoTransactionSignerPHP::signTransaction(
            $build_response['complete'],
            $signer_wallet['payment_skey_extended']
        );

        if (!$sign_result['success']) {
            error_log('Signing failed: ' . $sign_result['error']);
            return new WP_Error('sign_failed', $sign_result['error']);
        }

        error_log('Transaction signed successfully');
        error_log('Witness set (first 100 chars): ' . substr($sign_result['witnessSetHex'], 0, 100) . '...');

        // 6. Submit to blockchain
        error_log('Submitting to blockchain...');

        $submit_response = UmbrellaBlog_AnvilHelper::call('transactions/submit', [
            'transaction' => $build_response['complete'],
            'signatures' => [$sign_result['witnessSetHex']]
        ]);

        if (is_wp_error($submit_response)) {
            error_log('Submit failed: ' . $submit_response->get_error_message());
            return $submit_response;
        }

        $tx_hash = $submit_response['txHash'] ?? $submit_response['hash'] ?? null;

        if (!$tx_hash) {
            error_log('Submit response missing tx hash: ' . wp_json_encode($submit_response));
            return new WP_Error('submit_failed', 'Transaction submission failed - no hash returned');
        }

        error_log('Transaction submitted! Hash: ' . $tx_hash);

        // 7. Save signature data to database
        $update_result = $wpdb->update(
            $wpdb->prefix . 'umbrella_blog_posts',
            [
                'signature_tx_hash' => $tx_hash,
                'signature_wallet_address' => $signer_wallet['payment_address'],
                'signature_handle' => $handle ? $handle['name'] : null,
                'signature_handle_image' => $handle ? $handle['image'] : null,
                'signed_at' => current_time('mysql'),
                'signature_metadata' => wp_json_encode($metadata_for_db)
            ],
            ['id' => $post_id]
        );

        if ($update_result === false) {
            error_log('Database update failed: ' . $wpdb->last_error);
            return new WP_Error('db_failed', 'Failed to save signature data to database');
        }

        error_log('=== BLOG SIGNER: Signature complete! ===');

        return [
            'success' => true,
            'tx_hash' => $tx_hash,
            'handle' => $handle,
            'network' => $signer_wallet['network'],
            'wallet_address' => $signer_wallet['payment_address'],
            'explorer_url' => self::getExplorerUrl($tx_hash, $signer_wallet['network'])
        ];
    }

    /**
     * Truncate string to Cardano metadata limit
     *
     * @param string $string String to truncate
     * @param int $limit Character limit
     * @return string Truncated string
     */
    private static function truncate($string, $limit) {
        if (strlen($string) <= $limit) {
            return $string;
        }
        return substr($string, 0, $limit - 3) . '...';
    }

    /**
     * Get blockchain explorer URL for transaction
     *
     * @param string $tx_hash Transaction hash
     * @param string $network Network
     * @return string Explorer URL
     */
    private static function getExplorerUrl($tx_hash, $network) {
        $base_url = ($network === 'mainnet')
            ? 'https://cardanoscan.io'
            : 'https://preprod.cardanoscan.io';

        return $base_url . '/transaction/' . $tx_hash;
    }

    /**
     * Verify a signature on-chain (future feature)
     *
     * @param int $post_id Post ID
     * @return array|WP_Error Verification result
     */
    public static function verifySignature($post_id) {
        global $wpdb;

        $post = $wpdb->get_row($wpdb->prepare(
            "SELECT signature_tx_hash, signature_metadata FROM {$wpdb->prefix}umbrella_blog_posts WHERE id = %d",
            $post_id
        ));

        if (!$post || empty($post->signature_tx_hash)) {
            return new WP_Error('not_signed', 'Post is not signed');
        }

        // TODO: Implement on-chain verification via Blockfrost
        // Fetch transaction metadata and compare with stored metadata

        return [
            'success' => true,
            'verified' => true,
            'tx_hash' => $post->signature_tx_hash
        ];
    }
}
