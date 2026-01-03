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
         * Log debug messages.
         *
         * @param string $message The message to log.
         * @param mixed  $data    Optional data to log.
         * @return void
         */
        public static function log(string $message, $data = null): void
        {
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
