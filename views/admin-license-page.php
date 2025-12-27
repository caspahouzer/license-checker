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
        <h1><?php esc_html_e('License Management', 'slk-license-manager'); ?></h1>

        <!-- Message Container for AJAX -->
        <div id="slk-license-message" style="display: none; margin-bottom: 15px;"></div>

        <form method="post" action="" class="slk-license-form" onsubmit="return false;">
            <?php wp_nonce_field(\SLK\LicenseChecker\LicenseAdminPage::NONCE_ACTION, \SLK\LicenseChecker\LicenseAdminPage::NONCE_FIELD); ?>

            <div class="slk-form-group">
                <label for="license_key"><?php esc_html_e('License Key', 'slk-license-manager'); ?></label>
                <div class="slk-input-group">
                    <?php
                    $is_active = ($license_status === 'active');
                    $display_key = $license_key;
                    if ($is_active && !empty($license_key)) {
                        $key_length = strlen($license_key);
                        $display_key = ($key_length > 8) ? substr($license_key, 0, 4) . str_repeat('&#9679;', $key_length - 8) . substr($license_key, -4) : str_repeat('&#9679;', $key_length);
                    }
                    ?>
                    <input type="text" id="license_key" name="license_key" class="regular-text" value="<?php echo esc_attr($display_key); ?>" placeholder="<?php esc_attr_e('Enter your license key', 'slk-license-manager'); ?>" <?php echo $is_active ? 'readonly disabled' : ''; ?> />
                    <button type="button" id="slk-activate-btn" class="button button-primary" style="<?php echo $is_active ? 'display: none;' : ''; ?>"><?php esc_html_e('Activate', 'slk-license-manager'); ?></button>
                    <button type="button" id="slk-deactivate-btn" class="button" style="<?php echo $is_active ? '' : 'display: none;'; ?>"><?php esc_html_e('Deactivate', 'slk-license-manager'); ?></button>
                    <span class="spinner slk-spinner"></span>
                </div>
            </div>

            <div class="slk-form-group">
                <label><?php esc_html_e('Status', 'slk-license-manager'); ?></label>
                <div class="slk-license-status">
                    <?php
                    $status_class = 'inactive';
                    $status_text = __('Inactive', 'slk-license-manager');
                    if ($license_status === 'active') {
                        $status_class = 'active';
                        $status_text = __('Active', 'slk-license-manager');
                    } elseif ($license_status === 'invalid') {
                        $status_class = 'invalid';
                        $status_text = __('Invalid', 'slk-license-manager');
                    }
                    ?>
                    <span class="slk-status-indicator <?php echo esc_attr($status_class); ?>"></span>
                    <span><?php echo esc_html($status_text); ?></span>
                </div>
            </div>

            <div class="slk-form-group" style="<?php echo ($license_status === 'active') ? '' : 'display: none;'; ?>">
                <label><?php esc_html_e('Activations', 'slk-license-manager'); ?></label>
                <div class="slk-license-usage">
                    <?php
                    $usage_text = 'N/A';
                    if ($license_counts && isset($license_counts['activated'], $license_counts['limit'])) {
                        $usage_text = sprintf('%d / %d', $license_counts['activated'], $license_counts['limit']);
                    }
                    echo esc_html($usage_text);
                    ?>
                </div>
            </div>
        </form>
    </div>

    <div class="slk-help-section slk-license-card">
        <h2><?php esc_html_e('Need Help?', 'slk-license-manager'); ?></h2>
        <ul>
            <li><strong><?php esc_html_e('Where can I find my license key?', 'slk-license-manager'); ?></strong><br /><?php printf(esc_html__('Your license key is available in your account on our %s.', 'slk-license-manager'), '<a href="#" target="_blank">website</a>'); ?></li>
            <li><strong><?php esc_html_e('Having trouble?', 'slk-license-manager'); ?></strong><br /><?php printf(esc_html__('Please contact our %s for assistance.', 'slk-license-manager'), '<a href="#" target="_blank">support team</a>'); ?></li>
        </ul>
    </div>
</div>
