<?php

/**
 * LemonSqueezy Activate License Operation.
 *
 * Handles license activation via LemonSqueezy public License API.
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
 * Activate License class.
 *
 * Handles the activation of a license key via the LemonSqueezy public License API.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\API\ActivateLicense')) {
    class ActivateLicense
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
         * Execute license activation.
         *
         * @param string $license_key The license key to activate.
         * @param string $instance_name The instance name (usually website URL).
         * @return array Response array with activation result.
         */
        public function execute(string $license_key, string $instance_name): array
        {
            $license_key = sanitize_text_field(trim($license_key));
            $instance_name = sanitize_text_field(trim($instance_name));

            if (empty($license_key)) {
                return [
                    'success' => false,
                    'message' => __('License key is required.', 'slk-license-checker'),
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
                'instance_name' => $instance_name,
            ];

            // Make API request to activate endpoint.
            $response = $this->client->request('licenses/activate', 'POST', $params);

            if (!$response['success']) {
                return $response;
            }

            // Parse activation response and extract activation hash + usage info.
            return $this->parse_activation_response($response['data'] ?? []);
        }

        /**
         * Parse the activation response from LemonSqueezy License API.
         *
         * Converts LemonSqueezy's response format to the standard format
         * expected by LicenseChecker.
         *
         * @param array $response_data The API response data.
         * @return array Normalized response.
         */
        private function parse_activation_response(array $response_data): array
        {
            $activation_hash = '';
            $times_activated = 0;
            $times_activated_max = 0;

            // Extract activation hash (instance ID) from response.
            // LemonSqueezy returns instance ID for deactivation.
            if (isset($response_data['instance_id'])) {
                $activation_hash = sanitize_text_field($response_data['instance_id']);
            }

            // Extract activation counts.
            if (isset($response_data['times_activated'])) {
                $times_activated = (int) $response_data['times_activated'];
            }
            if (isset($response_data['times_activated_max'])) {
                $times_activated_max = (int) $response_data['times_activated_max'];
            }

            return [
                'success'         => true,
                'message'         => __('License activated successfully.', 'slk-license-checker'),
                'activation_hash' => $activation_hash,
                'data'            => [
                    'timesActivated'    => $times_activated,
                    'timesActivatedMax' => $times_activated_max,
                ],
            ];
        }
    }
}
