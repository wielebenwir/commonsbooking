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
            const typeInput = $('#type');
            //the assigned numbers for the typeinput
            const BOOKABLE_TYPE = 2;
            const HOLIDAY_TYPE = 3;
            const BLOCKED_TYPE = 5;

            // the assigned numbers for the location selection input
            const SELECTION_MANUAL = 0;
            const SELECTION_CATEGORY = 1;
            const SELECTION_ALL = 2;

            const timeframeRepetitionInput = $('#timeframe-repetition');
            const locationSelectionInput = $('#location-select');
            const itemSelectionInput = $('#item-select');
            const gridInput = $('#grid');
            const weekdaysInput = $('#weekdays1'); // TODO: find better solution.
            const startTimeInput = $('#start-time');
            const endTimeInput = $('#end-time');
            const repConfigTitle = $('#title-timeframe-rep-config');
            const repetitionStartInput = $('#repetition-start');
            const repetitionEndInput = $('#repetition-end');
            const fullDayInput = $('#full-day');

            // booking codes
            const bookingCodeTitle = $('#title-timeframe-booking-codes');
            const showBookingCodes = $('#show-booking-codes');
            const createBookingCodesInput = $('#create-booking-codes');
            const bookingCodesDownload = $('#booking-codes-download');
            const bookingCodesList = $('#booking-codes-list');

            const singleLocationSelection = $('.cmb2-id-location-id');
            const multiLocationSelection = $(".cmb2-id-location-ids");
            const singleItemSelection = $('.cmb2-id-item-id');
            const multiItemSelection = $(".cmb2-id-item-ids");
            const categoryLocationSelection = $('.cmb2-id-location-category-ids');
            const categoryItemSelection = $('.cmb2-id-item-category-ids');
            const bookingConfigTitle = $('.cmb2-id-title-bookings-config');
            const holidayField = $('.cmb2-id--cmb2-holiday');
            const holidayInput = $('#timeframe_manual_date');
            const manualDatePicker = $("#cmb2_multiselect_datepicker");
            const manualDateField = $('.cmb2-id-timeframe-manual-date');
            const maxDaysSelect = $('.cmb2-id-timeframe-max-days');
            const advanceBookingDays = $('.cmb2-id-timeframe-advance-booking-days');
            const BookingStartDayOffset = $('.cmb2-id-booking-startday-offset');
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
             * "Moves" selection from single item /location selection to multiselect.
             * Currently only for holidays.
             */
            const migrateSingleSelection = () => {

                if (typeInput.val() != HOLIDAY_TYPE) {
                    return;
                }
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

                const singleLocationSelectionOption = singleLocationSelection.find('option:selected');
                if (singleLocationSelectionOption.prop('value')) {
                    const multiLocationSelectionOption = multiLocationSelection.find(`input[value=${singleLocationSelectionOption.prop('value')}]`);
                    if(multiLocationSelectionOption) {
                        multiLocationSelectionOption.prop('checked', true);
                    }
                    singleLocationSelectionOption.prop('selected', false);
                }
            }
            migrateSingleSelection();

            /**
             * Shows/hides max day selection and user role restriction depending on timeframe type (for bookings).
             */
            const handleTypeSelection = function () {
                const selectedType = $("option:selected", typeInput).val();
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
                if (selectedType == BOOKABLE_TYPE) {
                    maxDaysSelect.show();
                    advanceBookingDays.show();
                    allowUserRoles.show();
                    showFieldset(bookingCodeTitle);
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                    hideFieldset(bookingCodeTitle);
                    if (selectedType == 3 && selectedRepetition == 'manual') {
                        holidayField.show();
                    } else {
                        holidayField.hide();
                        holidayInput.val('');
                    }
                }

                //we migrate the single selection to the multiselect (new holiday timeframes do not have a single selection anymore)
                if (selectedType == HOLIDAY_TYPE) {
                    itemSelectionInput.show();
                    locationSelectionInput.show();
                    migrateSingleSelection();
                } else {
                    itemSelectionInput.hide();
                    locationSelectionInput.hide();
                }
            }

            handleTypeSelection();
            typeInput.change(function () {
                handleTypeSelection();
                handleItemSelection();
                handleLocationSelection();
            });

            /**
             * Shows/hides selection options for locations
             */
            const handleLocationSelection = function () {
                const selectedType = $("option:selected", typeInput).val();
                //disable the mass selection for all timeframes except holidays
                if (selectedType == HOLIDAY_TYPE) {
                    singleLocationSelection.hide();
                    //handle different selection types
                    const selectedOption = $("option:selected", locationSelectionInput).val();
                    if (selectedOption == SELECTION_MANUAL) {
                        multiLocationSelection.show();
                        categoryLocationSelection.hide();
                    } else if (selectedOption == SELECTION_CATEGORY){
                        categoryLocationSelection.show();
                        multiLocationSelection.hide();
                    }
                    else if (selectedOption == SELECTION_ALL) {
                        multiLocationSelection.hide();
                        categoryLocationSelection.hide();
                    }
                }
                else {
                    singleLocationSelection.show();
                    multiLocationSelection.hide();
                    categoryLocationSelection.hide();
                }
            };
            handleLocationSelection();
            locationSelectionInput.change(function () {
                handleLocationSelection();
            });

            /**
             * Shows/hides selection options for items
             */
            const handleItemSelection = function () {
                const selectedType = $("option:selected", typeInput).val();
                //disable the mass selection for all timeframes except holidays (for now)
                if (selectedType == HOLIDAY_TYPE) {
                    singleItemSelection.hide();
                    //handle different selection types
                    const selectedOption = $("option:selected", itemSelectionInput).val();
                    if (selectedOption == SELECTION_MANUAL) {
                        multiItemSelection.show();
                        categoryItemSelection.hide();
                    } else if (selectedOption == SELECTION_CATEGORY){
                        categoryItemSelection.show();
                        multiItemSelection.hide();
                    }
                    else if (selectedOption == SELECTION_ALL) {
                        multiItemSelection.hide();
                        categoryItemSelection.hide();
                    }
                }
                else {
                    singleItemSelection.show();
                    multiItemSelection.hide();
                    categoryItemSelection.hide();
                }
            };
            handleItemSelection();
            itemSelectionInput.change(function () {
                handleItemSelection();
            });
            /**
             * Shows/hides max day selection and user role restriction depending on timeframe Repitition tyoe (for bookings).
             */
            const handleRepititionSelection = function () {
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
                const selectedType = $("option:selected", typeInput).val();
                if (selectedRepetition !== 'manual') {
                    manualDateField.hide()
                    manualDatePicker.hide();
                    holidayInput.val('');
                } else {
                    manualDateField.show();
                    manualDatePicker.show();
                    if (selectedType == 3) {
                        //do nothing
                    } else {
                        holidayInput.val('');
                    }
                }
            }
            handleRepititionSelection();
            timeframeRepetitionInput.change(function () {
                handleRepititionSelection();
            });
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
                const selectedTimeframeType = $("option:selected", typeInput).val();

                if (selectedType) {
                    if (selectedType == 'norep') {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }

                    if (selectedType == 'manual') {
                        manualDateField.show();
                        hideFieldset(repetitionStartInput);
                        hideFieldset(repetitionEndInput);
                    } else {
                        manualDateField.hide();
                        showFieldset(repetitionStartInput);
                        showFieldset(repetitionEndInput);
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

                if (repStart && fullday && type == BOOKABLE_TYPE) {
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
