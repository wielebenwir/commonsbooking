(function ($) {
    'use strict';
    $(function () {
        const manualDateInput = $("#timeframe_manual_date");
        const manualDatePicker = $("#cmb2_multiselect_datepicker");

        var addHolidayToInput = ( date ) => {
            const DATES_SEPERATOR = ",";
            //we need to add a leading zero if the day or month is less than 10
            var day = date.getDate();
            var month = date.getMonth() + 1;
            var dd = day <= 9 ? "0" + day : day;
            //month is 0 based, so we need to add 1
            var mm = month <= 9 ? "0" + month : month;
            var yyyy = date.getFullYear();
            var dateStr = yyyy + "-" + mm + "-" + dd;
            if (manualDateInput.val().length > 0) {
                if (manualDateInput.val().slice(-1) !== DATES_SEPERATOR) {
                    manualDateInput.val(manualDateInput.val() + DATES_SEPERATOR + dateStr);
                } else {
                    manualDateInput.val(manualDateInput.val() + dateStr);
                }
            } else {
                manualDateInput.val(dateStr + DATES_SEPERATOR);
            }
        }
        if (manualDatePicker.length) {
            manualDatePicker.datepicker({
                // enable selecting multiple dates
                onSelect: function(dateText, inst) {
                    var date = $(this).datepicker( "getDate" );
                    addHolidayToInput(date);
                }
            });
        }
    });
})(jQuery);
