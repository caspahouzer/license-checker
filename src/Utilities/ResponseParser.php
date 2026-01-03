<?php

/**
 * Response Parsing Utility for License Operations.
 *
 * Centralizes response parsing and normalization logic.
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
 * Response Parser class.
 *
 * Provides standardized response parsing and normalization.
 */
if (! class_exists('SLK\LicenseChecker\Utilities\ResponseParser')) {
    class ResponseParser
    {
        /**
         * Parse and normalize an API response.
         *
         * Ensures response has 'success', 'data', and 'message' keys.
         *
         * @param array $response The API response to parse.
         * @return array Normalized response array.
         */
        public static function parse(array $response): array
        {
            return [
                'success' => $response['success'] ?? false,
                'data'    => $response['data'] ?? null,
                'message' => $response['message'] ?? __('Operation completed.', 'slk-license-checker'),
            ];
        }

        /**
         * Extract error messages from a response.
         *
         * Handles various error formats including nested errors.
         *
         * @param array $response The response containing errors.
         * @return array Array of error messages.
         */
        public static function extract_errors(array $response): array
        {
            $errors = [];

            // Single error message
            if (isset($response['message']) && is_string($response['message']) && !empty($response['message'])) {
                $errors[] = $response['message'];
            }

            // Array of error messages
            if (isset($response['errors']) && is_array($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (is_string($error)) {
                        $errors[] = $error;
                    } elseif (is_array($error) && isset($error['message'])) {
                        $errors[] = $error['message'];
                    }
                }
            }

            // Nested errors from data
            if (isset($response['data']) && is_array($response['data'])) {
                $extracted = self::extract_nested_errors($response['data']);
                $errors = array_merge($errors, $extracted);
            }

            return array_filter(array_unique($errors));
        }

        /**
         * Extract nested error messages from response data.
         *
         * Handles LMFWC (License Manager for WooCommerce) error format.
         *
         * @param array $data The response data containing nested errors.
         * @return array Array of error messages.
         */
        public static function extract_nested_errors(array $data): array
        {
            $errors = [];

            // Check for LMFWC errors array
            if (isset($data['errors']) && is_array($data['errors'])) {
                foreach ($data['errors'] as $key => $error) {
                    if (is_array($error) && isset($error[0])) {
                        $errors[] = sanitize_text_field($error[0]);
                    } elseif (is_string($error)) {
                        $errors[] = sanitize_text_field($error);
                    }
                }
            }

            // Check for error message in data
            if (isset($data['error']) && is_string($data['error'])) {
                $errors[] = sanitize_text_field($data['error']);
            }

            if (isset($data['message']) && is_string($data['message'])) {
                $errors[] = sanitize_text_field($data['message']);
            }

            return array_filter(array_unique($errors));
        }

        /**
         * Get formatted error message from response.
         *
         * Combines all errors into a single readable message.
         *
         * @param array|string $errors Either an array of errors or response array.
         * @return string Formatted error message.
         */
        public static function get_error_message($errors): string
        {
            if (is_string($errors)) {
                return sanitize_text_field($errors);
            }

            if (!is_array($errors)) {
                return __('An error occurred.', 'slk-license-checker');
            }

            if (empty($errors)) {
                return __('An error occurred.', 'slk-license-checker');
            }

            // Extract errors from response array if needed
            if (isset($errors['success']) && isset($errors['message'])) {
                return sanitize_text_field($errors['message']);
            }

            // If it's an array of error messages, combine them
            if (count($errors) === 1) {
                return sanitize_text_field(array_values($errors)[0]);
            }

            // Multiple errors - create bullet list
            $formatted_errors = [];
            foreach ($errors as $error) {
                if (is_string($error)) {
                    $formatted_errors[] = '• ' . sanitize_text_field($error);
                }
            }

            return implode("\n", $formatted_errors);
        }

        /**
         * Check if a response indicates success.
         *
         * @param array $response The response to check.
         * @return bool True if response indicates success.
         */
        public static function is_success(array $response): bool
        {
            return isset($response['success']) && (bool) $response['success'];
        }

        /**
         * Check if a response indicates failure.
         *
         * @param array $response The response to check.
         * @return bool True if response indicates failure.
         */
        public static function is_error(array $response): bool
        {
            return !self::is_success($response);
        }
    }
}
