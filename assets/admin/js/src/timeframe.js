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
            const timeframeRepetitionInput = $('#timeframe-repetition');
            const typeInput = $('#type');
            const gridInput = $('#grid');
            const weekdaysInput = $('#weekdays1'); // TODO: find bettter solution.
            const startTimeInput = $('#start-time');
            const endTimeInput = $('#end-time');
            const repConfigTitle = $('#title-timeframe-rep-config');
            const repetitionStartInput = $('#repetition-start');
            const repetitionEndInput = $('#repetition-end');
            const fullDayInput = $('#full-day');
            const maxDaysSelect = $('.cmb2-id-timeframe-max-days');
            const repSet = [repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput];
            const noRepSet = [fullDayInput, startTimeInput, endTimeInput, gridInput, repetitionStartInput, repetitionEndInput];
            const repTimeFieldsSet = [gridInput, startTimeInput, endTimeInput];

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
             * Shows/hides max day selection depending on timeframe type (for bookings).
             */
            const handleTypeSelection = function () {
                const selectedType = $("option:selected", typeInput).val();

                if(selectedType == 2) {
                    maxDaysSelect.show();
                } else {
                    maxDaysSelect.hide();
                }
            }
            handleTypeSelection();
            typeInput.change(function () {
                handleTypeSelection();
            });

            /**
             * Shows/hides grid selection depending on checked-state.
             */
            const handleFullDaySelection = function () {
                const selectedRep = $("option:selected", timeframeRepetitionInput).val();
                // Full-day setting
                if(fullDayInput.prop( "checked" )) {
                    gridInput.prop("selected", false);
                    hideFieldset(repTimeFieldsSet);
                } else {
                    if(selectedRep == 'norep') {
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
                const selectedType = $('option:selected', timeframeRepetitionInput).val();

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
                } else {
                    hideFieldset(noRepSet);
                    hideFieldset(repSet);
                }

            }
            handleRepetitionSelection();
            timeframeRepetitionInput.change(function() {
                handleRepetitionSelection();
            })
        }
    });
})(jQuery);
