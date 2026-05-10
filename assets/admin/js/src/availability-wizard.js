/**
 * CommonBooking Availability Wizard
 *
 * Handles:
 *  - Step navigation (show/hide .cb-wizard-step divs, update progress indicator)
 *  - "Create new" toggle for inline Item/Location forms
 *  - AJAX for inline Item creation  (action: cb_quick_create_post)
 *  - AJAX for inline Location creation (action: cb_quick_create_post)
 *  - Final wizard submit AJAX (action: cb_create_timeframe_wizard)
 */
(function ($) {
    'use strict';

    // ---------------------------------------------------------------------------
    // State
    // ---------------------------------------------------------------------------
    var currentStep = 1;

    // IDs chosen in each step (item_id, location_id come from selects).
    // The selects hold the final values so we just read them on submit.

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------
    function showStep(step) {
        $('.cb-wizard-step').hide();
        $('#cb-wizard-step-' + step).show();
        currentStep = step;

        // Update progress indicators.
        $('.cb-wizard-step-indicator').removeClass('cb-wizard-active cb-wizard-done');
        for (var i = 1; i < step; i++) {
            $('[data-step="' + i + '"]').addClass('cb-wizard-done');
        }
        $('[data-step="' + step + '"]').addClass('cb-wizard-active');
    }

    function showNotice(message, type) {
        var $notice = $('#cb-wizard-notice');
        // type = 'success' | 'error' | 'warning'
        $notice
            .removeClass('notice-success notice-error notice-warning notice-info')
            .addClass('notice-' + (type || 'error'))
            .html('<p>' + message + '</p>')
            .show();
        $('html, body').animate({ scrollTop: $notice.offset().top - 32 }, 200);
    }

    function clearNotice() {
        $('#cb-wizard-notice').hide().html('');
    }

    // ---------------------------------------------------------------------------
    // Step navigation
    // ---------------------------------------------------------------------------
    $(document).on('click', '#cb-wizard-step1-next', function () {
        clearNotice();
        var itemId = $('#cb-wizard-item-select').val();
        if (!itemId) {
            showNotice(cbWizardData.i18n.item_required, 'error');
            return;
        }
        showStep(2);
    });

    $(document).on('click', '#cb-wizard-step2-back', function () {
        clearNotice();
        showStep(1);
    });

    $(document).on('click', '#cb-wizard-step2-next', function () {
        clearNotice();
        var locationId = $('#cb-wizard-location-select').val();
        if (!locationId) {
            showNotice(cbWizardData.i18n.location_required, 'error');
            return;
        }
        showStep(3);
    });

    $(document).on('click', '#cb-wizard-step3-back', function () {
        clearNotice();
        showStep(2);
    });

    // ---------------------------------------------------------------------------
    // Toggle inline creation forms
    // ---------------------------------------------------------------------------
    $(document).on('click', '#cb-wizard-item-toggle-create', function () {
        var $form = $('#cb-wizard-item-create-form');
        $form.toggle();
        var showing = $form.is(':visible');
        $(this).text(
            showing
                ? cbWizardData.i18n.cancel_create
                : cbWizardData.i18n.create_new_item
        );
    });

    $(document).on('click', '#cb-wizard-location-toggle-create', function () {
        var $form = $('#cb-wizard-location-create-form');
        $form.toggle();
        var showing = $form.is(':visible');
        $(this).text(
            showing
                ? cbWizardData.i18n.cancel_create
                : cbWizardData.i18n.create_new_location
        );
    });

    // ---------------------------------------------------------------------------
    // Full-day checkbox: show/hide time-slot row
    // ---------------------------------------------------------------------------
    $(document).on('change', '#cb-wizard-tf-full-day', function () {
        if ($(this).is(':checked')) {
            $('#cb-wizard-tf-time-row').hide();
        } else {
            $('#cb-wizard-tf-time-row').show();
        }
    });

    // ---------------------------------------------------------------------------
    // Inline Item creation AJAX
    // ---------------------------------------------------------------------------
    $(document).on('click', '#cb-wizard-item-create-submit', function () {
        var title = $.trim($('#cb-wizard-item-title').val());
        if (!title) {
            $('#cb-wizard-item-create-error').text(cbWizardData.i18n.title_required).show();
            return;
        }
        $('#cb-wizard-item-create-error').hide();

        var $spinner = $('#cb-wizard-item-create-spinner');
        $spinner.addClass('is-active');
        $(this).prop('disabled', true);

        $.post(cbWizardData.ajax_url, {
            action: 'cb_quick_create_post',
            _ajax_nonce: cbWizardData.nonce,
            post_type: 'cb_item',
            post_title: title,
        })
            .done(function (response) {
                if (response.success) {
                    var id = response.data.id;
                    var name = response.data.title;

                    // Append to select and auto-select.
                    var $select = $('#cb-wizard-item-select');
                    $('<option>', { value: id, text: name }).appendTo($select);
                    $select.val(id);

                    // Hide the inline form.
                    $('#cb-wizard-item-create-form').hide();
                    $('#cb-wizard-item-toggle-create').text(cbWizardData.i18n.create_new_item);
                    $('#cb-wizard-item-title').val('');
                } else {
                    var msg = response.data && response.data.message
                        ? response.data.message
                        : cbWizardData.i18n.unknown_error;
                    $('#cb-wizard-item-create-error').text(msg).show();
                }
            })
            .fail(function () {
                $('#cb-wizard-item-create-error').text(cbWizardData.i18n.unknown_error).show();
            })
            .always(function () {
                $spinner.removeClass('is-active');
                $('#cb-wizard-item-create-submit').prop('disabled', false);
            });
    });

    // ---------------------------------------------------------------------------
    // Inline Location creation AJAX
    // ---------------------------------------------------------------------------
    $(document).on('click', '#cb-wizard-location-create-submit', function () {
        var title = $.trim($('#cb-wizard-location-title').val());
        if (!title) {
            $('#cb-wizard-location-create-error').text(cbWizardData.i18n.title_required).show();
            return;
        }
        $('#cb-wizard-location-create-error').hide();

        var $spinner = $('#cb-wizard-location-create-spinner');
        $spinner.addClass('is-active');
        $(this).prop('disabled', true);

        $.post(cbWizardData.ajax_url, {
            action: 'cb_quick_create_post',
            _ajax_nonce: cbWizardData.nonce,
            post_type: 'cb_location',
            post_title: title,
            street: $('#cb-wizard-location-street').val(),
            postcode: $('#cb-wizard-location-postcode').val(),
            city: $('#cb-wizard-location-city').val(),
        })
            .done(function (response) {
                if (response.success) {
                    var id = response.data.id;
                    var name = response.data.title;

                    // Append to select and auto-select.
                    var $select = $('#cb-wizard-location-select');
                    $('<option>', { value: id, text: name }).appendTo($select);
                    $select.val(id);

                    // Hide the inline form.
                    $('#cb-wizard-location-create-form').hide();
                    $('#cb-wizard-location-toggle-create').text(cbWizardData.i18n.create_new_location);
                    $('#cb-wizard-location-title, #cb-wizard-location-street, #cb-wizard-location-postcode, #cb-wizard-location-city')
                        .val('');
                } else {
                    var msg = response.data && response.data.message
                        ? response.data.message
                        : cbWizardData.i18n.unknown_error;
                    $('#cb-wizard-location-create-error').text(msg).show();
                }
            })
            .fail(function () {
                $('#cb-wizard-location-create-error').text(cbWizardData.i18n.unknown_error).show();
            })
            .always(function () {
                $spinner.removeClass('is-active');
                $('#cb-wizard-location-create-submit').prop('disabled', false);
            });
    });

    // ---------------------------------------------------------------------------
    // Final wizard submit AJAX
    // ---------------------------------------------------------------------------
    $(document).on('submit', '#cb-availability-wizard-form', function (event) {
        event.preventDefault();
        clearNotice();

        var title = $.trim($('#cb-wizard-tf-title').val());
        var itemId = $('#cb-wizard-item-select').val();
        var locationId = $('#cb-wizard-location-select').val();
        var startDate = $('#cb-wizard-tf-start-date').val();

        if (!title) {
            showNotice(cbWizardData.i18n.title_required, 'error');
            return;
        }
        if (!itemId) {
            showNotice(cbWizardData.i18n.item_required, 'error');
            showStep(1);
            return;
        }
        if (!locationId) {
            showNotice(cbWizardData.i18n.location_required, 'error');
            showStep(2);
            return;
        }
        if (!startDate) {
            showNotice(cbWizardData.i18n.start_date_required, 'error');
            return;
        }

        var $submitBtn = $('#cb-wizard-step3-submit');
        var $spinner   = $('#cb-wizard-submit-spinner');
        $submitBtn.prop('disabled', true);
        $spinner.addClass('is-active');

        var fullDay = $('#cb-wizard-tf-full-day').is(':checked') ? 'on' : '';

        $.post(cbWizardData.ajax_url, {
            action: 'cb_create_timeframe_wizard',
            _ajax_nonce: cbWizardData.nonce,
            post_title: title,
            item_id: itemId,
            location_id: locationId,
            type: $('#cb-wizard-tf-type').val(),
            start_date: startDate,
            end_date: $('#cb-wizard-tf-end-date').val(),
            full_day: fullDay,
            start_time: $('#cb-wizard-tf-start-time').val(),
            end_time: $('#cb-wizard-tf-end-time').val(),
            repetition: $('#cb-wizard-tf-repetition').val(),
            grid: $('#cb-wizard-tf-grid').val(),
        })
            .done(function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect || cbWizardData.list_url;
                } else {
                    var msg = response.data && response.data.message
                        ? response.data.message
                        : cbWizardData.i18n.unknown_error;
                    showNotice(msg, 'error');
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            })
            .fail(function () {
                showNotice(cbWizardData.i18n.unknown_error, 'error');
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
    });

    // ---------------------------------------------------------------------------
    // Init
    // ---------------------------------------------------------------------------
    $(function () {
        showStep(1);
    });

})(jQuery);
