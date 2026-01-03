jQuery(document).ready(function ($) {
    const $licenseInput = $('#license_key');
    const $activateBtn = $('#slk-activate-btn');
    const $deactivateBtn = $('#slk-deactivate-btn');
    const $messageContainer = $('#slk-license-message');
    const $spinner = $('.slk-spinner');

    function showMessage(message, type) {
        $messageContainer.html('<div class="notice notice-' + type + ' inline"><p>' + message + '</p></div>').show();
    }

    function clearMessage() {
        $messageContainer.hide().empty();
    }

    function toggleLoading(isLoading) {
        if (isLoading) {
            $spinner.addClass('is-active');
            $activateBtn.prop('disabled', true);
            $deactivateBtn.prop('disabled', true);
        } else {
            $spinner.removeClass('is-active');
            $activateBtn.prop('disabled', false);
            $deactivateBtn.prop('disabled', false);
        }
    }

    // Handle Activation
    $activateBtn.on('click', function (e) {
        e.preventDefault();
        clearMessage();

        const licenseKey = $licenseInput.val().trim();
        if (!licenseKey) {
            showMessage(slk_license_vars.strings.enter_key, 'error');
            return;
        }

        toggleLoading(true);

        $.ajax({
            url: slk_license_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: slk_license_vars.action,
                security: slk_license_vars.nonce,
                method: 'activate',
                license_key: licenseKey,
                domain: slk_license_vars.domain
            },
            success: function (response) {
                toggleLoading(false);
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Update UI
                    $licenseInput.val(response.data.masked_key).prop('readonly', true).prop('disabled', true).css('background-color', '#f0f0f1');
                    $activateBtn.hide();
                    $deactivateBtn.show();
                    $('.slk-license-status-text').text(slk_license_vars.strings.active).removeClass('slk-status-inactive').addClass('slk-status-active');
                    $('.slk-license-status-icon').removeClass('dashicons-minus').addClass('dashicons-yes').css('color', 'green');
                    $('.slk-status-indicator').removeClass('inactive invalid').addClass('active');

                    // Update description text
                    $('.description').text(slk_license_vars.strings.active_desc);

                    // Update usage and show row
                    if (response.data.usage) {
                        $('.slk-license-usage').text(response.data.usage);
                        $('.slk-activations-row').show();
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                toggleLoading(false);
                showMessage(slk_license_vars.strings.network_error, 'error');
            }
        });
    });

    // Handle Deactivation
    $deactivateBtn.on('click', function (e) {
        e.preventDefault();
        clearMessage();

        if (!confirm(slk_license_vars.strings.confirm_deactivate)) {
            return;
        }

        toggleLoading(true);

        $.ajax({
            url: slk_license_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: slk_license_vars.action,
                security: slk_license_vars.nonce,
                method: 'deactivate'
            },
            success: function (response) {
                toggleLoading(false);
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Update UI
                    $licenseInput.val(response.data.license_key).prop('readonly', false).prop('disabled', false).css('background-color', '');
                    $deactivateBtn.hide();
                    $activateBtn.show();
                    $('.slk-license-status-text').text(slk_license_vars.strings.inactive).removeClass('slk-status-active').addClass('slk-status-inactive');
                    $('.slk-license-status-icon').removeClass('dashicons-yes').addClass('dashicons-minus').css('color', '#999');
                    $('.slk-status-indicator').removeClass('active invalid').addClass('inactive');

                    // Update description text
                    $('.description').text(slk_license_vars.strings.inactive_desc);

                    // Hide activations row
                    $('.slk-activations-row').hide();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                toggleLoading(false);
                showMessage(slk_license_vars.strings.network_error, 'error');
            }
        });
    });

    // Check status on load if active
    if (slk_license_vars.status === 'active') {
        $.ajax({
            url: slk_license_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: slk_license_vars.action,
                security: slk_license_vars.nonce,
                method: 'check_status'
            },
            success: function (response) {
                if (response.success) {
                    // Update usage
                    if (response.data.usage) {
                        $('.slk-license-usage').text(response.data.usage);
                        $('.slk-activations-row').show();
                    }

                    // If status changed to inactive (e.g. revoked), update UI
                    if (response.data.status !== 'active') {
                        var newStatus = response.data.status;

                        // Reset UI to inactive state
                        $licenseInput.val('').prop('readonly', false).prop('disabled', false).css('background-color', '');
                        $deactivateBtn.hide();
                        $activateBtn.show();
                        $('.slk-license-status-text').text(slk_license_vars.strings[newStatus] || slk_license_vars.strings.inactive).removeClass('slk-status-active').addClass('slk-status-inactive');
                        $('.slk-license-status-icon').removeClass('dashicons-yes').addClass('dashicons-minus').css('color', '#999');
                        $('.slk-status-indicator').removeClass('active').addClass(newStatus);
                        $('.description').text(slk_license_vars.strings.inactive_desc);
                        $('.slk-activations-row').hide();

                        showMessage(slk_license_vars.strings.inactive_desc, 'warning');
                    }
                }
            }
        });
    }

    /* ========== PLUGINS LIST INLINE LICENSE MANAGEMENT ========== */

    // Handle inline activation - Enter key in license input
    $(document).on('keypress', '.slk-inline-license-key', function (e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            handleInlineActivate($(this));
        }
    });

    // Handle inline deactivation - Click deactivate button
    $(document).on('click', '.slk-deactivate-link', function (e) {
        e.preventDefault();
        handleInlineDeactivate($(this));
    });

    /**
     * Handle inline license activation.
     *
     * @param {jQuery} $input The license key input element.
     */
    function handleInlineActivate($input) {
        const $row = $input.closest('.slk-license-row');
        const licenseKey = $input.val().trim();

        if (!licenseKey) {
            alert(slk_license_vars.strings.enter_key);
            return;
        }

        const $spinner = $row.find('.slk-spinner');
        const ajaxAction = $row.data('ajax-action');
        const nonce = $row.data('nonce');

        // Show spinner
        $spinner.show();
        $input.prop('disabled', true);

        $.ajax({
            url: slk_license_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: ajaxAction,
                security: nonce,
                method: 'activate',
                license_key: licenseKey
            },
            success: function (response) {
                $spinner.hide();
                $input.prop('disabled', false);

                if (response.success) {
                    // Reload the page to show updated license status
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    alert(response.data.message || slk_license_vars.strings.network_error);
                }
            },
            error: function () {
                $spinner.hide();
                $input.prop('disabled', false);
                alert(slk_license_vars.strings.network_error);
            }
        });
    }

    /**
     * Handle inline license deactivation.
     *
     * @param {jQuery} $button The deactivate button element.
     */
    function handleInlineDeactivate($button) {
        if (!confirm(slk_license_vars.strings.confirm_deactivate)) {
            return;
        }

        const $row = $button.closest('.slk-license-row');
        const ajaxAction = $row.data('ajax-action');
        const nonce = $row.data('nonce');
        const $spinner = $row.find('.slk-spinner');

        // Show spinner
        $spinner.show();
        $button.prop('disabled', true);

        $.ajax({
            url: slk_license_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: ajaxAction,
                security: nonce,
                method: 'deactivate'
            },
            success: function (response) {
                $spinner.hide();
                $button.prop('disabled', false);

                if (response.success) {
                    // Reload the page to show updated license status
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    alert(response.data.message || slk_license_vars.strings.network_error);
                }
            },
            error: function () {
                $spinner.hide();
                $button.prop('disabled', false);
                alert(slk_license_vars.strings.network_error);
            }
        });
    }
});
