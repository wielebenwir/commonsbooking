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
            const timeFieldsSet = [
                startDateTimeInput,
                endDateTimeInput,
                gridInput.parents('.cmb-row '),
                startTimeInput.parents('.cmb-row '),
                endTimeInput.parents('.cmb-row ')
            ];

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
             * Hides time related inputs.
             */
            const hideTimeInputs = function () {
                $.each(timeFieldsSet, function () {
                    $(this).hide();
                })
            }

            /**
             * Shows time related inputs.
             */
            const showTimeInputs = function () {
                $.each(timeFieldsSet, function () {
                    $(this).show();
                })
            }

            /**
             * Shows/hides grid selection depending on checked-state.
             */
            const updateFullDayHandler = function () {
                // Full-day setting
                if(fullDayInput.prop( "checked" )) {
                    gridInput.prop("selected", false);
                    hideTimeInputs();
                } else {
                    showTimeInputs();
                }

                fullDayInput.change(function () {
                    if($(this).prop( "checked" )) {
                        gridInput.prop("selected", false);
                        hideTimeInputs()
                    } else {
                        showTimeInputs();
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

            /**
             * Updates form depending on selected type.
             */
            const handleTypeSelect = function () {
                const selectedType = $('option:selected', typeInput).val();

                initTypeSpecificHandlers();
                if(selectedType) {
                    if (selectedType == 'norep') {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }

                } else {
                    hideFieldset(noRepSet);
                    hideFieldset(repSet);
                }
            }

            // Type select functions
            const initTypeSelect = function() {
                typeInput.change(function () {
                    handleTypeSelect();
                });

                handleTypeSelect();
            };

            initTypeSelect();
        }
    });
})(jQuery);
