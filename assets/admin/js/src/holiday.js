(function ($) {
    'use strict';
    $(function () {
        let holidayLoadButton = $("#holiday_load_btn");
        const manualDateInput = $("#timeframe_manual_date");

        var addHolidayToInput = ( date ) => {
            const DATE_SEPERATOR = ",";
            //we need to add a leading zero if the day or month is less than 10
            var day = date.getDate();
            var month = date.getMonth() + 1;
            var dd = day <= 9 ? "0" + day : day;
            //month is 0 based, so we need to add 1
            var mm = month <= 9 ? "0" + month : month;
            var yyyy = date.getFullYear();
            var dateStr = yyyy + "-" + mm + "-" + dd;
            if (manualDateInput.val().length > 0) {
                if (manualDateInput.val().slice(-1) !== DATE_SEPERATOR) {
                    manualDateInput.val(manualDateInput.val() + DATE_SEPERATOR + dateStr);
                } else {
                    manualDateInput.val(manualDateInput.val() + dateStr);
                }
            } else {
                manualDateInput.val(dateStr + DATE_SEPERATOR);
            }
        }
        if (holidayLoadButton.length) {
            var fillHolidays = (year, state) => {
                var holidays = feiertagejs.getHolidays(year, state);
                //add holidays to input field, comma separated in format (d.m.Y) and with trailing semi-colon
                holidays.forEach((holiday) => {
                    var date = new Date(holiday.date);
                    addHolidayToInput(date);
                });
            };

            holidayLoadButton.click(function () {
                fillHolidays(
                    $('#_cmb2_holidayholiday_year').val(),
                    $('#_cmb2_holidayholiday_state').val()
                );
            });

            $("#cmb2_multiselect_datepicker").datepicker({
                // enable selecting multiple dates
                onSelect: function(dateText, inst) {
                    var date = $(this).datepicker( "getDate" );
                    console.log(date);
                    addHolidayToInput(date);
                }
            });
    }
    });
})(jQuery);
