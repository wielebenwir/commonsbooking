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

        const BOOKABLE_ID = "2";
        const HOLIDAYS_ID = "3";
        const REPAIR_ID = "5";

        const REPETITION_NONE = "norep";
        const REPETITION_MANUAL = "manual";
        const REPETITION_DAILY = "d";
        const REPETITION_WEEKLY = "w";
        const REPETITION_MONTHLY = "m";
        const REPETITION_YEARLY = "y";

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
            const bookingCodeTitle = $('#title-timeframe-booking-codes');
            const showBookingCodes = $('#show-booking-codes');
            const createBookingCodesInput = $('#create-booking-codes');
            const bookingCodesDownload = $('#booking-codes-download');
            const bookingCodesList = $('#booking-codes-list');
            const emailBookingCodesList = $("#email-booking-codes-list");
            const cronEmailBookingCodesList = $("#cron-email-booking-code");

            // The links for sending booking codes for part of the timeframe
            const boxSendEntireTimeframeCodes = $('#timeframe-bookingcodes-sendall');
            const linkSendEntireTimeframeCodes = $('#email-booking-codes-list-all');
            const linkSendCurrentMonth = $('#email-booking-codes-list-current');
            const linkSendNextMonth = $('#email-booking-codes-list-next');


            const holidayField = $('.cmb2-id--cmb2-holiday');
            const holidayInput = $('#timeframe_manual_date');
            const manualDatePicker = $("#cmb2_multiselect_datepicker");
            const manualDateField = $('.cmb2-id-timeframe-manual-date');
            const maxDaysSelect = $('#timeframe-max-days');
            const advanceBookingDays = $('#timeframe-advance-booking-days');
            const bookingStartDayOffset = $('#booking-startday-offset');
            const bookingConfigurationTitle = $('#title-bookings-config');
            const allowUserRoles = $('#allowed_user_roles');
            const repSet = [repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput];
            const noRepSet = [fullDayInput, startTimeInput, endTimeInput, gridInput, repetitionStartInput, repetitionEndInput];
            const repTimeFieldsSet = [gridInput, startTimeInput, endTimeInput];
            const bookingCodeSet = [createBookingCodesInput, bookingCodesList, bookingCodesDownload, showBookingCodes, emailBookingCodesList, cronEmailBookingCodesList];
            const bookingCodeConfigSet = [showBookingCodes, bookingCodesList, bookingCodesDownload, emailBookingCodesList, cronEmailBookingCodesList];

            const form = $('input[name=post_type][value=cb_timeframe]').parent('form');
            const bookingConfigSet = [maxDaysSelect, advanceBookingDays, bookingStartDayOffset, allowUserRoles, bookingConfigurationTitle];

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
             * Shows/hides max day selection and user role restriction depending on timeframe type (for bookings).
             */
            const handleTypeSelection = function () {
                const selectedType =  $("option:selected", typeInput).val();
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val()
                if (selectedType === BOOKABLE_ID) {
                    showFieldset(bookingConfigSet);
                    showFieldset(bookingCodeTitle);
                    holidayField.hide();
                } else {
                    hideFieldset(bookingConfigSet);
                    hideFieldset(bookingCodeTitle);
                    if (selectedType == HOLIDAYS_ID && selectedRepetition == REPETITION_MANUAL) {
                        holidayField.show();
                    } else {
                        holidayField.hide();
                    }
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
                const selectedRepetition = $('option:selected', timeframeRepetitionInput).val();
                const selectedTimeframeType = $("option:selected", typeInput).val();

                if (selectedRepetition) {
                    if (selectedRepetition == REPETITION_NONE) {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }

                    if (selectedRepetition === REPETITION_MANUAL) {
                        manualDateField.show();
                        manualDatePicker.show();
                        hideFieldset(repetitionStartInput);
                        hideFieldset(repetitionEndInput);
                        if (selectedTimeframeType == HOLIDAYS_ID) {
                            holidayField.show();
                        }
                        else {
                            holidayField.hide();
                        }
                    } else {
                        manualDateField.hide();
                        manualDatePicker.hide();
                        showFieldset(repetitionStartInput);
                        showFieldset(repetitionEndInput);
                    }

                    if (selectedRepetition === REPETITION_WEEKLY) {
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

                if (repStart && fullday && type === BOOKABLE_ID) {
                    showFieldset(bookingCodeSet);

                    // If booking codes shall not be created we disable and hide option to show them
                    if (!createBookingCodesInput.prop('checked')) {
                        hideFieldset(bookingCodeConfigSet);
                        showBookingCodes.prop('checked', false);
                    }
                    else {
                        showFieldset(bookingCodeConfigSet);
                    }

                    // If no end-date is selected, we hide the option to send codes for the entire timeframe
                    if (!repEnd) {
                        boxSendEntireTimeframeCodes.hide();
                    }
                    else {
                        boxSendEntireTimeframeCodes.show();
                    }

                }
            };
            handleBookingCodesSelection();

            // disable sending booking code emails before saving the form
            form.find('input, select, textarea').on('keyup change paste', function () {
                linkSendEntireTimeframeCodes.addClass('disabled');
                linkSendCurrentMonth.addClass('disabled');
                linkSendNextMonth.addClass('disabled');
            });

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
