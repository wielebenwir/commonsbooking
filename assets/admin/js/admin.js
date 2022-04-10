(function($) {
    "use strict";
    $(function() {
        if ($("#holiday_load_btn").length) {
            var fillHolidays = (year, state) => {
                $.post(cb_ajax_holiday_get.ajax_url, {
                    _ajax_nonce: cb_ajax_holiday_get.nonce,
                    action: "holiday_get",
                    year: year,
                    state: state
                }, function(data) {
                    var array = $.parseJSON(data);
                    array = Object.entries(array).map(item => item[1]);
                    if ($("#timeframe_manual_date").val().length > 0) {
                        if ($("#timeframe_manual_date").val().slice(-1) !== ",") {
                            $("#timeframe_manual_date").val($("#timeframe_manual_date").val() + "," + array.join(","));
                        }
                        $("#timeframe_manual_date").val($("#timeframe_manual_date").val() + array.join(","));
                    } else {
                        $("#timeframe_manual_date").val(array.join(",") + ",");
                    }
                });
            };
            $("#holiday_load_btn").click(function() {
                fillHolidays($("#_cmb2_holidayholiday_year").val(), $("#_cmb2_holidayholiday_state").val());
            });
        }
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
                $.post(cb_ajax.ajax_url, {
                    _ajax_nonce: cb_ajax.nonce,
                    action: "start_migration",
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
            $.post(cb_ajax.ajax_url, {
                _ajax_nonce: cb_ajax.nonce,
                action: "start_booking_migration"
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
            const itemField = $(".cmb2-id-item-id");
            const itemAllField = $(".cmb2-id-item-all-id");
            const itemMultiField = $(".cmb2-id-item-multi-id");
            const locationField = $(".cmb2-id-location-id");
            const locationAllField = $(".cmb2-id-location-all-id");
            const locationMultiField = $(".cmb2-id-location-multi-id");
            const showBookingCodes = $("#show-booking-codes");
            const createBookingCodesInput = $("#create-booking-codes");
            const bookingCodesDownload = $("#booking-codes-download");
            const bookingCodesList = $("#booking-codes-list");
            const itemAllCheckbox = $("#item-all-id");
            const locationAllCheckbox = $("#location-all-id");
            const holidayField = $(".cmb2-id--cmb2-holiday");
            const holidayInput = $("#timeframe_manual_date");
            const manualDateField = $(".cmb2-id-timeframe-manual-date");
            const maxDaysSelect = $(".cmb2-id-timeframe-max-days");
            const advanceBookingDays = $(".cmb2-id-timeframe-advance-booking-days");
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
            const handleTypeSelection = function() {
                const selectedType = $("option:selected", typeInput).val();
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
                if (selectedType == 2) {
                    maxDaysSelect.show();
                    advanceBookingDays.show();
                    allowUserRoles.show();
                    itemField.hide();
                    itemAllField.show();
                    itemMultiField.hide();
                    locationField.show();
                    locationAllField.hide();
                    locationMultiField.hide();
                    console.log("here");
                } else if (selectedType == 3) {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                    itemField.hide();
                    itemAllField.hide();
                    itemMultiField.hide();
                    locationField.hide();
                    locationAllField.show();
                    locationMultiField.hide();
                    if (selectedRepetition == "manual") {
                        holidayField.show();
                    } else {
                        holidayField.hide();
                        holidayInput.val("");
                    }
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                    itemField.show();
                    itemAllField.hide();
                    itemMultiField.hide();
                    locationField.show();
                    locationAllField.hide();
                    locationMultiField.hide();
                }
            };
            handleTypeSelection();
            typeInput.change(function() {
                handleTypeSelection();
            });
            const handleItemAllSelection = function() {
                if (itemAllCheckbox.is(":checked")) {
                    itemMultiField.hide();
                } else {
                    itemMultiField.show();
                }
            };
            itemAllCheckbox.change(function() {
                handleItemAllSelection();
            });
            const handleLocationAllSelection = function() {
                if (locationAllCheckbox.is(":checked")) {
                    locationMultiField.hide();
                } else {
                    locationMultiField.show();
                }
            };
            locationAllCheckbox.change(function() {
                handleLocationAllSelection();
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
                    if (selectedType == "norep") {
                        showNoRepFields();
                    } else {
                        showRepFields();
                    }
                    if (selectedType !== "manual") {
                        manualDateField.hide();
                        holidayField.hide();
                        holidayInput.val("");
                    } else {
                        manualDateField.show();
                        if (selectedTimeframeType == 3) {
                            holidayField.show();
                        } else {
                            holidayField.hide();
                        }
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
                if (repStart && repEnd && fullday && type == 2) {
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