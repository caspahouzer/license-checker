<?php

/**
 * LemonSqueezy License API HTTP Client.
 *
 * Handles HTTP communication with the public LemonSqueezy License API.
 * No authentication required - uses only license_key as parameter.
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
 * LemonSqueezy License API HTTP Client class.
 *
 * Provides methods for communicating with the public LemonSqueezy License API.
 * No authentication required - suitable for client-side license management.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\API\LemonSqueezyClient')) {
    class LemonSqueezyClient
    {
        /**
         * LemonSqueezy API base URL.
         *
         * @var string
         */
        private const API_BASE_URL = 'https://api.lemonsqueezy.com/v1';

        /**
         * HTTP request timeout in seconds.
         *
         * @var int
         */
        private const REQUEST_TIMEOUT = 10;

        /**
         * Make an HTTP request to the LemonSqueezy License API.
         *
         * @param string $endpoint The API endpoint (e.g., 'licenses/activate').
         * @param string $method   The HTTP method (GET, POST, etc).
         * @param array  $data     The request parameters.
         * @return array Normalized response array.
         */
        public function request(string $endpoint, string $method = 'POST', array $data = []): array
        {
            $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/');

            // Log the request.
            $this->log('Making API request', ['url' => $url, 'method' => $method, 'data' => $data]);

            // Prepare request arguments.
            $args = [
                'method'  => strtoupper($method),
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ];

            // Add body and headers for POST/PATCH/PUT requests.
            if (!empty($data) && in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
                $args['headers']['Content-Type'] = 'application/json';
                $args['body'] = wp_json_encode($data);
            } elseif (!empty($data) && strtoupper($method) === 'GET') {
                // For GET, add params as query string.
                $url = add_query_arg($data, $url);
            }

            // Make the request.
            $response = wp_remote_request($url, $args);

            // Handle WP_Error.
            if (is_wp_error($response)) {
                $this->log('API request failed with WP_Error', [
                    'error_message' => $response->get_error_message(),
                    'error_code'    => $response->get_error_code(),
                ]);

                return [
                    'success' => false,
                    'data'    => null,
                    'message' => $response->get_error_message(),
                ];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            $this->log('API response received', [
                'status_code' => $response_code,
                'body_length' => strlen($response_body),
            ]);

            // Parse JSON response.
            if (empty($response_body)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => __('Empty response from API.', 'slk-license-checker'),
                ];
            }

            $data = json_decode($response_body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log('JSON decode failed', [
                    'error'       => json_last_error_msg(),
                    'body_sample' => substr($response_body, 0, 200),
                ]);

                return [
                    'success' => false,
                    'data'    => null,
                    'message' => __('Failed to parse API response.', 'slk-license-checker'),
                ];
            }

            // Handle HTTP error status codes (4xx, 5xx).
            if ($response_code < 200 || $response_code >= 300) {
                return $this->handle_error_response($response_code, $data);
            }

            // Successful response.
            $this->log('API request successful', ['response_code' => $response_code]);

            return [
                'success' => true,
                'data'    => $data,
                'message' => __('API request successful.', 'slk-license-checker'),
            ];
        }

        /**
         * Handle HTTP error responses from the API.
         *
         * @param int   $status_code The HTTP status code.
         * @param array $data        The parsed response body.
         * @return array Normalized error response.
         */
        private function handle_error_response(int $status_code, ?array $data): array
        {
            $error_message = __('API request failed.', 'slk-license-checker');

            // Try to extract error message from response.
            if (is_array($data)) {
                if (isset($data['error'])) {
                    $error_message = sanitize_text_field($data['error']);
                } elseif (isset($data['message'])) {
                    $error_message = sanitize_text_field($data['message']);
                }
            }

            $this->log('API returned error status', [
                'code'    => $status_code,
                'message' => $error_message,
            ]);

            return [
                'success' => false,
                'data'    => $data,
                'message' => $error_message,
            ];
        }

        /**
         * Log debug messages if SLK_DEBUG is enabled.
         *
         * @param string $message The message to log.
         * @param mixed  $data    Optional data to log.
         * @return void
         */
        private function log(string $message, $data = null): void
        {
            if (!defined('SLK_DEBUG') || !SLK_DEBUG) {
                return;
            }

            $log_message = '[SLK LemonSqueezy Client] ' . $message;

            if ($data !== null) {
                $log_message .= ' | Data: ' . wp_json_encode($data);
            }

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($log_message);
        }
    }
}
