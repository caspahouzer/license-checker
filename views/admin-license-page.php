<?php

/**
 * License Management Admin View Template.
 *
 * This template is loaded by License_Admin_Page::render().
 *
 * @package SLK\LicenseChecker
 * @var string $license_key       Current license key.
 * @var string $license_status    Current license status.
 * @var string $notice            Admin notice message.
 * @var string $notice_type       Admin notice type.
 */


// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap slk-license-page">
    <div class="slk-license-card">
        <h1><?php esc_html_e('License Management', 'slk-license-checker'); ?></h1>

        <!-- Message Container for AJAX -->
        <div id="slk-license-message" style="display: none; margin-bottom: 15px;"></div>

        <form method="post" action="" class="slk-license-form" onsubmit="return false;">
            <?php wp_nonce_field(\SLK\LicenseChecker\LicenseAdminPage::NONCE_ACTION, \SLK\LicenseChecker\LicenseAdminPage::NONCE_FIELD); ?>

            <div class="slk-form-group">
                <label for="license_key"><?php esc_html_e('License Key', 'slk-license-checker'); ?></label>
                <div class="slk-input-group">
                    <?php
                    $is_active = ($license_status === 'active');
                    $display_key = $license_key;
                    if ($is_active && !empty($license_key)) {
                        $key_length = strlen($license_key);
                        $display_key = ($key_length > 8) ? substr($license_key, 0, 4) . str_repeat('&#9679;', $key_length - 8) . substr($license_key, -4) : str_repeat('&#9679;', $key_length);
                    }
                    ?>
                    <input type="text" id="license_key" name="license_key" class="regular-text" value="<?php echo esc_attr($display_key); ?>" placeholder="<?php esc_attr_e('Enter your license key', 'slk-license-checker'); ?>" <?php echo $is_active ? 'readonly disabled' : ''; ?> />
                    <button type="button" id="slk-activate-btn" class="button button-primary" style="<?php echo $is_active ? 'display: none;' : ''; ?>"><?php esc_html_e('Activate', 'slk-license-checker'); ?></button>
                    <button type="button" id="slk-deactivate-btn" class="button" style="<?php echo $is_active ? '' : 'display: none;'; ?>"><?php esc_html_e('Deactivate', 'slk-license-checker'); ?></button>
                    <span class="spinner slk-spinner"></span>
                </div>
            </div>

            <div class="slk-form-group">
                <label><?php esc_html_e('Status', 'slk-license-checker'); ?></label>
                <div class="slk-license-status">
                    <?php
                    $status_class = 'inactive';
                    $status_text = __('Inactive', 'slk-license-checker');
                    if ($license_status === 'active') {
                        $status_class = 'active';
                        $status_text = __('Active', 'slk-license-checker');
                    } elseif ($license_status === 'invalid') {
                        $status_class = 'invalid';
                        $status_text = __('Invalid', 'slk-license-checker');
                    }
                    ?>
                    <span class="slk-status-indicator <?php echo esc_attr($status_class); ?>"></span>
                    <span class="slk-license-status-text"><?php echo esc_html($status_text); ?></span>
                </div>
            </div>

            <div class="slk-form-group" style="<?php echo ($license_status === 'active') ? '' : 'display: none;'; ?>">
                <label><?php esc_html_e('Activations', 'slk-license-checker'); ?></label>
                <div class="slk-license-usage">
                    <?php
                    $usage_text = esc_html__('N/A', 'slk-license-checker');
                    if ($license_counts && isset($license_counts['activated'], $license_counts['limit'])) {
                        /* translators: %1$d: number of activated sites, %2$d: total license limit */
                        $usage_text = sprintf(esc_html__('%1$d / %2$d', 'slk-license-checker'), $license_counts['activated'], $license_counts['limit']);
                    }
                    echo esc_html($usage_text);
                    ?>
                </div>
            </div>
        </form>
    </div>
</div>
