<?php

/**
 * License Checker main controller.
 *
 * Handles license operations and data storage.
 *
 * @package SLK\LicenseChecker
 */

declare(strict_types=1);

namespace SLK\LicenseChecker;

use SLK\LicenseChecker\LemonSqueezy\LemonSqueezyStrategy;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * License Manager class.
 * 
 * Singleton class for managing license operations.
 */
if (! class_exists('SLK\LicenseChecker\LicenseChecker')) {
    class LicenseChecker
    {
        /**
         * Singleton instances - one per text domain.
         *
         * @var array<string, LicenseChecker>
         */
        private static array $instances = [];

        /**
         * License API strategy (LemonSqueezy).
         *
         * @var LemonSqueezyStrategy
         */
        private LemonSqueezyStrategy $strategy;

        /**
         * Plugin version.
         *
         * @var string
         */
        private string $version = '1.0.0';

        /**
         * Option prefix for this instance.
         *
         * @var string
         */
        private string $option_prefix;

        /**
         * Parent menu slug.
         *
         * @var string
         */
        private string $parent_slug = '';

        /**
         * Validation interval constant.
         */
        private const VALIDATION_INTERVAL = 12 * HOUR_IN_SECONDS; // 12 hours

        private static ?string $text_domain = '';

        /**
         * Private constructor to prevent direct instantiation.
         */
        private function __construct($text_domain = '')
        {
            if (empty($text_domain)) {
                throw new \InvalidArgumentException('Text domain must be provided in LicenseChecker constructor.');
            }
            // Generate option prefix from text_domain (e.g., 'my-plugin' => 'my_plugin_license_manager')
            $this->option_prefix = str_replace('-', '_', $text_domain) . '_license_checker';

            // Initialize the license strategy (LemonSqueezy or Default).
            $this->init_strategy();

            // Hook into admin_init to check license validation.
            add_action('admin_init', [$this, 'maybe_validate_license']);

            // Register AJAX actions with dynamic action name based on plugin
            $ajax_action = str_replace('_license_checker', '_manage_license', $this->option_prefix);
            add_action('wp_ajax_' . $ajax_action, [$this, 'handle_ajax_request']);

            // Enqueue scripts.
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

            // Add admin menu page - use dynamic hook based on plugin text_domain
            $admin_menu_hook = str_replace('_license_checker', '_admin_menu', $this->option_prefix);
            add_action($admin_menu_hook, [$this, 'add_admin_menu'], 10, 1);

            // Allow plugins to register the plugin file for plugins list rendering
            $register_renderer_hook = str_replace('_license_checker', '_register_renderer', $this->option_prefix);
            do_action($register_renderer_hook, $this);
        }

        /**
         * Register the plugin file for plugins list rendering.
         *
         * This method is called via a dynamic hook so that the plugin can register its plugin file
         * for rendering in the plugins list.
         *
         * @param string $plugin_file The plugin file path (e.g., 'my-plugin/my-plugin.php').
         * @return void
         */
        public function register_plugins_list_renderer(string $plugin_file): void
        {
            if (empty($plugin_file)) {
                return;
            }

            // Instantiate and register the plugins list renderer
            $renderer = new LicensePluginsListRenderer($this, $plugin_file, $this->option_prefix);
            $renderer->register_hooks();
        }

        /**
         * Get the admin menu slug for this license checker instance.
         *
         * @return string
         */
        private function get_admin_menu_slug(): string
        {
            return str_replace('_', '-', $this->option_prefix);
        }

        /**
         * Add admin menu page - orchestrates hook-based menu registration.
         *
         * @param string $parent_slug The parent menu slug.
         * @return void
         */
        public function add_admin_menu(string $parent_slug): void
        {
            $this->parent_slug = $parent_slug;

            // Trigger action for plugins to register their submenu
            // This allows external plugins to control menu registration
            do_action('slk_register_license_submenu', $parent_slug, $this);

            // Fallback: If nothing was registered, register it ourselves
            $submenu_done_hook = 'slk_register_license_submenu_done_' . $this->option_prefix;
            if (!did_action($submenu_done_hook)) {
                $this->register_submenu($parent_slug);
            }

            // Add this License menu slug to the list of items that should appear at the end
            add_filter('slk_license_manager_last_menu_items', [$this, 'add_to_last_menu_items']);

            // Move License menu to the end - hooked to admin_menu with low priority
            add_action('admin_menu', [$this, 'reorder_submenu_to_end'], 999);

            // Register active menu handler
            $this->register_active_menu_handler();
        }

        /**
         * Add this License menu item to the list of items that should appear at the end.
         *
         * @param array $items The list of last menu item slugs.
         * @return array Modified list with License menu slug added.
         */
        public function add_to_last_menu_items(array $items): array
        {
            $items[] = $this->get_admin_menu_slug();
            return $items;
        }

        /**
         * Register the submenu page (can be called from hook or directly).
         *
         * @param string $parent_slug The parent menu slug.
         * @return void
         */
        public function register_submenu(string $parent_slug): void
        {
            $menu_slug = $this->get_admin_menu_slug();
            add_submenu_page(
                $parent_slug,
                __('License', 'slk-license-checker'),
                __('License', 'slk-license-checker'),
                'manage_options',
                $menu_slug,
                [$this, 'render_license_form'],
            );

            do_action('slk_register_license_submenu_done_' . $this->option_prefix);
        }

        /**
         * Reorder submenu item to appear at the end.
         *
         * @param string $parent_slug The parent menu slug.
         * @return void
         */
        public function reorder_submenu_to_end(): void
        {
            global $submenu;
            // dd($submenu, $this->parent_slug, $this->option_prefix);
            $slug = str_replace('_', '-', $this->option_prefix);

            if (!empty($this->parent_slug) && isset($submenu[$slug])) {
                $menu_slug = $this->get_admin_menu_slug();
                $item = null;
                // Find and remove our item
                foreach ($submenu[$slug] as $key => $sub_item) {

                    if ($sub_item[2] === $menu_slug) {
                        $item = $sub_item;
                        unset($submenu[$slug][$key]);
                        break;
                    }
                }

                // Re-add it at the end
                if ($item !== null) {
                    $submenu[$slug][] = $item;
                }
            }
        }

        /**
         * Register handler for active menu highlighting.
         *
         * @return void
         */
        private function register_active_menu_handler(): void
        {
            add_action('admin_menu', function () {
                global $submenu_file;
                $current_page = $_GET['page'] ?? '';

                if ($current_page === $this->get_admin_menu_slug()) {
                    $submenu_file = $this->get_admin_menu_slug();
                }
            }, 100);  // Priority 100 - after all other hooks
        }

        /**
         * Get singleton instance for a specific text domain.
         *
         * @param string $text_domain The text domain (defaults to 'slk-license-checker').
         * @return LicenseChecker
         */
        public static function instance($text_domain = ''): LicenseChecker
        {
            self::$text_domain = $text_domain;
            if (!isset(self::$instances[$text_domain])) {
                self::$instances[$text_domain] = new self($text_domain);
            }

            return self::$instances[$text_domain];
        }

        /**
         * Initialize the license strategy.
         *
         * Uses LemonSqueezy public License API (no authentication required).
         *
         * @return void
         */
        private function init_strategy(): void
        {
            $this->strategy = new LemonSqueezyStrategy();
        }

        /**
         * Get the license key option name.
         *
         * @return string
         */
        private function get_option_key(): string
        {
            return $this->option_prefix . '_license_key';
        }

        /**
         * Get the activation hash option name.
         *
         * @return string
         */
        private function get_option_activation_hash(): string
        {
            return $this->option_prefix . '_activation_hash';
        }

        /**
         * Get the license status option name.
         *
         * @return string
         */
        private function get_option_license_status(): string
        {
            return $this->option_prefix . '_license_status';
        }

        /**
         * Get the license counts option name.
         *
         * @return string
         */
        private function get_option_license_counts(): string
        {
            return $this->option_prefix . '_license_counts';
        }

        /**
         * Get the license creds option name.
         *
         * @return string
         */
        private function get_option_license_creds(): string
        {
            return $this->option_prefix . '_license_creds';
        }

        /**
         * Get the license validation transient name.
         *
         * @return string
         */
        private function get_transient_license_validation(): string
        {
            return $this->option_prefix . '_license_validation';
        }

        /**
         * Activate a license.
         *
         * @param string $license_key The license key to activate.
         * @return array Response array with success status and message.
         */
        public function activate_license(string $license_key): array
        {
            // Call API via the configured strategy.
            $response = $this->strategy->activate_license($license_key);

            // Check if API request succeeded AND activation was successful.
            if (!$response['success']) {
                return $response;
            }

            // Verify the response contains valid data.
            if (!isset($response['data']) || empty($response['data'])) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => __('Invalid API response: No data returned.', 'slk-license-checker'),
                ];
            }

            // Check if the API response indicates success (some APIs return success:true in the response body).
            if (isset($response['data']['success']) && $response['data']['success'] === false) {
                $error_msg = isset($response['data']['message'])
                    ? $response['data']['message']
                    : __('License activation was rejected by the API.', 'slk-license-checker');

                return [
                    'success' => false,
                    'data'    => $response['data'],
                    'message' => $error_msg,
                ];
            }

            // Check for errors in the nested data structure (License Manager for WooCommerce format).
            if (isset($response['data']['data']['errors']) && !empty($response['data']['data']['errors'])) {
                // Extract error message from the errors array.
                $errors = $response['data']['data']['errors'];
                $error_msg = __('License activation failed.', 'slk-license-checker');

                // Get the first error message.
                foreach ($errors as $error_key => $error_messages) {
                    if (is_array($error_messages) && !empty($error_messages)) {
                        $error_msg = is_array($error_messages[0]) ? json_encode($error_messages[0]) : $error_messages[0];
                        break;
                    }
                }

                return [
                    'success' => false,
                    'data'    => $response['data'],
                    'message' => $error_msg,
                ];
            }

            // Store license data.
            update_option($this->get_option_key(), sanitize_text_field($license_key));
            update_option($this->get_option_license_status(), 'active');

            // Store activation hash if provided.
            if (isset($response['activation_hash'])) {
                update_option($this->get_option_activation_hash(), sanitize_text_field($response['activation_hash']));
            }

            // Update license counts.
            // If we already fetched details for the token, use that.
            if (isset($details) && $details['success'] && isset($details['data'])) {
                $this->update_license_counts($details['data']);
            } else {
                // Otherwise, fetch details now to get the counts.
                $details = $this->strategy->get_license_details($license_key);
                if ($details['success'] && isset($details['data'])) {
                    $this->update_license_counts($details['data']);
                }
            }

            // Set up automatic validation.
            $this->schedule_validation();

            return [
                'success' => true,
                'message' => __('License activated successfully.', 'slk-license-checker'),
            ];
        }

        /**
         * Get license counts.
         *
         * @return array|null Array with 'activated' and 'limit' keys, or null if not set.
         */
        public function get_license_counts(): ?array
        {
            return get_option($this->get_option_license_counts(), null);
        }

        /**
         * Update license counts from API data.
         *
         * @param array $data API response data.
         * @return void
         */
        private function update_license_counts(array $data): void
        {
            // Handle nested data structure (data.data).
            if (isset($data['data']) && is_array($data['data'])) {
                $data = $data['data'];
            }

            if (isset($data['timesActivated']) && isset($data['timesActivatedMax'])) {
                $counts = [
                    'activated' => (int) $data['timesActivated'],
                    'limit'     => (int) $data['timesActivatedMax'],
                ];
                update_option($this->get_option_license_counts(), $counts);
            }
        }

        /**
         * Deactivate a license.
         *
         * @param string $activation_hash The activation hash.
         * @return array Response array with success status and message.
         */
        public function deactivate_license(string $license_key, string $activation_hash): array
        {
            // Call API via the configured strategy.
            $response = $this->strategy->deactivate_license($license_key, $activation_hash);

            if ($response['success']) {
                // Check for API errors in nested data (LMFWC format).
                // API might return success:true but contain errors in data.
                if (isset($response['data']['data']['errors']) && !empty($response['data']['data']['errors'])) {
                    $errors = $response['data']['data']['errors'];
                    $error_msg = __('Deactivation failed.', 'slk-license-checker');

                    foreach ($errors as $error_key => $error_messages) {
                        if (is_array($error_messages) && !empty($error_messages)) {
                            $error_msg = is_array($error_messages[0]) ? json_encode($error_messages[0]) : $error_messages[0];
                            break;
                        }
                    }

                    return [
                        'success' => false,
                        'data'    => $response['data'],
                        'message' => $error_msg,
                    ];
                }

                // Clear license data.
                LicenseHelper::delete_license_data($this->option_prefix);
            }

            return $response;
        }

        /**
         * Validate a license.
         *
         * @param string $license_key The license key to validate.
         * @param bool $silent If true, preserve active status on API failure (for background checks).
         * @return array Response array with success status and message.
         */
        public function validate_license(string $license_key, bool $silent = false): array
        {
            $activation_hash = $this->get_activation_hash();
            if (empty($activation_hash)) {
                return [
                    'success' => false,
                    'data'    => null,
                    'message' => __('Activation hash is required.', 'slk-license-checker'),
                ];
            }

            // Call API via the configured strategy.
            $response = $this->strategy->validate_license($license_key, $activation_hash, $silent);

            if ($response['success']) {
                // Update status based on validation result.
                // API returns 'valid' => true if license is valid.
                $is_valid = isset($response['valid']) && $response['valid'];
                update_option($this->get_option_license_status(), $is_valid ? 'active' : 'invalid');

                // Update license counts.
                // If validation response doesn't have counts, fetch details.
                // Check both direct and nested locations.
                $has_counts = isset($response['data']['timesActivated']) || isset($response['data']['data']['timesActivated']);

                if (!$has_counts) {
                    $details = $this->strategy->get_license_details($license_key);
                    if ($details['success'] && isset($details['data'])) {
                        $this->update_license_counts($details['data']);
                    }
                } else {
                    $this->update_license_counts($response['data']);
                }

                // Set transient for automatic validation (12 hours).
                $this->schedule_validation();
            } else {
                // If silent mode (background check) and API failed, keep current status.
                if ($silent) {
                    // Reset transient to try again in 12 hours.
                    $this->schedule_validation();
                }
                // Otherwise, let the admin page handle the error display.
            }

            return $response;
        }

        /**
         * Get stored license key.
         *
         * @return string License key or empty string.
         */
        public function get_license_key(): string
        {
            return (string) get_option($this->get_option_key(), '');
        }

        /**
         * Get stored activation hash.
         *
         * @return string Activation hash or empty string.
         */
        public function get_activation_hash(): string
        {
            return (string) get_option($this->get_option_activation_hash(), '');
        }

        /**
         * Get current license status.
         *
         * @return string License status (active, inactive, invalid, or empty).
         */
        public function get_license_status(): string
        {
            return (string) get_option($this->get_option_license_status(), '');
        }

        /**
         * Check if license is active.
         *
         * Performs automatic validation every 12 hours against the LemonSqueezy API.
         * If validation is not due, returns cached status.
         *
         * @return bool True if active, false otherwise.
         */
        public static function is_active(): bool
        {
            if (empty(self::$text_domain)) {
                throw new \InvalidArgumentException('Text domain must be provided in active method.');
            }

            // Get the instance for the specified text domain
            $instance = self::instance(self::$text_domain);

            // Get current status from options
            $status = (string) get_option($instance->get_option_license_status(), '');

            // Only proceed if license is marked as active
            if ($status !== 'active') {
                return false;
            }

            // Check if validation is due (every 12 hours)
            $validation_transient = $instance->get_transient_license_validation();
            $last_validation = get_transient($validation_transient);

            // If validation transient has expired, validate against LemonSqueezy API
            if (false === $last_validation) {
                $license_key = $instance->get_license_key();

                // Only validate if we have a license key
                if (!empty($license_key)) {
                    // Validate in silent mode - preserves active status if API fails
                    $instance->validate_license($license_key, true);

                    // Get updated status after validation
                    $status = (string) get_option($instance->get_option_license_status(), '');
                }
            }

            return $status === 'active';
        }

        /**
         * Schedules the next license validation check.
         *
         * @return void
         */
        private function schedule_validation(): void
        {
            set_transient($this->get_transient_license_validation(), time(), self::VALIDATION_INTERVAL);
        }

        /**
         * Automatically validate license if transient has expired.
         *
         * This method is hooked to admin_init and runs every 12 hours.
         * If the API fails to respond, the license remains active.
         *
         * @return void
         */
        public function maybe_validate_license(): void
        {
            $license_key = $this->get_license_key();
            if (empty($license_key)) {
                return;
            }

            // Check if transient exists.
            $last_validation = get_transient($this->get_transient_license_validation());

            // If transient doesn't exist, validate the license.
            if (false === $last_validation) {
                $license_status = $this->get_license_status();

                // Only auto-validate if there's a license key and status is active.
                if (!empty($license_key) && $license_status === 'active') {
                    // Validate in silent mode - preserves active status if API fails.
                    $this->validate_license($license_key, true);
                }
            }
        }

        /**
         * Enqueue admin scripts and styles.
         *
         * @return void
         */
        public function enqueue_scripts(): void
        {
            $screen = get_current_screen();
            $expected_screen_id = $this->get_admin_menu_slug();

            // Check if we're on license settings page or plugins page
            $is_license_page = $screen && str_contains($screen->id, $expected_screen_id);
            $is_plugins_page = $screen && $screen->id === 'plugins';

            if (!$is_license_page && !$is_plugins_page) {
                return;
            }

            wp_enqueue_style(
                'slk-license-checker',
                plugin_dir_url(__FILE__) . 'assets/css/license-checker.css',
                [],
                $this->version
            );

            wp_enqueue_script(
                'slk-license-checker',
                plugin_dir_url(__FILE__) . 'assets/js/license-checker.js',
                ['jquery'],
                $this->version,
                true
            );

            // Calculate AJAX action dynamically based on plugin
            $ajax_action = str_replace('_license_checker', '_manage_license', $this->option_prefix);

            wp_localize_script('slk-license-checker', 'slk_license_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'action'   => $ajax_action,
                'nonce'    => wp_create_nonce('slk_license_nonce'),
                'status'   => $this->get_license_status(),
                'domain'   => parse_url(home_url(), PHP_URL_HOST),
                'strings'  => [
                    'enter_key'         => __('Please enter a license key.', 'slk-license-checker'),
                    'confirm_deactivate' => __('Are you sure you want to deactivate this license?', 'slk-license-checker'),
                    'network_error'     => __('Network error. Please try again.', 'slk-license-checker'),
                    'active_desc'       => __('Your license is active. Click "Deactivate" to change or remove the license.', 'slk-license-checker'),
                    'inactive_desc'     => __('Enter the license key you received after purchase.', 'slk-license-checker'),
                    'active'            => __('Active', 'slk-license-checker'),
                    'inactive'          => __('Inactive', 'slk-license-checker'),
                    'invalid'           => __('Invalid', 'slk-license-checker'),
                ],
            ]);
        }

        /**
         * Handle AJAX request for license management.
         *
         * @return void
         */
        public function handle_ajax_request(): void
        {
            // Verify nonce.
            if (!check_ajax_referer('slk_license_nonce', 'security', false)) {
                wp_send_json_error(['message' => __('Security check failed.', 'slk-license-checker')]);
            }

            // Check capabilities.
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Permission denied.', 'slk-license-checker')]);
            }

            $method = isset($_POST['method']) ? sanitize_text_field(wp_unslash($_POST['method'])) : '';

            if ($method === 'activate') {
                $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
                if (empty($license_key)) {
                    wp_send_json_error(['message' => __('License key is required.', 'slk-license-checker')]);
                }

                $response = $this->activate_license($license_key);

                if ($response['success']) {
                    // Return masked key for display
                    $key_length = strlen($license_key);
                    $masked_key = ($key_length > 8)
                        ? substr($license_key, 0, 4) . str_repeat('*', $key_length - 8) . substr($license_key, -4)
                        : str_repeat('*', $key_length);

                    // Get counts
                    $counts = $this->get_license_counts();
                    $usage = $counts ? sprintf('%d / %d', $counts['activated'], $counts['limit']) : '';

                    wp_send_json_success([
                        'message'    => __('License activated successfully!', 'slk-license-checker'),
                        'masked_key' => $masked_key,
                        'usage'      => $usage
                    ]);
                } else {
                    wp_send_json_error(['message' => $response['message']]);
                }
            } elseif ($method === 'deactivate') {
                $activation_hash = $this->get_activation_hash();

                if (empty($activation_hash)) {
                    wp_send_json_error(['action' => 'deactivate', 'message' => __('No activation hash found.', 'slk-license-checker')]);
                }

                $license_key = $this->get_license_key();
                if (!$license_key) {
                    wp_send_json_error(['action' => 'deactivate', 'message' => __('No license key found.', 'slk-license-checker')]);
                }

                $response = $this->deactivate_license($license_key, $activation_hash);

                if ($response['success']) {
                    wp_send_json_success([
                        'message'     => __('License deactivated successfully!', 'slk-license-checker'),
                        'license_key' => $this->get_license_key() // Return full key so user can edit it
                    ]);
                } else {
                    wp_send_json_error(['message' => $response['message']]);
                }
            } elseif ($method === 'check_status') {
                $license_key = $this->get_license_key();

                if (empty($license_key)) {
                    wp_send_json_error(['action' => 'check_status', 'message' => __('No license key found.', 'slk-license-checker')]);
                }

                // Force validation (silent=true so we don't deactivate on network error, but we DO update on API result).
                $response = $this->validate_license($license_key, true);

                // Get fresh status and counts.
                $status = $this->get_license_status();
                $counts = $this->get_license_counts();
                $usage = $counts ? sprintf('%d / %d', $counts['activated'], $counts['limit']) : '';

                wp_send_json_success([
                    'status' => $status,
                    'usage'  => $usage,
                    'message' => ($status === 'active')
                        ? __('License is active.', 'slk-license-checker')
                        : __('License is inactive.', 'slk-license-checker')
                ]);
            } else {
                wp_send_json_error(['message' => __('Invalid method.', 'slk-license-checker')]);
            }
        }

        /**
         * Render the license form.
         *
         * This method is called from the settings page.
         *
         * @return void
         */
        public function render_license_form(): void
        {
            $admin_page = new LicenseAdminPage();
            $admin_page->render($this);
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
