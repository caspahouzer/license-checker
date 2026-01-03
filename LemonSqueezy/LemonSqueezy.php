<?php

/**
 * LemonSqueezy Configuration Manager.
 *
 * Manages LemonSqueezy API configuration and API key storage.
 *
 * @package SLK\LicenseChecker\LemonSqueezy
 */

declare(strict_types=1);

namespace SLK\LicenseChecker\LemonSqueezy;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * LemonSqueezy Configuration Manager class.
 *
 * Singleton class for managing LemonSqueezy API configuration.
 * Stores and retrieves the LemonSqueezy API key from WordPress options.
 */
if (! class_exists('SLK\LicenseChecker\LemonSqueezy\LemonSqueezy')) {
    class LemonSqueezy
    {
        /**
         * Singleton instance.
         *
         * @var self|null
         */
        private static ?self $instance = null;

        /**
         * WordPress option key for storing the API key.
         *
         * @var string
         */
        private const OPTION_API_KEY = 'slk_lemonsqueezy_api_key';

        /**
         * LemonSqueezy API base URL.
         *
         * @var string
         */
        public const API_BASE_URL = 'https://api.lemonsqueezy.com/v1';

        /**
         * API rate limit (requests per minute).
         *
         * @var int
         */
        public const RATE_LIMIT = 60;

        /**
         * HTTP request timeout in seconds.
         *
         * @var int
         */
        public const REQUEST_TIMEOUT = 10;

        /**
         * Private constructor to prevent direct instantiation.
         */
        private function __construct() {}

        /**
         * Get the singleton instance.
         *
         * @return self
         */
        public static function instance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Check if LemonSqueezy is configured.
         *
         * @return bool True if API key is set, false otherwise.
         */
        public function is_configured(): bool
        {
            return !empty($this->get_api_key());
        }

        /**
         * Get the stored API key.
         *
         * @return string The API key, or empty string if not set.
         */
        public function get_api_key(): string
        {
            $api_key = get_option(self::OPTION_API_KEY, '');

            return is_string($api_key) ? trim($api_key) : '';
        }

        /**
         * Set the API key.
         *
         * @param string $api_key The API key to store.
         * @return bool True if the option was updated, false otherwise.
         */
        public function set_api_key(string $api_key): bool
        {
            $api_key = sanitize_text_field(trim($api_key));

            if (empty($api_key)) {
                return delete_option(self::OPTION_API_KEY);
            }

            return update_option(self::OPTION_API_KEY, $api_key);
        }

        /**
         * Delete the API key.
         *
         * @return bool True if the option was deleted, false otherwise.
         */
        public function delete_api_key(): bool
        {
            return delete_option(self::OPTION_API_KEY);
        }

        /**
         * Log debug messages.
         *
         * @param string $message The message to log.
         * @param mixed  $data    Optional data to log.
         * @return void
         */
        public static function log(string $message, $data = null): void
        {
            if (!defined('SLK_DEBUG') || !SLK_DEBUG) {
                return;
            }

            $log_message = '[SLK LemonSqueezy] ' . $message;

            if ($data !== null) {
                $log_message .= ' | Data: ' . wp_json_encode($data);
            }

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($log_message);
        }

        /**
         * Prevent cloning.
         *
         * @return void
         */
        private function __clone() {}

        /**
         * Prevent unserialization.
         *
         * @return void
         */
        public function __wakeup(): void
        {
            throw new \Exception('Cannot unserialize singleton');
        }
    }
}
