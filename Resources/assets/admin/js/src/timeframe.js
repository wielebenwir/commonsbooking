(function ($) {
    'use strict';
    $(function () {
        const arrayDiff = function(array1,array2) {
            var newItems = [];
            jQuery.grep(array2, function(i) {
                if (jQuery.inArray(i, array1) == -1)
                {
                    newItems.push(i);
                }
            });
            return newItems;
        }

        /**
         * Hides set-elements.
         * @param set
         */
        const hideFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').hide();
            });
        };

        /**
         * Show set-elements.
         * @param set
         */
        const showFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').show();
            });
        };

        const timeframeForm = $('#cmb2-metabox-cb_timeframe-custom-fields');

        if(timeframeForm.length) {
            const typeInput = $('#timeframe-repetition');
            const startDateInput = $('#start-date_date');
            const startDateTimeInput = $('#start-date_time');
            const endDateInput = $('#end-date_date');
            const endDateTimeInput = $('#end-date_time');
            const gridInput = $('#grid');
            const weekdaysInput = $('#weekdays1'); // TODO: find bettter solution.
            const startTimeInput = $('#start-time');
            const endTimeInput = $('#end-time');
            const repConfigTitle = $('#title-timeframe-rep-config');
            const repetitionStartInput = $('#repetition-start');
            const repetitionEndInput = $('#repetition-end');
            const fullDayInput = $('#full-day');
            const repSet = [repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput];
            const noRepSet = [fullDayInput, startDateInput, startDateTimeInput, endDateInput, endDateTimeInput, gridInput];

            /**
             * Show repetition fields.
             */
            const showRepFields = function () {
                showFieldset(repSet);
                hideFieldset(arrayDiff(repSet, noRepSet));
            }

            /**
             * Show no-repetition fields.
             */
            const showNoRepFields = function () {
                showFieldset(noRepSet);
                hideFieldset(arrayDiff(noRepSet, repSet));
            }

            /**
             * Time-Select copy functions
             */
            const updateTimeSelectionHandlers = function () {
                startTimeInput.change(function () {
                    startDateTimeInput.val($(this).val());
                });
                endTimeInput.change(function () {
                    endDateTimeInput.val($(this).val());
                });
                repetitionStartInput.change(function () {
                    startDateInput.val($(this).val());
                });
                repetitionEndInput.change(function () {
                    endDateInput.val($(this).val());
                });
            };

            /**
             * Shows/hides grid selection depending on checked-state.
             */
            const updateFullDayHandler = function () {
                // Full-day setting
                if(fullDayInput.prop( "checked" )) {
                    gridInput.prop("selected", false);
                    gridInput.parents('.cmb-row ').hide();
                    startTimeInput.parents('.cmb-row ').hide();
                    endTimeInput.parents('.cmb-row ').hide();
                } else {
                    gridInput.parents('.cmb-row ').show();
                    startTimeInput.parents('.cmb-row ').show();
                    endTimeInput.parents('.cmb-row ').show();
                }

                fullDayInput.change(function () {
                    if($(this).prop( "checked" )) {
                        gridInput.prop("selected", false);
                        gridInput.parents('.cmb-row ').hide();
                        startTimeInput.parents('.cmb-row ').hide();
                        endTimeInput.parents('.cmb-row ').hide();
                    } else {
                        gridInput.parents('.cmb-row ').show();
                        startTimeInput.parents('.cmb-row ').show();
                        endTimeInput.parents('.cmb-row ').show();
                    }
                });
            }

            const uncheck = function (checkboxes) {
                $.each(checkboxes, function () {
                   $(this).prop( "checked", false );
                });
            }

            const updateRepetitionHandler = function() {
                const selectedRep = $("option:selected", typeInput).val();

                if(selectedRep == 'w') {
                    weekdaysInput.parents('.cmb-row').show();
                } else {
                    weekdaysInput.parents('.cmb-row').hide();
                    uncheck($('input[name*=weekdays]'));
                }
                typeInput.change(function() {
                    const selectedRep = $("option:selected", $(this)).val();
                    if(selectedRep == 'w') {
                        weekdaysInput.parents('.cmb-row').show();
                    } else {
                        weekdaysInput.parents('.cmb-row').hide();
                        uncheck($('input[name*=weekdays]'));
                    }
                })
            };

            const initTypeSpecificHandlers = function() {
                updateTimeSelectionHandlers();
                updateFullDayHandler();
                updateRepetitionHandler();
            };

            // Type select functions
            const initTypeSelect = function() {
                const selectedType = $("option:selected", typeInput).val();

                if (selectedType && selectedType !== 'norep') {
                    showRepFields();
                }

                if (selectedType == 'norep') {
                    showNoRepFields();
                }

                if(!selectedType) {
                    hideFieldset(noRepSet);
                    hideFieldset(repSet);
                }

                typeInput.change(function (e) {
                    const selectedType = e.target.options[e.target.selectedIndex].value;

                    if (selectedType && selectedType !== 'norep') {
                        showRepFields();
                    }

                    if (selectedType == 'norep') {
                        showNoRepFields();
                    }

                    if(!selectedType) {
                        hideFieldset(noRepSet);
                        hideFieldset(repSet);
                    }

                    updateFullDayHandler();
                    updateRepetitionHandler();
                });

                initTypeSpecificHandlers();
            };

            initTypeSelect();
        }
    });
})(jQuery);
