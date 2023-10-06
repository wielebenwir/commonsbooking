(function ($) {
    'use strict';
    $(function () {
        let fullDayCheckbox = $('#full-day');
        let startTimeInput = $('#repetition-start_time');
        let endTimeInput = $('#repetition-end_time');
        fullDayCheckbox.on('change', function (event) {
            if (fullDayCheckbox.is(':checked')) {
                startTimeInput.val('00:00');
                endTimeInput.val('23:59');
                startTimeInput.prop('disabled', true);
                endTimeInput.prop('disabled', true);
            } else {
                startTimeInput.prop('disabled', false);
                endTimeInput.prop('disabled', false);
            }
        });
        fullDayCheckbox.trigger('change');


        let itemInput = $('#item-id');
        let locationInput = $('#location-id');
        itemInput.on('change', function (event) {
            let data = {
                itemID: itemInput.val(),
            };
            const fetchLocation = (data) => {
                $.post(
                    cb_ajax_get_bookable_location.ajax_url,
                    {
                        _ajax_nonce: cb_ajax_get_bookable_location.nonce,
                        action: "cb_get_bookable_location",
                        data: data
                    }, function (data) {
                        if (data.success) {
                            locationInput.val(data.locationID);
                        }
                    });
            };
            fetchLocation(data);
        });
    });
})(jQuery);