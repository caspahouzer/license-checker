<?php

/**
 * Logging Utility for License Operations.
 *
 * Centralizes logging logic for license operations.
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
 * Logger class.
 *
 * Provides centralized logging for license operations.
 */
if (! class_exists('SLK\LicenseChecker\Utilities\Logger')) {
    class Logger
    {
        /**
         * Log a debug message.
         *
         * Only logs if SLK_DEBUG constant is defined and true.
         *
         * @param string $message The message to log.
         * @param mixed  $data    Optional data to include in the log.
         * @param string $context Optional context prefix for the log message.
         * @return void
         */
        public static function debug(string $message, $data = null, string $context = 'SLK LicenseChecker'): void
        {
            if (!defined('SLK_DEBUG') || !SLK_DEBUG) {
                return;
            }

            self::log($message, $data, $context);
        }

        /**
         * Log an error message.
         *
         * Always logs error messages, regardless of SLK_DEBUG setting.
         *
         * @param string $message The error message to log.
         * @param mixed  $data    Optional data to include in the log.
         * @param string $context Optional context prefix for the log message.
         * @return void
         */
        public static function error(string $message, $data = null, string $context = 'SLK LicenseChecker'): void
        {
            self::log($message, $data, $context, true);
        }

        /**
         * Internal logging method.
         *
         * @param string $message     The message to log.
         * @param mixed  $data        Optional data to include.
         * @param string $context     Context prefix for the log message.
         * @param bool   $force_error Force logging even if SLK_DEBUG is false.
         * @return void
         */
        private static function log(string $message, $data = null, string $context = 'SLK LicenseChecker', bool $force_error = false): void
        {
            $log_message = '[' . $context . '] ' . $message;

            if ($data !== null) {
                $log_message .= ' | Data: ' . wp_json_encode($data);
            }

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($log_message);
        }
    }
}
