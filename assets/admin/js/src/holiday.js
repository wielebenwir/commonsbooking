(function ($) {
    'use strict';
    $(function () {
        if ($("#holiday_load_btn").length) {
            var fillHolidays = (year, state) => {
                var holidays = feiertagejs.getHolidays(year, state);
                const inputField = $("#timeframe_manual_date");
                //add holidays to input field, comma separated in format (d.m.Y) and with trailing semi-colon
                holidays.forEach((holiday) => {
                    var date = new Date(holiday.date);
                    var day = date.getDate();
                    //month is 0 based, so we need to add 1
                    var month = date.getMonth() + 1;
                    var year = date.getFullYear();
                    var dateStr = day + "." + month + "." + year;
                    if (inputField.val().length > 0) {
                        if (inputField.val().slice(-1) !== ";") {
                            inputField.val(inputField.val() + ";" + dateStr);
                        } else {
                            inputField.val(inputField.val() + dateStr);
                        }
                    } else {
                        inputField.val(dateStr + ";");
                    }
                });
            };

            $("#holiday_load_btn").click(function () {
                fillHolidays(
                    $('#_cmb2_holidayholiday_year').val(),
                    $('#_cmb2_holidayholiday_state').val()
                );
            });
        }
    });
})(jQuery);
