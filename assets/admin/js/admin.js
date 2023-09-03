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
        if (timeframeForm.length) {
            const typeInput = $("#type");
            const BOOKABLE_TYPE = 2;
            const HOLIDAY_TYPE = 3;
            const BLOCKED_TYPE = 5;
            const SELECTION_MANUAL = 0;
            const SELECTION_CATEGORY = 1;
            const SELECTION_ALL = 2;
            const timeframeRepetitionInput = $("#timeframe-repetition");
            const locationSelectionInput = $("#location-select");
            const itemSelectionInput = $("#item-select");
            const gridInput = $("#grid");
            const weekdaysInput = $("#weekdays1");
            const startTimeInput = $("#start-time");
            const endTimeInput = $("#end-time");
            const repConfigTitle = $("#title-timeframe-rep-config");
            const repetitionStartInput = $("#repetition-start");
            const repetitionEndInput = $("#repetition-end");
            const fullDayInput = $("#full-day");
            const showBookingCodes = $("#show-booking-codes");
            const createBookingCodesInput = $("#create-booking-codes");
            const bookingCodesDownload = $("#booking-codes-download");
            const bookingCodesList = $("#booking-codes-list");
            const singleLocationSelection = $(".cmb2-id-location-id");
            const multiLocationSelection = $(".cmb2-id-location-ids");
            const singleItemSelection = $(".cmb2-id-item-id");
            const multiItemSelection = $(".cmb2-id-item-ids");
            const categoryLocationSelection = $(".cmb2-id-location-category-ids");
            const categoryItemSelection = $(".cmb2-id-item-category-ids");
            const bookingConfigTitle = $(".cmb2-id-title-bookings-config");
            const maxDaysSelect = $(".cmb2-id-timeframe-max-days");
            const advanceBookingDays = $(".cmb2-id-timeframe-advance-booking-days");
            const BookingStartDayOffset = $(".cmb2-id-booking-startday-offset");
            const allowUserRoles = $(".cmb2-id-allowed-user-roles");
            const repSet = [ repConfigTitle, fullDayInput, startTimeInput, endTimeInput, weekdaysInput, repetitionStartInput, repetitionEndInput, gridInput ];
            const noRepSet = [ fullDayInput, startTimeInput, endTimeInput, gridInput, repetitionStartInput, repetitionEndInput ];
            const repTimeFieldsSet = [ gridInput, startTimeInput, endTimeInput ];
            const bookingCodeSet = [ createBookingCodesInput, bookingCodesList, bookingCodesDownload, showBookingCodes ];
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
            const migrateSingleSelection = () => {
                if (typeInput.val() != HOLIDAY_TYPE) {
                    return;
                }
                const singleSelectionOption = singleItemSelection.find("option:selected");
                if (singleSelectionOption.prop("value")) {
                    const multiItemSelectionOption = multiItemSelection.find(`input[value=${singleSelectionOption.prop("value")}]`);
                    if (multiItemSelectionOption) {
                        multiItemSelectionOption.prop("checked", true);
                    }
                    singleSelectionOption.prop("selected", false);
                }
                const singleLocationSelectionOption = singleLocationSelection.find("option:selected");
                if (singleLocationSelectionOption.prop("value")) {
                    const multiLocationSelectionOption = multiLocationSelection.find(`input[value=${singleLocationSelectionOption.prop("value")}]`);
                    if (multiLocationSelectionOption) {
                        multiLocationSelectionOption.prop("checked", true);
                    }
                    singleLocationSelectionOption.prop("selected", false);
                }
            };
            migrateSingleSelection();
            const handleTypeSelection = function() {
                const selectedType = $("option:selected", typeInput).val();
                if (selectedType == BOOKABLE_TYPE) {
                    maxDaysSelect.show();
                    advanceBookingDays.show();
                    allowUserRoles.show();
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                }
                if (selectedType == HOLIDAY_TYPE) {
                    itemSelectionInput.show();
                    locationSelectionInput.show();
                    migrateSingleSelection();
                } else {
                    itemSelectionInput.hide();
                    locationSelectionInput.hide();
                }
            };
            handleTypeSelection();
            typeInput.change(function() {
                handleTypeSelection();
                handleItemSelection();
                handleLocationSelection();
            });
            const handleLocationSelection = function() {
                const selectedType = $("option:selected", typeInput).val();
                if (selectedType == HOLIDAY_TYPE) {
                    singleLocationSelection.hide();
                    const selectedOption = $("option:selected", locationSelectionInput).val();
                    if (selectedOption == SELECTION_MANUAL) {
                        multiLocationSelection.show();
                        categoryLocationSelection.hide();
                    } else if (selectedOption == SELECTION_CATEGORY) {
                        categoryLocationSelection.show();
                        multiLocationSelection.hide();
                    } else if (selectedOption == SELECTION_ALL) {
                        multiLocationSelection.hide();
                        categoryLocationSelection.hide();
                    }
                } else {
                    singleLocationSelection.show();
                    multiLocationSelection.hide();
                    categoryLocationSelection.hide();
                }
            };
            handleLocationSelection();
            locationSelectionInput.change(function() {
                handleLocationSelection();
            });
            const handleItemSelection = function() {
                const selectedType = $("option:selected", typeInput).val();
                if (selectedType == HOLIDAY_TYPE) {
                    singleItemSelection.hide();
                    const selectedOption = $("option:selected", itemSelectionInput).val();
                    if (selectedOption == SELECTION_MANUAL) {
                        multiItemSelection.show();
                        categoryItemSelection.hide();
                    } else if (selectedOption == SELECTION_CATEGORY) {
                        categoryItemSelection.show();
                        multiItemSelection.hide();
                    } else if (selectedOption == SELECTION_ALL) {
                        multiItemSelection.hide();
                        categoryItemSelection.hide();
                    }
                } else {
                    singleItemSelection.show();
                    multiItemSelection.hide();
                    categoryItemSelection.hide();
                }
            };
            handleItemSelection();
            itemSelectionInput.change(function() {
                handleItemSelection();
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
                if (selectedType) {
                    if (selectedType == "norep") {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }
                    if (selectedType == "w") {
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
                const fullday = fullDayInput.prop("checked"), type = typeInput.val(), repStart = repetitionStartInput.val(), repEnd = repetitionEndInput.val();
                hideFieldset(bookingCodeSet);
                if (repStart && fullday && type == BOOKABLE_TYPE) {
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