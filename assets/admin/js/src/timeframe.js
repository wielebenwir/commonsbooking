(function ($) {
    'use strict';
    $(function () {
        const arrayDiff = function (array1, array2) {
            var newItems = [];
            jQuery.grep(array2, function (i) {
                if (jQuery.inArray(i, array1) == -1) {
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

        if (timeframeForm.length) {
            const timeframeRepetitionInput = $('#timeframe-repetition');
            const typeInput = $('#type');
            const gridInput = $('#grid');
            const weekdaysInput = $('#weekdays1'); // TODO: find better solution.
            const startTimeInput = $('#start-time');
            const endTimeInput = $('#end-time');
            const repConfigTitle = $('#title-timeframe-rep-config');
            const repetitionStartInput = $('#repetition-start');
            const repetitionEndInput = $('#repetition-end');
            const fullDayInput = $('#full-day');

            // booking codes
            const showBookingCodes = $('#show-booking-codes');
            const createBookingCodesInput = $('#create-booking-codes');
            const bookingCodesDownload = $('#booking-codes-download');
            const bookingCodesList = $('#booking-codes-list');

            const singleLocationSelection = $('.cmb2-id-location-id');
            const multiLocationSelection = $('.cmb2-id-location-ids');
            const singleItemSelection = $('.cmb2-id-item-id');
            const multiItemSelection = $('.cmb2-id-item-ids');

            const maxDaysSelect = $('.cmb2-id-timeframe-max-days');
            const advanceBookingDays = $('.cmb2-id-timeframe-advance-booking-days');
            const allowUserRoles = $('.cmb2-id-allowed-user-roles');
            const repSet = [repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput];
            const noRepSet = [fullDayInput, startTimeInput, endTimeInput, gridInput, repetitionStartInput, repetitionEndInput];
            const repTimeFieldsSet = [gridInput, startTimeInput, endTimeInput];
            const bookingCodeSet = [createBookingCodesInput, bookingCodesList, bookingCodesDownload, showBookingCodes];

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
             * Uncheck checkboxes.
             * @param checkboxes
             */
            const uncheck = function (checkboxes) {
                $.each(checkboxes, function () {
                    $(this).prop("checked", false);
                });
            }

            /**
             * "Moves" selection from single item selection to multiselect.
             */
            const migrateSingleSelection = () => {
                // get single selection
                const singleSelectionOption = singleItemSelection.find('option:selected');

                // if it has a value, remove selection from single select and activate checkbox in multiselect
                if(singleSelectionOption.prop('value')) {
                    const multiItemSelectionOption = multiItemSelection.find(`input[value=${singleSelectionOption.prop('value')}]`);
                    if(multiItemSelectionOption) {
                        multiItemSelectionOption.prop('checked', true);
                    }
                    singleSelectionOption.prop('selected', false);
                }
            }
            migrateSingleSelection();

            /**
             * Shows/hides max day selection and user role restriction depending on timeframe type (for bookings).
             */
            const handleTypeSelection = function () {
                const selectedType = $("option:selected", typeInput).val();
                singleItemSelection.hide();
                multiItemSelection.show();

                if (selectedType == 2) {
                    maxDaysSelect.show();
                    advanceBookingDays.show();
                    allowUserRoles.show();
                    singleLocationSelection.show();
                    multiLocationSelection.hide();
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                    singleLocationSelection.hide();
                    multiLocationSelection.show();
                }
            }
            handleTypeSelection();
            typeInput.change(function () {
                handleTypeSelection();
                // handleItemSelection();
                // handleLocationSelection();
            });

            // /**
            //  * Shows/hides selection options for locations
            //  */
            // const handleLocationSelection = function () {
            //     const selectedType = $("option:selected", typeInput).val();
            //     //disable the mass selection for bookable timeframes (for now)
            //     if (selectedType == 2) {
            //         manualLocationSelection.show();
            //         categoryLocationSelection.hide();
            //         locationSelectionInput.hide();
            //     }
            //     const selectedOption = $("option:selected", locationSelectionInput).val();
            //     if (selectedOption == 0) {
            //         categoryLocationSelection.hide();
            //         manualLocationSelection.show();
            //     } else if (selectedOption == 1){
            //         categoryLocationSelection.show();
            //         manualLocationSelection.hide();
            //     } else {
            //         manualLocationSelection.hide();
            //         categoryLocationSelection.hide();
            //     }
            // };
            // handleLocationSelection();
            // locationSelectionInput.change(function () {
            //     handleLocationSelection();
            // });

            // /**
            //  * Shows/hides selection options for items
            //  */
            // const handleItemSelection = function () {
            //     const selectedType = $("option:selected", typeInput).val();
            //     //disable the mass selection for bookable timeframes (for now)
            //     if (selectedType == 2) {
            //         manualItemSelection.show();
            //         categoryItemSelection.hide();
            //         itemSelectionInput.hide();
            //     }
            //     const selectedOption = $("option:selected", itemSelectionInput).val();
            //     if (selectedOption == 0) {
            //         manualItemSelection.show();
            //         categoryItemSelection.hide();
            //     } else if (selectedOption == 1){
            //         categoryItemSelection.show();
            //         manualItemSelection.hide();
            //     }
            //     else {
            //         manualItemSelection.hide();
            //         categoryItemSelection.hide();
            //     }
            // };
            // handleItemSelection();
            // itemSelectionInput.change(function () {
            //     handleItemSelection();
            // });

            /**
             * Shows/hides grid selection depending on checked-state.
             */
            const handleFullDaySelection = function () {
                const selectedRep = $("option:selected", timeframeRepetitionInput).val();
                // Full-day setting
                if (fullDayInput.prop("checked")) {
                    gridInput.prop("selected", false);
                    hideFieldset(repTimeFieldsSet);
                } else {
                    showFieldset(repTimeFieldsSet);
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

                if (selectedType) {
                    if (selectedType == 'norep') {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }

                    if (selectedType == 'w') {
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
            timeframeRepetitionInput.change(function () {
                handleRepetitionSelection();
            })

            const handleBookingCodesSelection = function () {
                const fullday = fullDayInput.prop('checked'),
                    type = typeInput.val(),
                    repStart = repetitionStartInput.val(),
                    repEnd = repetitionEndInput.val();

                hideFieldset(bookingCodeSet);

                if (repStart && repEnd && fullday && type == 2) {
                    showFieldset(bookingCodeSet);

                    // If booking codes shall not be created we disable and hide option to show them
                    if (!createBookingCodesInput.prop('checked')) {
                        hideFieldset([showBookingCodes]);
                        showBookingCodes.prop('checked', false);
                    }
                }
            };
            handleBookingCodesSelection();

            // Add handler to relevant fields
            const bookingCodeSelectionInputs = [
                repetitionStartInput,
                repetitionEndInput,
                fullDayInput,
                typeInput,
                createBookingCodesInput
            ];
            $.each(bookingCodeSelectionInputs, function (key, input) {
                input.change(
                    function () {
                        handleBookingCodesSelection();
                    }
                );
            })
        }
    });
})(jQuery);
