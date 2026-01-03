<?php

/**
 * LemonSqueezy License Strategy Implementation.
 *
 * Orchestrates license operations via LemonSqueezy public License API.
 * No authentication required - public API suitable for client-side use.
 *
 * @package SLK\LicenseChecker\LemonSqueezy
 */

declare(strict_types=1);

namespace SLK\LicenseChecker\LemonSqueezy;

use SLK\LicenseChecker\LemonSqueezy\API\LemonSqueezyClient;
use SLK\LicenseChecker\LemonSqueezy\API\ActivateLicense;
use SLK\LicenseChecker\LemonSqueezy\API\DeactivateLicense;
use SLK\LicenseChecker\LemonSqueezy\API\ValidateLicense;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * LemonSqueezy License Strategy class.
 *
 * Orchestrates activation, deactivation, and validation of licenses via LemonSqueezy.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\LemonSqueezyStrategy')) {
    class LemonSqueezyStrategy
    {
        /**
         * The HTTP client.
         *
         * @var LemonSqueezyClient
         */
        private LemonSqueezyClient $client;

        /**
         * The activate operation handler.
         *
         * @var ActivateLicense
         */
        private ActivateLicense $activate;

        /**
         * The deactivate operation handler.
         *
         * @var DeactivateLicense
         */
        private DeactivateLicense $deactivate;

        /**
         * The validate operation handler.
         *
         * @var ValidateLicense
         */
        private ValidateLicense $validate;

        /**
         * Constructor.
         *
         * Initializes the HTTP client and API operation handlers.
         */
        public function __construct()
        {
            // Create HTTP client (no API key needed for public License API).
            $this->client = new LemonSqueezyClient();

            // Initialize operation handlers.
            $this->activate = new ActivateLicense($this->client);
            $this->deactivate = new DeactivateLicense($this->client);
            $this->validate = new ValidateLicense($this->client);
        }

        /**
         * Activate a license.
         *
         * @param string $license_key The license key to activate.
         * @return array Response array.
         */
        public function activate_license(string $license_key): array
        {
            $instance_name = $this->get_instance_name();

            if (empty($instance_name)) {
                return [
                    'success' => false,
                    'message' => __('Unable to determine instance name for license activation.', 'slk-license-checker'),
                ];
            }

            return $this->activate->execute($license_key, $instance_name);
        }

        /**
         * Deactivate a license.
         *
         * @param string $license_key The license key.
         * @param string $activation_hash The activation hash (instance ID).
         * @return array Response array.
         */
        public function deactivate_license(string $license_key, string $activation_hash): array
        {
            $instance_name = $this->get_instance_name();

            if (empty($instance_name)) {
                return [
                    'success' => false,
                    'message' => __('Unable to determine instance name for license deactivation.', 'slk-license-checker'),
                ];
            }

            // activation_hash is the instance_id in LemonSqueezy.
            return $this->deactivate->execute($license_key, $activation_hash, $instance_name);
        }

        /**
         * Validate a license.
         *
         * @param string $license_key The license key.
         * @param string $activation_hash The activation hash (instance ID).
         * @param bool $silent Whether to suppress errors for background checks.
         * @return array Response array.
         */
        public function validate_license(string $license_key, string $activation_hash, bool $silent = false): array
        {
            $instance_name = $this->get_instance_name();

            if (empty($instance_name)) {
                if ($silent) {
                    // In silent mode, return success to avoid blocking.
                    return [
                        'success' => true,
                        'valid'   => false,
                        'message' => __('Unable to determine instance name.', 'slk-license-checker'),
                    ];
                }

                return [
                    'success' => false,
                    'message' => __('Unable to determine instance name for license validation.', 'slk-license-checker'),
                ];
            }

            // activation_hash is the instance_id in LemonSqueezy.
            return $this->validate->execute($license_key, $activation_hash, $instance_name, $silent);
        }

        /**
         * Get license details.
         *
         * Note: LemonSqueezy License API doesn't have a separate details endpoint.
         * Details are returned in activation/validation responses.
         *
         * @param string $license_key The license key.
         * @return array Response array.
         */
        public function get_license_details(string $license_key): array
        {
            // Details are returned in activation/validation responses.
            // This is a no-op for the public License API.
            return [
                'success' => true,
                'message' => __('License details available via activation or validation.', 'slk-license-checker'),
            ];
        }

        /**
         * Get the instance name (usually the website URL).
         *
         * @return string The instance name or empty string if unable to determine.
         */
        private function get_instance_name(): string
        {
            // Use WordPress home URL as instance name.
            $home_url = home_url();

            if (empty($home_url)) {
                // Fallback to HTTP_HOST if home_url fails.
                $instance_name = $_SERVER['HTTP_HOST'] ?? '';
            } else {
                // Extract domain from URL.
                $parsed = wp_parse_url($home_url);
                $instance_name = $parsed['host'] ?? $home_url;
            }

            return sanitize_text_field(trim($instance_name));
        }
    }
}
