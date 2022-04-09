(function ($) {
    'use strict';
    let ajax = (year, state) => {
        $.post(
            cb_ajax_holiday_get.ajax_url,
            {
                _ajax_nonce: cb_ajax_holiday_get.nonce,
                action: "holiday_get",
                year: year,
                state: state
            },
            function (data) {
                var array = $.parseJSON(data);
                array = Object.entries(array).map(item => item[1])

                if ($("#timeframe_manual_date").val().length > 0) {
                    if ($("#timeframe_manual_date").val().slice(-1) !== ",") {
                        $("#timeframe_manual_date").val($("#timeframe_manual_date").val() + "," + array.join(","))
                    }
                    $("#timeframe_manual_date").val($("#timeframe_manual_date").val() + array.join(","))
                } else {
                    $("#timeframe_manual_date").val(array.join(",") + ",")
                }
            }
        );
    };
    $(document).ready(function () {
        $("#holiday_load_btn").click(function () {
            ajax($('#_cmb2_holidayholiday_year').val(), $('#_cmb2_holidayholiday_state').val());
        });
    });

})(jQuery);