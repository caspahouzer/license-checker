<?php

/**
 * LemonSqueezy Deactivate License Operation.
 *
 * Handles license deactivation via LemonSqueezy public License API.
 *
 * @package SLK\LicenseChecker\LemonSqueezy\API
 */

declare(strict_types=1);

namespace SLK\LicenseChecker\LemonSqueezy\API;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Deactivate License class.
 *
 * Handles the deactivation of a license key via the LemonSqueezy public License API.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\API\DeactivateLicense')) {
    class DeactivateLicense
    {
        /**
         * The HTTP client instance.
         *
         * @var LemonSqueezyClient
         */
        private LemonSqueezyClient $client;

        /**
         * Constructor.
         *
         * @param LemonSqueezyClient $client The HTTP client.
         */
        public function __construct(LemonSqueezyClient $client)
        {
            $this->client = $client;
        }

        /**
         * Execute license deactivation.
         *
         * @param string $license_key The license key to deactivate.
         * @param string $instance_id The instance ID (activation hash).
         * @param string $instance_name The instance name (usually website URL).
         * @return array Response array with deactivation result.
         */
        public function execute(string $license_key, string $instance_id, string $instance_name): array
        {
            $license_key = sanitize_text_field(trim($license_key));
            $instance_id = sanitize_text_field(trim($instance_id));
            $instance_name = sanitize_text_field(trim($instance_name));

            if (empty($license_key)) {
                return [
                    'success' => false,
                    'message' => __('License key is required.', 'slk-license-checker'),
                ];
            }

            if (empty($instance_id)) {
                return [
                    'success' => false,
                    'message' => __('Instance ID is required.', 'slk-license-checker'),
                ];
            }

            if (empty($instance_name)) {
                return [
                    'success' => false,
                    'message' => __('Instance name is required.', 'slk-license-checker'),
                ];
            }

            // Prepare request parameters for LemonSqueezy License API.
            $params = [
                'license_key'   => $license_key,
                'instance_id'   => $instance_id,
                'instance_name' => $instance_name,
            ];

            // Make API request to deactivate endpoint.
            $response = $this->client->request('licenses/deactivate', 'POST', $params);

            if (!$response['success']) {
                return $response;
            }

            return [
                'success' => true,
                'message' => __('License deactivated successfully.', 'slk-license-checker'),
            ];
        }
    }
}
