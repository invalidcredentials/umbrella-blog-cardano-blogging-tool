<?php
/**
 * Blockfrost API Helper for Blog Signing
 *
 * Fetches ADA Handle NFTs from wallet addresses
 * Reuses Blockfrost configuration from theme/other plugins
 */

class UmbrellaBlog_BlockfrostHelper {

    /**
     * ADA Handle Policy ID (Mainnet)
     */
    const ADA_HANDLE_POLICY_MAINNET = 'f0ff48bbb7bbe9d59a40f1ce90e9e9d0ff5002ec48f232b49ca0fb9a';

    /**
     * ADA Handle Policy ID (Preprod)
     */
    const ADA_HANDLE_POLICY_PREPROD = 'f0ff48bbb7bbe9d59a40f1ce90e9e9d0ff5002ec48f232b49ca0fb9a';

    /**
     * Get Blockfrost API configuration
     *
     * @param string $network Network (mainnet/preprod)
     * @return array Configuration
     */
    private static function getApiConfig($network = 'preprod') {
        $is_mainnet = ($network === 'mainnet');

        $api_url = $is_mainnet
            ? 'https://cardano-mainnet.blockfrost.io/api/v0'
            : 'https://cardano-preprod.blockfrost.io/api/v0';

        // Use Umbrella Blog settings (standalone)
        $api_key_option = $is_mainnet
            ? 'umbrella_blog_blockfrost_mainnet_key'
            : 'umbrella_blog_blockfrost_preprod_key';

        $api_key = get_option($api_key_option);

        // Fallback to theme options if available (for backwards compatibility)
        if (empty($api_key)) {
            $api_key_option = $is_mainnet
                ? 'cardano_place_blockfrost_api_key_mainnet'
                : 'cardano_place_blockfrost_api_key_preprod';
            $api_key = get_option($api_key_option);
        }

        return [
            'url' => $api_url,
            'key' => $api_key,
            'network' => $network
        ];
    }

