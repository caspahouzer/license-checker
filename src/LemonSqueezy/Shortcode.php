<?php

/**
 * LemonSqueezy Shortcode.
 *
 * Handles the [lemonsqueezy_checkout] shortcode.
 *
 * @package SLK\LicenseChecker\LemonSqueezy
 */

declare(strict_types=1);

namespace SLK\LicenseChecker\LemonSqueezy;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('SLK\LicenseChecker\LemonSqueezy\Shortcode')) {
    class Shortcode
    {
        /**
         * Initialize the hooks.
         */
        public function init(): void
        {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_shortcode('lemonsqueezy_checkout', [$this, 'render_shortcode']);
        }

        /**
         * Enqueue Lemon.js.
         */
        public function enqueue_scripts(): void
        {
            wp_enqueue_script('lemonsqueezy', 'https://app.lemonsqueezy.com/js/lemon.js', [], null, true);
        }

        /**
         * Render the checkout button shortcode.
         *
         * @param array $atts Shortcode attributes.
         * @return string The shortcode output.
         */
        public function render_shortcode($atts): string
        {
            $atts = shortcode_atts(
                [
                    'variant_id' => '',
                    'text'       => 'Purchase',
                ],
                $atts,
                'lemonsqueezy_checkout'
            );

            if (empty($atts['variant_id'])) {
                return '<!-- Lemon Squeezy: No variant ID provided -->';
            }

            $url = sprintf('https://slkcommunications.lemonsqueezy.com/checkout/buy/%s', esc_attr($atts['variant_id']));

            return sprintf(
                '<a href="#" onclick="LemonSqueezy.Url.Open(\'%s\'); return false;" class="lemonsqueezy-button">%s</a>',
                $url,
                esc_html($atts['text'])
            );
        }
    }
}
