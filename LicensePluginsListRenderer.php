<?php

/**
 * License Plugins List Renderer.
 *
 * Handles rendering of license management UI in the WordPress plugins list.
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
 * License Plugins List Renderer class.
 *
 * Renders license status and inline license management fields in the plugins list.
 */
class LicensePluginsListRenderer
{
    /**
     * The LicenseChecker instance.
     *
     * @var LicenseChecker
     */
    private LicenseChecker $license_checker;

    /**
     * The plugin file path.
     *
     * @var string
     */
    private string $plugin_file;

    /**
     * The text domain.
     *
     * @var string
     */
    private string $text_domain;

    /**
     * Constructor.
     *
     * @param LicenseChecker $license_checker The LicenseChecker instance.
     * @param string         $plugin_file     The plugin file path.
     * @param string         $text_domain     The text domain.
     */
    public function __construct(LicenseChecker $license_checker, string $plugin_file, string $text_domain)
    {
        $this->license_checker = $license_checker;
        $this->plugin_file = $plugin_file;
        $this->text_domain = $text_domain;
    }

    /**
     * Register hooks for the plugins list.
     *
     * @return void
     */
    public function register_hooks(): void
    {
        add_action('after_plugin_row_' . $this->plugin_file, [$this, 'render_license_row']);
    }

    /**
     * Render the license row in the plugins list.
     *
     * This method is called by the after_plugin_row_{$plugin} hook.
     *
     * @return void
     */
    public function render_license_row(): void
    {
        $license_key = $this->license_checker->get_license_key();
        $license_status = $this->license_checker->get_license_status();
        $license_counts = $this->license_checker->get_license_counts();

        // Determine the status class and text.
        $status_class = empty($license_key) ? 'inactive' : ($license_status === 'active' ? 'active' : 'invalid');
        $status_text = $this->get_status_text($status_class);

        // Prepare data attributes for JavaScript.
        $ajax_action = str_replace('_license_checker', '_manage_license', $this->text_domain . '_license_checker');
        $nonce = wp_create_nonce('slk_license_nonce');

        // Render the HTML row.
        $allowed_html = [
            'tr' => [
                'class' => [],
                'data-text-domain' => [],
                'data-plugin-file' => [],
                'data-nonce' => [],
                'data-ajax-action' => [],
            ],
            'td' => [
                'colspan' => [],
            ],
            'div' => [
                'class' => [],
            ],
            'span' => [
                'class' => [],
                'title' => [],
                'style' => [],
            ],
            'input' => [
                'type' => [],
                'class' => [],
                'placeholder' => [],
                'value' => [],
            ],
            'button' => [
                'type' => [],
                'class' => [],
                'title' => [],
            ],
        ];
        echo wp_kses($this->render_html($status_class, $license_key, $license_counts, $ajax_action, $nonce, $status_text), $allowed_html);
    }

    /**
     * Get the status text based on the status class.
     *
     * @param string $status_class The status class.
     * @return string The status text.
     */
    private function get_status_text(string $status_class): string
    {
        switch ($status_class) {
            case 'active':
                return __('Active', 'slk-license-checker');
            case 'invalid':
                return __('Invalid', 'slk-license-checker');
            default:
                return __('Inactive', 'slk-license-checker');
        }
    }

    /**
     * Render the HTML for the license row.
     *
     * @param string $status_class The status class.
     * @param string $license_key  The license key.
     * @param array  $license_counts The license counts (activated/limit).
     * @param string $ajax_action  The AJAX action name.
     * @param string $nonce        The nonce.
     * @param string $status_text  The status text.
     * @return string The HTML markup.
     */
    private function render_html(
        string $status_class,
        string $license_key,
        ?array $license_counts,
        string $ajax_action,
        string $nonce,
        string $status_text
    ): string {
        $is_active = $status_class === 'active';
        $display_key = '';

        // Mask the key if active.
        if ($is_active && !empty($license_key)) {
            $key_length = strlen($license_key);
            $display_key = ($key_length > 8)
                ? substr($license_key, 0, 4) . str_repeat('*', $key_length - 8) . substr($license_key, -4)
                : str_repeat('*', $key_length);
        }

        // Build usage text.
        $usage_text = '';
        if ($is_active && $license_counts) {
            $usage_text = sprintf(
                __('%1$d / %2$d', 'slk-license-checker'),
                $license_counts['activated'],
                $license_counts['limit']
            );
        }

        // Build the HTML.
        $html = '<tr class="slk-license-row slk-license-status-' . esc_attr($status_class) . '" data-text-domain="' . esc_attr($this->text_domain) . '" data-plugin-file="' . esc_attr($this->plugin_file) . '" data-nonce="' . esc_attr($nonce) . '" data-ajax-action="' . esc_attr($ajax_action) . '">';
        $html .= '<td colspan="3">';
        $html .= '<div class="slk-license-inline-manager">';

        // Status badge.
        $html .= '<span class="slk-status-badge slk-status-' . esc_attr($status_class) . '" title="' . esc_attr($status_text) . '"></span>';

        // License key input.
        if ($is_active) {
            $html .= '<div class="slk-license-key-display">';
            $html .= '<span class="slk-license-key-masked">' . esc_html($display_key) . '</span>';
            $html .= '</div>';
        } else {
            $html .= '<input type="text" class="slk-inline-license-key" placeholder="' . esc_attr__('Enter your license key', 'slk-license-checker') . '" value="' . esc_attr($license_key) . '" />';
        }

        // Deactivate button (only show if active).
        if ($is_active) {
            $html .= '<button type="button" class="slk-deactivate-link button-link" title="' . esc_attr__('Deactivate this license', 'slk-license-checker') . '">' . esc_html__('Deactivate', 'slk-license-checker') . '</button>';
        }

        // Usage text (only show if active).
        if ($is_active && !empty($usage_text)) {
            $html .= '<span class="slk-usage">' . esc_html($usage_text) . '</span>';
        }

        // Spinner (hidden by default).
        $html .= '<span class="slk-spinner spinner" style="display: none; float: none; margin: 0 5px;"></span>';

        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }
}
