(function ($) {
    'use strict';
    $(function () {
        let fullDayCheckbox = $('#full-day');
        let startTimeInput = $('#repetition-start_time');
        let endTimeInput = $('#repetition-end_time');
        let preserveManualCode = false;
        fullDayCheckbox.on('change', function (event) {
            if (fullDayCheckbox.is(':checked')) {
                startTimeInput.val('00:00');
                endTimeInput.val('23:59');
                startTimeInput.hide();
                endTimeInput.hide();
            } else {
                startTimeInput.show();
                endTimeInput.show();
            }
        });
        fullDayCheckbox.trigger('change');


        let itemInput = $('#item-id');
        let locationInput = $('#location-id');
        let startDateInput = $('#repetition-start_date');
        let bookingCodeInput = $('#_cb_bookingcode');

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
                            fullDayCheckbox.prop('checked', data.fullDay);
                            fullDayCheckbox.trigger('change');
                        }
                    }).then(() =>  {
                        fetchBookingCode()
                    });
            };
            fetchLocation(data);
        });
        //immediately fetch location (and booking code) when the page is loaded
        itemInput.trigger('change');

        const fetchBookingCode = () => {
            if (! fullDayCheckbox.is(':checked')) {
                return;
            }
            let data = {
                itemID: itemInput.val(),
                locationID: locationInput.val(),
                startDate: startDateInput.val()
            };
            $.post(
                cb_ajax_get_booking_code.ajax_url,
                {
                    _ajax_nonce: cb_ajax_get_booking_code.nonce,
                    action: "cb_get_booking_code",
                    data: data
                }, function (data) {
                    if (data.success) {
                        bookingCodeInput.val(data.bookingCode);
                        preserveManualCode = false;
                    }
                    else if (! preserveManualCode) {
                        bookingCodeInput.val('');
                    }
                });
        };

        //prevent the automatically generated empty string for non-existing booking codes from overwriting a manually entered code
        bookingCodeInput.on('keyup', function (event) {
            preserveManualCode = true;
        });

        startDateInput.on('change', function (event) {
            fetchBookingCode();
        });
        fullDayCheckbox.on('change', function (event) {
            fetchBookingCode();
        });
    });
})(jQuery);