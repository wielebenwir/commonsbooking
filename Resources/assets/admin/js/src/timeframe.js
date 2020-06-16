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
        const hideFields = function (set) {
            $.each(set, function () {
                $(this).hide();
            });
        };

        /**
         * Show set-elements.
         * @param set
         */
        const showFields = function (set) {
            $.each(set, function () {
                $(this).show();
            });
        };

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
            const repTimeFieldsSet = [gridInput, startTimeInput, endTimeInput];
            const noRepTimeFieldsSet = [startDateTimeInput, endDateTimeInput];

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
             * Unccheck checkboxes.
             * @param checkboxes
             */
            const uncheck = function (checkboxes) {
                $.each(checkboxes, function () {
                    $(this).prop( "checked", false );
                });
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

                    // repetion end is removed, we need to clear enddate time input too
                    if($(this).val() == "") {
                        endDateTimeInput.val($(this).val());
                    }
                });
            };
            updateTimeSelectionHandlers();

            /**
             * Shows/hides grid selection depending on checked-state.
             */
            const handleFullDaySelection = function () {
                const selectedRep = $("option:selected", typeInput).val();
                // Full-day setting
                if(fullDayInput.prop( "checked" )) {
                    gridInput.prop("selected", false);
                    hideFieldset(repTimeFieldsSet);
                    hideFields(noRepTimeFieldsSet);
                } else {
                    if(selectedRep == 'norep') {
                        showFields(noRepTimeFieldsSet);
                        showFieldset([gridInput]);
                    } else {
                        showFieldset(repTimeFieldsSet);
                    }
                }
            }
            handleFullDaySelection();
            fullDayInput.change(function () {
                handleFullDaySelection();
            });

            /**
             * Handles repetition selection.
             */
            const handleRepetitionSelection = function () {
                const selectedType = $('option:selected', typeInput).val();

                if(selectedType) {
                    if (selectedType == 'norep') {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }

                    if(selectedType == 'w') {
                        weekdaysInput.parents('.cmb-row').show();
                    } else {
                        weekdaysInput.parents('.cmb-row').hide();
                        uncheck($('input[name*=weekdays]'));
                    }

                    handleFullDaySelection();
                    updateTimeSelectionHandlers();
                } else {
                    hideFieldset(noRepSet);
                    hideFieldset(repSet);
                }

            }
            handleRepetitionSelection();
            typeInput.change(function() {
                handleRepetitionSelection();
            })
        }
    });
})(jQuery);
