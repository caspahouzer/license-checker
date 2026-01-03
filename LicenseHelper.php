<?php

/**
 * License Helper for license data management.
 *
 * @package SLK\LicenseChecker
 */

declare(strict_types=1);

namespace SLK\LicenseChecker;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * License Helper class.
 *
 * Provides utility methods for license data management.
 */
class LicenseHelper
{
    /**
     * Delete all license data.
     *
     * @param string $option_prefix The option prefix for this license instance.
     * @return void
     */
    public static function delete_license_data(string $option_prefix = ''): void
    {
        // If no prefix provided, use the default SLK license manager prefix
        if (empty($option_prefix)) {
            $option_prefix = 'slk_license_manager';
        }

        delete_option($option_prefix . '_license_key');
        delete_option($option_prefix . '_activation_hash');
        delete_option($option_prefix . '_license_status');
        delete_option($option_prefix . '_license_counts');
        delete_transient($option_prefix . '_license_validation');
    }
}
