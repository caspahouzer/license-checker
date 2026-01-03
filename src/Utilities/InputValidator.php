<?php

/**
 * Input Validation Utility for License Operations.
 *
 * Centralizes input validation logic for license operations.
 *
 * @package SLK\LicenseChecker\Utilities
 */

declare(strict_types=1);

namespace SLK\LicenseChecker\Utilities;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Input Validator class.
 *
 * Provides validation methods for license operations.
 */
if (! class_exists('SLK\LicenseChecker\Utilities\InputValidator')) {
    class InputValidator
    {
        /**
         * Validate a license key.
         *
         * @param string $key The license key to validate.
         * @return array Array with 'valid' boolean and optional 'message' string.
         */
        public static function validate_license_key(string $key): array
        {
            $key = sanitize_text_field(trim($key));

            if (empty($key)) {
                return [
                    'valid'   => false,
                    'message' => __('License key is required.', 'slk-license-checker'),
                ];
            }

            return [
                'valid' => true,
                'value' => $key,
            ];
        }

        /**
         * Validate an activation hash (instance ID).
         *
         * @param string $hash The activation hash to validate.
         * @return array Array with 'valid' boolean and optional 'message' string.
         */
        public static function validate_activation_hash(string $hash): array
        {
            $hash = sanitize_text_field(trim($hash));

            if (empty($hash)) {
                return [
                    'valid'   => false,
                    'message' => __('Instance ID is required.', 'slk-license-checker'),
                ];
            }

            return [
                'valid' => true,
                'value' => $hash,
            ];
        }

        /**
         * Validate an instance name.
         *
         * @param string $name The instance name to validate.
         * @return array Array with 'valid' boolean and optional 'message' string.
         */
        public static function validate_instance_name(string $name): array
        {
            $name = sanitize_text_field(trim($name));

            if (empty($name)) {
                return [
                    'valid'   => false,
                    'message' => __('Instance name is required.', 'slk-license-checker'),
                ];
            }

            return [
                'valid' => true,
                'value' => $name,
            ];
        }

        /**
         * Validate license key and activation hash together.
         *
         * @param string $key  The license key.
         * @param string $hash The activation hash.
         * @return array Array with 'valid' boolean and optional 'message'/'values' keys.
         */
        public static function validate_license_and_hash(string $key, string $hash): array
        {
            $key_validation = self::validate_license_key($key);
            if (!$key_validation['valid']) {
                return $key_validation;
            }

            $hash_validation = self::validate_activation_hash($hash);
            if (!$hash_validation['valid']) {
                return $hash_validation;
            }

            return [
                'valid'  => true,
                'values' => [
                    'key'  => $key_validation['value'],
                    'hash' => $hash_validation['value'],
                ],
            ];
        }

        /**
         * Validate license key and instance name together.
         *
         * @param string $key  The license key.
         * @param string $name The instance name.
         * @return array Array with 'valid' boolean and optional 'message'/'values' keys.
         */
        public static function validate_license_and_instance(string $key, string $name): array
        {
            $key_validation = self::validate_license_key($key);
            if (!$key_validation['valid']) {
                return $key_validation;
            }

            $name_validation = self::validate_instance_name($name);
            if (!$name_validation['valid']) {
                return $name_validation;
            }

            return [
                'valid'  => true,
                'values' => [
                    'key'  => $key_validation['value'],
                    'name' => $name_validation['value'],
                ],
            ];
        }
    }
}
