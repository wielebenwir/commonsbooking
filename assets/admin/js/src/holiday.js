(function ($) {
    'use strict';
    $(function () {
        if ($("#holiday_load_btn").length) {
            var fillHolidays = (year, state) => {
                var holidays = feiertagejs.getHolidays(year, state);
                const inputField = $("#timeframe_manual_date");
                const DATE_SEPERATOR = ",";
                //add holidays to input field, comma separated in format (d.m.Y) and with trailing semi-colon
                holidays.forEach((holiday) => {
                    var date = new Date(holiday.date);
                    //we need to add a leading zero if the day or month is less than 10
                    var dd = (date.getDate()).length == 1 ? "0" + date.getDate() : date.getDate();
                    //month is 0 based, so we need to add 1
                    var mm = (date.getMonth() + 1).length == 1 ? "0" + (date.getMonth() + 1) : (date.getMonth() + 1);
                    var yyyy = date.getFullYear();
                    var dateStr = yyyy + "-" + mm + "-" + dd;
                    if (inputField.val().length > 0) {
                        if (inputField.val().slice(-1) !== DATE_SEPERATOR) {
                            inputField.val(inputField.val() + DATE_SEPERATOR + dateStr);
                        } else {
                            inputField.val(inputField.val() + dateStr);
                        }
                    } else {
                        inputField.val(dateStr + DATE_SEPERATOR);
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
