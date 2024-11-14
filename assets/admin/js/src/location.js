(function ($) {
    'use strict';
    $(function () {

        /**
         * Hides set-elements.
         * @param set
         */
        const hideFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').hide();
            });
        };

        /**
         * Show set-elements.
         * @param set
         */
        const showFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').show();
            });
        };

        const useGlobalSettings = $('#_cb_use_global_settings');
        const allowLockDaysCheckbox = $('#_cb_allow_lockdays_in_range');
        const countLockedDaysCheckbox = $('#_cb_count_lockdays_in_range');
        const countAmountLockedDays = $('#_cb_count_lockdays_maximum');

        const handleCountLockedDays = function () {
            if (countLockedDaysCheckbox.prop('checked')) {
                showFieldset(countAmountLockedDays);
            } else {
                hideFieldset(countAmountLockedDays);
            }
        };
        handleCountLockedDays();
        countLockedDaysCheckbox.change(function () {
            handleCountLockedDays();
        });

        const handleAllowLockDays = function () {
            if (allowLockDaysCheckbox.prop('checked')) {
                showFieldset(countLockedDaysCheckbox);
                handleCountLockedDays();
            } else {
                hideFieldset(countLockedDaysCheckbox);
                hideFieldset(countAmountLockedDays);
            }
        }
        handleAllowLockDays();
        allowLockDaysCheckbox.change(function () {
            handleAllowLockDays();
        } );

        //hide settings if global settings are used
        const handleUseGlobalSettings = function () {
            if (useGlobalSettings.prop('checked')) {
                hideFieldset(allowLockDaysCheckbox);
                hideFieldset(countLockedDaysCheckbox);
                hideFieldset(countAmountLockedDays);
            }
            else {
                showFieldset(allowLockDaysCheckbox);
                showFieldset(countLockedDaysCheckbox);
                handleCountLockedDays();
            }
        }
        handleUseGlobalSettings();
        useGlobalSettings.change(function () {
            handleUseGlobalSettings();
        });

    });
})(jQuery);
