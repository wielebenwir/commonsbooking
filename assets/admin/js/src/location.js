(function ($) {
    'use strict';
    $(function () {
        const form = $('input[name=post_type][value=cb_location]').parent('form');

        const countLockedDaysCheckbox = $('#_cb_count_lockdays_in_range');
        const countAmountLockedDays = $('#_cb_count_lockdays_maximum');
        var handleCountLockedDays = function () {
            if (countLockedDaysCheckbox.prop('checked')) {
                countAmountLockedDays.prop('disabled', false);
            } else {
                countAmountLockedDays.prop('disabled', true);
            }
        }
        handleCountLockedDays();
        countLockedDaysCheckbox.change(function () {
            handleCountLockedDays();
        });

    });
})(jQuery);
