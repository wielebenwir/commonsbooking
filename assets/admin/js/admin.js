(function($) {
    "use strict";
    $(function() {
        const manualDateInput = $("#timeframe_manual_date");
        const manualDatePicker = $("#cmb2_multiselect_datepicker");
        var addHolidayToInput = date => {
            const DATES_SEPERATOR = ",";
            var day = date.getDate();
            var month = date.getMonth() + 1;
            var dd = day <= 9 ? "0" + day : day;
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
        };
        if (manualDatePicker.length) {
            manualDatePicker.datepicker({
                onSelect: function(dateText, inst) {
                    var date = $(this).datepicker("getDate");
                    addHolidayToInput(date);
                }
            });
        }
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const hideFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").hide();
            });
        };
        const showFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").show();
            });
        };
        const useGlobalSettings = $("#_cb_use_global_settings");
        const allowLockDaysCheckbox = $("#_cb_allow_lockdays_in_range");
        const countLockedDaysCheckbox = $("#_cb_count_lockdays_in_range");
        const countAmountLockedDays = $("#_cb_count_lockdays_maximum");
        const handleCountLockedDays = function() {
            if (countLockedDaysCheckbox.prop("checked")) {
                showFieldset(countAmountLockedDays);
            } else {
                hideFieldset(countAmountLockedDays);
            }
        };
        handleCountLockedDays();
        countLockedDaysCheckbox.change(function() {
            handleCountLockedDays();
        });
        const handleAllowLockDays = function() {
            if (allowLockDaysCheckbox.prop("checked")) {
                showFieldset(countLockedDaysCheckbox);
                handleCountLockedDays();
            } else {
                hideFieldset(countLockedDaysCheckbox);
                hideFieldset(countAmountLockedDays);
            }
        };
        handleAllowLockDays();
        allowLockDaysCheckbox.change(function() {
            handleAllowLockDays();
        });
        const handleUseGlobalSettings = function() {
            if (useGlobalSettings.prop("checked")) {
                hideFieldset(allowLockDaysCheckbox);
                hideFieldset(countLockedDaysCheckbox);
                hideFieldset(countAmountLockedDays);
            } else {
                showFieldset(allowLockDaysCheckbox);
                showFieldset(countLockedDaysCheckbox);
                handleCountLockedDays();
            }
        };
        handleUseGlobalSettings();
        useGlobalSettings.change(function() {
            handleUseGlobalSettings();
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        $("#cmb2-metabox-migration #migration-start").on("click", function(event) {
            event.preventDefault();
            $("#migration-state").show();
            $("#migration-in-progress").show();
            const runMigration = data => {
                $.post(cb_ajax_start_migration.ajax_url, {
                    _ajax_nonce: cb_ajax_start_migration.nonce,
                    action: "cb_start_migration",
                    data: data,
                    geodata: $("#get-geo-locations").is(":checked")
                }, function(data) {
                    let allComplete = true;
                    $.each(data, function(index, value) {
                        $("#" + index + "-index").text(value.index);
                        $("#" + index + "-count").text(value.count);
                        if (value.complete == "0") {
                            allComplete = false;
                        }
                    });
                    if (!allComplete) {
                        runMigration(data);
                    } else {
                        $("#migration-in-progress").hide();
                        $("#migration-done").show();
                    }
                });
            };
            runMigration(false);
        });
        $("#cmb2-metabox-migration #booking-update-start").on("click", function(event) {
            event.preventDefault();
            $("#booking-migration-in-progress").show();
            $.post(cb_ajax_start_migration.ajax_url, {
                _ajax_nonce: cb_ajax_start_migration.nonce,
                action: "cb_start_booking_migration"
            }).done(function() {
                $("#booking-migration-in-progress").hide();
                $("#booking-migration-done").show();
            }).fail(function() {
                $("#booking-migration-in-progress").hide();
                $("#booking-migration-failed").show();
            });
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const form = $("input[name=post_type][value=cb_restriction]").parent("form");
        form.find("input, select, textarea").on("keyup change paste", function() {
            form.find("input[name=restriction-send]").prop("disabled", true);
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const hideFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").hide();
            });
        };
        const showFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").show();
            });
        };
        const emailform = $("#templates");
        if (emailform.length) {
            const eventCreateCheckbox = $("#emailtemplates_mail-booking_ics_attach");
            const eventTitleInput = $("#emailtemplates_mail-booking_ics_event-title");
            const eventDescInput = $("#emailtemplates_mail-booking_ics_event-description");
            const eventFieldSet = [ eventTitleInput, eventDescInput ];
            const handleiCalAttachmentSelection = function() {
                showFieldset(eventFieldSet);
                if (!eventCreateCheckbox.prop("checked")) {
                    hideFieldset(eventFieldSet);
                    eventCreateCheckbox.prop("checked", false);
                }
            };
            handleiCalAttachmentSelection();
            eventCreateCheckbox.click(function() {
                handleiCalAttachmentSelection();
            });
        }
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const arrayDiff = function(array1, array2) {
            var newItems = [];
            jQuery.grep(array2, function(i) {
                if (jQuery.inArray(i, array1) == -1) {
                    newItems.push(i);
                }
            });
            return newItems;
        };
        const hideFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").hide();
            });
        };
        const showFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").show();
            });
        };
        const timeframeForm = $("#cmb2-metabox-cb_timeframe-custom-fields");
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
            const timeframeRepetitionInput = $("#timeframe-repetition");
            const typeInput = $("#type");
            const gridInput = $("#grid");
            const weekdaysInput = $("#weekdays1");
            const startTimeInput = $("#start-time");
            const endTimeInput = $("#end-time");
            const repConfigTitle = $("#title-timeframe-rep-config");
            const repetitionStartInput = $("#repetition-start");
            const repetitionEndInput = $("#repetition-end");
            const fullDayInput = $("#full-day");
            const bookingCodeTitle = $("#title-timeframe-booking-codes");
            const showBookingCodes = $("#show-booking-codes");
            const createBookingCodesInput = $("#create-booking-codes");
            const bookingCodesDownload = $("#booking-codes-download");
            const bookingCodesList = $("#booking-codes-list");
            const holidayInput = $("#timeframe_manual_date");
            const manualDatePicker = $("#cmb2_multiselect_datepicker");
            const manualDateField = $(".cmb2-id-timeframe-manual-date");
            const maxDaysSelect = $("#timeframe-max-days");
            const advanceBookingDays = $("#timeframe-advance-booking-days");
            const bookingStartDayOffset = $("#booking-startday-offset");
            const bookingConfigurationTitle = $("#title-bookings-config");
            const allowUserRoles = $("#allowed_user_roles");
            const repSet = [ repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput ];
            const noRepSet = [ fullDayInput, startTimeInput, endTimeInput, gridInput, repetitionStartInput, repetitionEndInput ];
            const repTimeFieldsSet = [ gridInput, startTimeInput, endTimeInput ];
            const bookingCodeSet = [ createBookingCodesInput, bookingCodesList, bookingCodesDownload, showBookingCodes ];
            const bookingConfigSet = [ maxDaysSelect, advanceBookingDays, bookingStartDayOffset, allowUserRoles, bookingConfigurationTitle ];
            const showRepFields = function() {
                showFieldset(repSet);
                hideFieldset(arrayDiff(repSet, noRepSet));
            };
            const showNoRepFields = function() {
                showFieldset(noRepSet);
                hideFieldset(arrayDiff(noRepSet, repSet));
            };
            const uncheck = function(checkboxes) {
                $.each(checkboxes, function() {
                    $(this).prop("checked", false);
                });
            };
            const handleTypeSelection = function() {
                const selectedType = $("option:selected", typeInput).val();
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
                if (selectedType === BOOKABLE_ID) {
                    showFieldset(bookingConfigSet);
                    showFieldset(bookingCodeTitle);
                } else {
                    hideFieldset(bookingConfigSet);
                    hideFieldset(bookingCodeTitle);
                }
            };
            handleTypeSelection();
            typeInput.change(function() {
                handleTypeSelection();
            });
            const handleFullDaySelection = function() {
                const selectedRep = $("option:selected", timeframeRepetitionInput).val();
                if (fullDayInput.prop("checked")) {
                    gridInput.prop("selected", false);
                    hideFieldset(repTimeFieldsSet);
                } else {
                    showFieldset(repTimeFieldsSet);
                }
            };
            handleFullDaySelection();
            fullDayInput.change(function() {
                handleFullDaySelection();
            });
            const handleRepetitionSelection = function() {
                const selectedType = $("option:selected", timeframeRepetitionInput).val();
                const selectedTimeframeType = $("option:selected", typeInput).val();
                if (selectedType) {
                    if (selectedType == REPETITION_NONE) {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }
                    if (selectedType === REPETITION_MANUAL) {
                        manualDateField.show();
                        manualDatePicker.show();
                        hideFieldset(repetitionStartInput);
                        hideFieldset(repetitionEndInput);
                    } else {
                        manualDateField.hide();
                        manualDatePicker.hide();
                        showFieldset(repetitionStartInput);
                        showFieldset(repetitionEndInput);
                    }
                    if (selectedType === REPETITION_WEEKLY) {
                        weekdaysInput.parents(".cmb-row").show();
                    } else {
                        weekdaysInput.parents(".cmb-row").hide();
                        uncheck($("input[name*=weekdays]"));
                    }
                    handleFullDaySelection();
                } else {
                    hideFieldset(noRepSet);
                    hideFieldset(repSet);
                }
            };
            handleRepetitionSelection();
            timeframeRepetitionInput.change(function() {
                handleRepetitionSelection();
            });
            const handleBookingCodesSelection = function() {
                const fullday = fullDayInput.prop("checked"), type = typeInput.val(), repStart = repetitionStartInput.val();
                hideFieldset(bookingCodeSet);
                if (repStart && fullday && type === BOOKABLE_ID) {
                    showFieldset(bookingCodeSet);
                    if (!createBookingCodesInput.prop("checked")) {
                        hideFieldset([ showBookingCodes ]);
                        showBookingCodes.prop("checked", false);
                    }
                }
            };
            handleBookingCodesSelection();
            const bookingCodeSelectionInputs = [ repetitionStartInput, repetitionEndInput, fullDayInput, typeInput, createBookingCodesInput ];
            $.each(bookingCodeSelectionInputs, function(key, input) {
                input.change(function() {
                    handleBookingCodesSelection();
                });
            });
        }
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        $(document).tooltip();
    });
})(jQuery);