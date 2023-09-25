(function ($) {
    'use strict';
    $(function () {
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