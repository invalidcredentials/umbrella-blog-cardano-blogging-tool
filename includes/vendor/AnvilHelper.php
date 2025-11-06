<?php
/**
 * Anvil API Helper for Umbrella Blog
 * Copied from theme AnvilHelper (no modifications)
 */

class UmbrellaBlog_AnvilHelper {

    /**
     * Call Anvil API endpoint
     */
    public static function call($endpoint, $data) {
        // Determine network from settings
        $network = get_option('umbrella_blog_default_network', 'preprod');

        // Get API URL and key based on network
        if ($network === 'mainnet') {
            $api_url = 'https://prod.api.ada-anvil.app/v2/services';
            $api_key = get_option('umbrella_blog_anvil_mainnet_api_key');
        } else {
            $api_url = 'https://preprod.api.ada-anvil.app/v2/services';
            $api_key = get_option('umbrella_blog_anvil_preprod_api_key');
        }

        if (!$api_key) {
            error_log('UmbrellaBlog: No Anvil API key configured for ' . $network);
            return new WP_Error('no_api_key', 'Anvil API key not configured for ' . $network . '. Please add it in Settings.');
        }

        error_log('UmbrellaBlog: Calling Anvil API: ' . $api_url . '/' . $endpoint);
        error_log('UmbrellaBlog: Request data: ' . wp_json_encode($data, JSON_PRETTY_PRINT));

        $response = wp_remote_post($api_url . '/' . $endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Api-Key' => $api_key
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('UmbrellaBlog: API error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('UmbrellaBlog: API returned status ' . $status_code);
            error_log('UmbrellaBlog: Response body: ' . $body);
            return new WP_Error('api_error', $decoded['message'] ?? 'API request failed');
        }

        error_log('UmbrellaBlog: API success: ' . wp_json_encode($decoded, JSON_PRETTY_PRINT));
        return $decoded;
    }

    /**
     * Build transaction for tile purchase (MINTING) - NOT USED IN BLOG
     */
    public static function buildTilePurchase($customer_address, $x, $y, $color, $note) {
        // Get merchant address from settings
        $merchant_address = get_option('cardano_mint_merchant_address', '');
        if (empty($merchant_address)) {
            return new WP_Error('no_merchant', 'Merchant address not configured');
        }

        // Get policy wallet
        $policy_wallet = cardano_place_get_policy_wallet();
        if (!$policy_wallet) {
            return new WP_Error('no_policy', 'Cardano Place policy not configured');
        }

        // Convert addresses to Bech32
        $merchant_address = self::convertAddressToBech32($merchant_address);
        $customer_address = self::convertAddressToBech32($customer_address);

        // Pricing
        $tile_price_lovelace = 1000000; // 1 tADA
        $receipt_lovelace = 2000000; // 2 ADA (min for NFT output)

        // Generate unique asset name
        $asset_name = 'Tile_' . $x . '_' . $y . '_' . time();

        // Generate SVG color tile (1x1 colored square)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 1 1">' .
               '<rect width="1" height="1" fill="' . esc_attr($color) . '"/>' .
               '</svg>';

        // Base64 encode the SVG for data URI
        $svg_base64 = base64_encode($svg);
        $image_data_uri = 'data:image/svg+xml;base64,' . $svg_base64;

        // CIP-25 metadata
        $metadata = array(
            'name' => 'Cardano.Place Tile #' . $x . ',' . $y,
            'image' => $image_data_uri,
            'description' => 'Pixel tile on Cardano.Place',
            'mediaType' => 'image/svg+xml',
            'website' => 'https://umbrella.lol/cardano-place/',
            'x' => $x,
            'y' => $y,
            'color' => $color,
            'note' => $note,
            'purchasedAt' => current_time('c')
        );

        // Build transaction with MINTING
        $transaction_request = array(
            'changeAddress' => $customer_address,
            'outputs' => array(
                array(
                    'address' => $merchant_address,
                    'lovelace' => $tile_price_lovelace
                ),
                array(
                    'address' => $customer_address,
                    'lovelace' => $receipt_lovelace,
                    'assets' => array(
                        array(
                            'policyId' => $policy_wallet['policy_id'],
                            'assetName' => array(
                                'name' => $asset_name,
                                'format' => 'utf8'
                            ),
                            'quantity' => 1
                        )
                    )
                )
            ),
            'mint' => array(
                array(
                    'version' => 'cip25',
                    'policyId' => $policy_wallet['policy_id'],
                    'quantity' => 1,
                    'assetName' => array(
                        'name' => $asset_name,
                        'format' => 'utf8'
                    ),
                    'metadata' => $metadata
                )
            ),
            'preloadedScripts' => array(
                array(
                    'type' => 'simple',
                    'script' => json_decode($policy_wallet['policy_schema'], true),
                    'hash' => $policy_wallet['policy_id']
                )
            )
        );

        error_log('CardanoPlace: Building MINT transaction for tile (' . $x . ',' . $y . ')');
        error_log('CardanoPlace: Request: ' . wp_json_encode($transaction_request, JSON_PRETTY_PRINT));
        return self::call('transactions/build', $transaction_request);
    }

    /**
     * Submit signed transaction to blockchain
     * Adds policy wallet signature before submitting
     */
    public static function submitTransaction($transaction_cbor, $signatures) {
        error_log('=== CARDANO PLACE: ADDING POLICY WALLET SIGNATURE ===');

        // Get policy wallet
        $policy_wallet = cardano_place_get_policy_wallet();
        if (!$policy_wallet) {
            error_log('No policy wallet found');
            return new WP_Error('no_policy_wallet', 'Policy wallet not configured');
        }

        // Decrypt the signing key
        if (class_exists('\CardanoMintPay\Helpers\EncryptionHelper')) {
            $skey_hex = \CardanoMintPay\Helpers\EncryptionHelper::decrypt($policy_wallet['skey_encrypted']);
        } else {
            // Fallback for testing
            $skey_hex = base64_decode($policy_wallet['skey_encrypted']);
        }

        if (empty($skey_hex)) {
            error_log('Failed to decrypt policy wallet signing key');
            return new WP_Error('decryption_failed', 'Could not decrypt policy wallet signing key');
        }

        // Sign transaction using CardanoCLI helper (same as mint plugin)
        error_log('Signing transaction with policy wallet...');
        if (class_exists('\CardanoMintPay\Helpers\CardanoCLI')) {
            $result = \CardanoMintPay\Helpers\CardanoCLI::signTransaction($transaction_cbor, $skey_hex);

            if (!$result || !isset($result['success']) || !$result['success']) {
                $error_msg = isset($result['error']) ? $result['error'] : 'Unknown signing error';
                error_log('Transaction signing failed: ' . $error_msg);
                return new WP_Error('sign_failed', 'Transaction signing failed: ' . $error_msg);
            }

            // Add policy wallet's witness set to signatures array
            if (isset($result['witnessSetHex'])) {
                array_unshift($signatures, $result['witnessSetHex']);
                error_log('✅ Policy wallet witness set added');
            } else {
                error_log('⚠️ WARNING: No witnessSetHex in signing result');
            }
        } else {
            error_log('ERROR: CardanoCLI class not found!');
            return new WP_Error('no_cli', 'CardanoCLI helper not available');
        }

        // Submit with all signatures
        // IMPORTANT: Transaction should remain UNSIGNED, witnesses are passed separately
        $submit_data = array(
            'transaction' => $transaction_cbor,  // Keep unsigned transaction
            'signatures' => is_array($signatures) ? $signatures : array($signatures)
        );

        error_log('=== CARDANO PLACE: SUBMITTING TO ANVIL ===');
        error_log('Transaction (first 100 chars): ' . substr($transaction_cbor, 0, 100) . '...');
        error_log('Number of signatures: ' . count($submit_data['signatures']));
        foreach ($submit_data['signatures'] as $i => $sig) {
            error_log('Signature ' . $i . ' (first 60 chars): ' . substr($sig, 0, 60) . '...');
        }
        error_log('===========================');

        return self::call('transactions/submit', $submit_data);
    }

    /**
     * Convert CBOR address to Bech32 format using Anvil API
     */
    public static function convertAddressToBech32($address) {
        // Already Bech32?
        if (preg_match('/^addr[0-9a-z]+$/', $address)) {
            return $address;
        }

        // Try to convert using Anvil
        $response = self::call('utils/addresses/parse', array('address' => $address));

        if (is_wp_error($response)) {
            return $address; // Return original if conversion fails
        }

        // Extract Bech32 from response
        if (isset($response['address'])) {
            return $response['address'];
        } elseif (isset($response['bech32Address'])) {
            return $response['bech32Address'];
        }

        return $address;
    }

    /**
     * Truncate metadata to 64 chars (Cardano limit)
     */
    public static function truncateMetadata($string) {
        if (strlen($string) <= 64) {
            return $string;
        }
        return substr($string, 0, 61) . '...';
    }
}
