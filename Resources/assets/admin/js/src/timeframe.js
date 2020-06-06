(function ($) {
    'use strict';
    $(function () {
        const timeframeForm = $('#cmb2-metabox-cb_timeframe-custom-fields');
        if(timeframeForm.length) {
            const typeInput = $('#timeframe-repetition');
            const startDateInput = $('#start-date_date');
            const startDateTimeInput = $('#start-date_time');
            const endDateInput = $('#end-date_date');
            const endDateTimeInput = $('#end-date_time');
            const gridInput = $('#grid');
            const repetitionInput = $('#repetition');
            const weekdaysInput = $('#weekdays1'); // TODO: find bettter solution.
            const startTimeInput = $('#start-time');
            const endTimeInput = $('#end-time');
            const repConfigTitle = $('#title-timeframe-rep-config');
            const repetitionStartInput = $('#repetition-start');
            const repetitionEndInput = $('#repetition-end');
            const fullDayInput = $('#full-day');
            const repSet = [repConfigTitle, fullDayInput, startTimeInput, endTimeInput, repetitionInput, weekdaysInput, repetitionStartInput, repetitionEndInput];
            const noRepSet = [fullDayInput, startDateInput, startDateTimeInput, endDateInput, endDateTimeInput, gridInput];

            typeInput.change(function (e) {
                const selectedType = e.target.options[e.target.selectedIndex].value;

                if(selectedType == 'rep') {
                    $.each(noRepSet, function () {
                        console.log($(this).parents('.cmb-row '))
                        $(this).parents('.cmb-row ').hide();
                    });
                    $.each(repSet, function () {
                        console.log($(this).parents('.cmb-row '))
                        $(this).parents('.cmb-row ').show();
                    });
                }

                if(selectedType == 'norep') {
                    $.each(repSet, function () {
                        console.log($(this).parents('.cmb-row '))
                        $(this).parents('.cmb-row ').hide();
                    });
                    $.each(noRepSet, function () {
                        console.log($(this).parents('.cmb-row '))
                        $(this).parents('.cmb-row ').show();
                    });

                }
            });

        }
    });
})(jQuery);
