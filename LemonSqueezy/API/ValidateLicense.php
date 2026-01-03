<?php

/**
 * LemonSqueezy Validate License Operation.
 *
 * Handles license validation via LemonSqueezy public License API.
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
 * Validate License class.
 *
 * Handles the validation of a license key via the LemonSqueezy public License API.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\API\ValidateLicense')) {
    class ValidateLicense
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
         * Execute license validation.
         *
         * @param string $license_key The license key to validate.
         * @param string $instance_id The instance ID (activation hash).
         * @param string $instance_name The instance name (usually website URL).
         * @param bool $silent Whether to suppress errors (for background checks).
         * @return array Response array with validation result.
         */
        public function execute(
            string $license_key,
            string $instance_id,
            string $instance_name,
            bool $silent = false
        ): array {
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

            // Prepare request parameters for LemonSqueezy License API.
            $params = [
                'license_key'   => $license_key,
                'instance_id'   => $instance_id,
                'instance_name' => $instance_name,
            ];

            // Make API request to validate endpoint.
            $response = $this->client->request('licenses/validate', 'POST', $params);

            if (!$response['success']) {
                // In silent mode, return success but mark as invalid.
                if ($silent) {
                    return [
                        'success' => true,
                        'valid'   => false,
                        'message' => __('License validation check completed.', 'slk-license-checker'),
                    ];
                }

                return $response;
            }

            // Parse validation response.
            return $this->parse_validation_response($response['data'] ?? []);
        }

        /**
         * Parse the validation response from LemonSqueezy License API.
         *
         * @param array $response_data The API response data.
         * @return array Normalized response.
         */
        private function parse_validation_response(array $response_data): array
        {
            // Check if license is valid from response.
            $is_valid = isset($response_data['valid']) && (bool) $response_data['valid'];
            $times_activated = (int) ($response_data['times_activated'] ?? 0);
            $times_activated_max = (int) ($response_data['times_activated_max'] ?? 0);

            return [
                'success' => true,
                'valid'   => $is_valid,
                'message' => $is_valid
                    ? __('License is valid.', 'slk-license-checker')
                    : __('License is invalid or expired.', 'slk-license-checker'),
                'data'    => [
                    'timesActivated'    => $times_activated,
                    'timesActivatedMax' => $times_activated_max,
                ],
            ];
        }
    }
}