    /**
     * Make Blockfrost API request
     *
     * @param string $endpoint API endpoint
     * @param string $network Network
     * @return array|null Response data or null on error
     */
    private static function apiRequest($endpoint, $network = 'preprod') {
        $config = self::getApiConfig($network);

        if (empty($config['key'])) {
            error_log('UmbrellaBlog Blockfrost: No API key configured for ' . $network);
            return null;
        }

        $url = $config['url'] . $endpoint;

        $response = wp_remote_get($url, [
            'headers' => [
                'project_id' => $config['key']
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('UmbrellaBlog Blockfrost API error: ' . $response->get_error_message());
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            error_log('UmbrellaBlog Blockfrost API returned status ' . $status_code . ' - Response: ' . $body);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Get ALL ADA Handles from wallet address
     *
     * @param string $address Cardano address
     * @param string $network Network (auto-detected from address if not provided)
     * @return array Array of handle data (name, image, asset_name)
     */
    public static function getAllHandlesFromAddress($address, $network = null) {
        // Auto-detect network from address if not provided
        if (!$network) {
            $network = (strpos($address, 'addr1') === 0) ? 'mainnet' : 'preprod';
        }

        // Get policy ID for network
        $policy_id = ($network === 'mainnet')
            ? self::ADA_HANDLE_POLICY_MAINNET
            : self::ADA_HANDLE_POLICY_PREPROD;

        // Fetch assets from address
        $assets = self::apiRequest("/addresses/{$address}", $network);

        if (!$assets || !isset($assets['amount'])) {
            error_log('UmbrellaBlog: No assets found for address: ' . $address);
            return [];
        }

        $handles = [];

        // Search for ALL ADA Handle NFTs in assets
        foreach ($assets['amount'] as $asset) {
            // Check if this asset belongs to ADA Handle policy
            if (isset($asset['unit']) && strpos($asset['unit'], $policy_id) === 0) {
                // Get metadata for this asset (includes handle name and image)
                $metadata = self::getAssetMetadata($asset['unit'], $network);

                if (!$metadata || !isset($metadata['onchain_metadata'])) {
                    error_log('UmbrellaBlog: No metadata for asset: ' . $asset['unit']);
                    continue;
                }

                $onchain = $metadata['onchain_metadata'];

                // Extract handle name from metadata (e.g., "$pb_anvil")
                $handle_name = $onchain['name'] ?? null;
                if (empty($handle_name)) {
                    error_log('UmbrellaBlog: No name in metadata for asset: ' . $asset['unit']);
                    continue;
                }

                // Extract raw name without $ prefix
                $raw_name = ltrim($handle_name, '$');

                // Extract and resolve image URL
                $image = null;
                if (isset($onchain['image'])) {
                    $image = self::resolveIpfsUrl($onchain['image']);
                }

                error_log('UmbrellaBlog: Found handle: ' . $handle_name . ' with image: ' . ($image ?? 'none'));

                $handles[] = [
                    'name' => $handle_name, // Already includes $ from metadata
                    'raw_name' => $raw_name,
                    'image' => $image,
                    'asset_name' => $asset['unit'],
                    'policy_id' => $policy_id,
                    'metadata' => $onchain // Store full metadata for debugging
                ];
            }
        }

        return $handles;
    }

    /**
     * Get ADA Handle from wallet address (returns first handle found)
     *
     * @param string $address Cardano address
     * @param string $network Network (auto-detected from address if not provided)
     * @return array|null Handle data or null if not found
     */
    public static function getHandleFromAddress($address, $network = null) {
        // Auto-detect network from address if not provided
        if (!$network) {
            $network = (strpos($address, 'addr1') === 0) ? 'mainnet' : 'preprod';
        }

        // Get policy ID for network
        $policy_id = ($network === 'mainnet')
            ? self::ADA_HANDLE_POLICY_MAINNET
            : self::ADA_HANDLE_POLICY_PREPROD;

        // Fetch assets from address
        $assets = self::apiRequest("/addresses/{$address}", $network);

        if (!$assets || !isset($assets['amount'])) {
            error_log('UmbrellaBlog: No assets found for address: ' . $address);
            return null;
        }

        // Search for ADA Handle NFT in assets (return first found)
        foreach ($assets['amount'] as $asset) {
            // Check if this asset belongs to ADA Handle policy
            if (isset($asset['unit']) && strpos($asset['unit'], $policy_id) === 0) {
                // Get metadata for this asset (includes handle name and image)
                $metadata = self::getAssetMetadata($asset['unit'], $network);

                if (!$metadata || !isset($metadata['onchain_metadata'])) {
                    continue;
                }

                $onchain = $metadata['onchain_metadata'];

                // Extract handle name from metadata (e.g., "$pb_anvil")
                $handle_name = $onchain['name'] ?? null;
                if (empty($handle_name)) {
                    continue;
                }

                // Extract raw name without $ prefix
                $raw_name = ltrim($handle_name, '$');

                // Extract and resolve image URL
                $image = null;
                if (isset($onchain['image'])) {
                    $image = self::resolveIpfsUrl($onchain['image']);
                }

                return [
                    'name' => $handle_name, // Already includes $ from metadata
                    'raw_name' => $raw_name,
                    'image' => $image,
                    'asset_name' => $asset['unit'],
                    'policy_id' => $policy_id
                ];
            }
        }

        error_log('UmbrellaBlog: No ADA Handle found for address: ' . $address);
        return null;
    }

    /**
     * Get asset metadata from Blockfrost
     *
     * @param string $asset Asset unit (policy_id + asset_name_hex)
     * @param string $network Network
     * @return array|null Metadata or null
     */
    private static function getAssetMetadata($asset, $network = 'preprod') {
        return self::apiRequest("/assets/{$asset}", $network);
    }

    /**
     * Resolve IPFS URL to HTTP gateway
     *
     * @param string $url IPFS or HTTP URL
     * @return string HTTP URL
     */
    private static function resolveIpfsUrl($url) {
        if (strpos($url, 'ipfs://') === 0) {
            $cid = substr($url, 7);
            return 'https://ipfs.io/ipfs/' . $cid;
        }
        return $url;
    }

    /**
     * Get address information from Blockfrost
     *
     * @param string $address Cardano address
     * @param string $network Network
     * @return array|WP_Error Address info or error
     */
    public static function getAddressInfo($address, $network = 'preprod') {
        $config = self::getApiConfig($network);

        if (empty($config['key'])) {
            return new WP_Error('no_api_key', 'No Blockfrost API key configured for ' . $network . '. Please add it in Settings.');
        }

        // Get address info
        $address_info = self::apiRequest("/addresses/{$address}", $network);

        if ($address_info === null) {
            return new WP_Error('api_error', 'Failed to fetch address info from Blockfrost');
        }

        // Get UTxOs
        $utxos = self::apiRequest("/addresses/{$address}/utxos", $network);

        return [
            'address' => $address,
            'amount' => $address_info['amount'] ?? [],
            'stake_address' => $address_info['stake_address'] ?? null,
            'utxos' => $utxos ?? []
        ];
    }

    /**
     * Test Blockfrost connection
     *
     * @param string $network Network to test
     * @return array Test results
     */
    public static function testConnection($network = 'preprod') {
        $config = self::getApiConfig($network);

        if (empty($config['key'])) {
            return [
                'success' => false,
                'error' => 'No Blockfrost API key configured for ' . $network
            ];
        }

        // Test API with a simple health check
        $response = self::apiRequest('/health', $network);

        if ($response === null) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Blockfrost API'
            ];
        }

        return [
            'success' => true,
            'network' => $network,
            'api_url' => $config['url']
        ];
    }
}
