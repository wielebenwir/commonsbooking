(function($) {
    "use strict";
    $(function() {
        let fullDayCheckbox = $("#full-day");
        let startTimeInput = $("#repetition-start_time");
        let endTimeInput = $("#repetition-end_time");
        let preserveManualCode = false;
        let itemInput = $("#item-id");
        let locationInput = $("#location-id");
        let startDateInput = $("#repetition-start_date");
        let bookingCodeInput = $("#_cb_bookingcode");
        let allExist = [ fullDayCheckbox, startTimeInput, endTimeInput, itemInput, locationInput, startDateInput, bookingCodeInput ].every(domElement => domElement.length === 1);
        if (!allExist) {
            return;
        }
        fullDayCheckbox.on("change", function(event) {
            if (fullDayCheckbox.is(":checked")) {
                startTimeInput.val("00:00");
                endTimeInput.val("23:59");
                startTimeInput.hide();
                endTimeInput.hide();
            } else {
                startTimeInput.show();
                endTimeInput.show();
            }
        });
        fullDayCheckbox.trigger("change");
        itemInput.on("change", function(event) {
            let data = {
                itemID: itemInput.val()
            };
            const fetchLocation = data => {
                $.post(cb_ajax_get_bookable_location.ajax_url, {
                    _ajax_nonce: cb_ajax_get_bookable_location.nonce,
                    action: "cb_get_bookable_location",
                    data: data
                }, function(data) {
                    if (data.success) {
                        locationInput.val(data.locationID);
                        fullDayCheckbox.prop("checked", data.fullDay);
                        fullDayCheckbox.trigger("change");
                    }
                }).then(() => {
                    fetchBookingCode();
                });
            };
            fetchLocation(data);
        });
        itemInput.trigger("change");
        const fetchBookingCode = () => {
            if (!fullDayCheckbox.is(":checked")) {
                return;
            }
            let data = {
                itemID: itemInput.val(),
                locationID: locationInput.val(),
                startDate: startDateInput.val()
            };
            $.post(cb_ajax_get_booking_code.ajax_url, {
                _ajax_nonce: cb_ajax_get_booking_code.nonce,
                action: "cb_get_booking_code",
                data: data
            }, function(data) {
                if (data.success) {
                    bookingCodeInput.val(data.bookingCode);
                    preserveManualCode = false;
                } else if (!preserveManualCode) {
                    bookingCodeInput.val("");
                }
            });
        };
        bookingCodeInput.on("keyup", function(event) {
            preserveManualCode = true;
        });
        startDateInput.on("change", function(event) {
            fetchBookingCode();
        });
        fullDayCheckbox.on("change", function(event) {
            fetchBookingCode();
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const groupName = "rules_group";
        const groupID = "cmb-group-rules_group-";
        const ruleSelectorID = "rule-type";
        const ruleDescriptionID = "rule-description";
        const ruleAppliesAllID = "rule-applies-all";
        const ruleAppliesCategoriesID = "rule-applies-categories";
        const ruleParam1ID = "rule-param1";
        const ruleParam2ID = "rule-param2";
        const ruleSelectParamID = "rule-select-param";
        const exemptRolesID = "exempt-roles";
        const handleRuleSelection = function() {
            let groupFields = $("#" + groupName + "_repeat");
            groupFields.on("cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete", function() {
                handleRuleSelection();
            });
            for (let i = 0; i < groupFields.children().length - 1; i++) {
                let currentGroup = $("#" + groupID + i);
                let ruleSelector = currentGroup.find("#" + groupName + "_" + i + "_" + ruleSelectorID);
                let ruleDescription = currentGroup.find('[class*="' + ruleDescriptionID + '"]').find(".cmb2-metabox-description");
                let ruleAppliesAll = currentGroup.find('[class*="' + ruleAppliesAllID + '"]');
                let ruleAppliesCategories = currentGroup.find('[class*="' + ruleAppliesCategoriesID + '"]');
                let exemptRoles = currentGroup.find('[class*="' + exemptRolesID + '"]');
                let ruleParam1 = currentGroup.find('[class*="' + ruleParam1ID + '"]');
                let ruleParam1Input = ruleParam1.find(".cmb2-text-small");
                let ruleParam1InputLabel = $(ruleParam1Input.labels()[0]);
                let ruleParam1Desc = ruleParam1.find(".cmb2-metabox-description");
                let ruleParam2 = currentGroup.find('[class*="' + ruleParam2ID + '"]');
                let ruleParam2Input = ruleParam2.find(".cmb2-text-small");
                let ruleParam2InputLabel = $(ruleParam2Input.labels()[0]);
                let ruleParam2Desc = ruleParam2.find(".cmb2-metabox-description");
                let ruleSelectParam = currentGroup.find('[class*="' + ruleSelectParamID + '"]');
                let ruleSelectParamDesc = ruleSelectParam.find(".cmb2-metabox-description");
                let ruleSelectParamOptions = ruleSelectParam.find(".cmb2_select");
                ruleSelector.change(function() {
                    handleRuleSelection();
                });
                const selectedRule = $("option:selected", ruleSelector).val();
                if (selectedRule === "") {
                    ruleDescription.hide();
                    ruleParam1.hide();
                    ruleParam2.hide();
                    ruleSelectParam.hide();
                    ruleAppliesAll.hide();
                    ruleAppliesCategories.hide();
                    exemptRoles.hide();
                    return;
                }
                cb_booking_rules.forEach(rule => {
                    if (rule.name == selectedRule) {
                        ruleDescription.text(rule.description);
                        ruleSelector.width(300);
                        ruleAppliesAll.show();
                        ruleAppliesCategories.show();
                        exemptRoles.show();
                        ruleDescription.show();
                        if (rule.hasOwnProperty("params") && rule.params.length > 0) {
                            switch (rule.params.length) {
                              case 1:
                                ruleParam1.show();
                                ruleParam2.hide();
                                ruleParam1InputLabel.text(rule.params[0]["title"]);
                                ruleParam1Desc.text(rule.params[0]["description"]);
                                ruleParam2.val("");
                                break;

                              case 2:
                                ruleParam1.show();
                                ruleParam2.show();
                                ruleParam1InputLabel.text(rule.params[0]["title"]);
                                ruleParam1Desc.text(rule.params[0]["description"]);
                                ruleParam2InputLabel.text(rule.params[1]["title"]);
                                ruleParam2Desc.text(rule.params[1]["description"]);
                                break;
                            }
                        } else {
                            ruleParam1.hide();
                            ruleParam1.val("");
                            ruleParam2.hide();
                            ruleParam2.val("");
                        }
                        if (rule.hasOwnProperty("selectParam") && rule.selectParam.length > 0) {
                            ruleSelectParam.show();
                            ruleSelectParamDesc.text(rule.selectParam[0]);
                            let ruleOptions = rule.selectParam[1];
                            ruleSelectParamOptions.empty();
                            for (var key in ruleOptions) {
                                ruleSelectParamOptions.append($("<option>", {
                                    value: key,
                                    text: ruleOptions[key]
                                }));
                            }
                            ruleSelectParamOptions.width(150);
                            let appliedRule = cb_applied_booking_rules.filter(appliedRule => {
                                return appliedRule.name == rule.name;
                            });
                            if (appliedRule.length === 1) {
                                ruleSelectParamOptions.val(appliedRule[0].appliedSelectParam);
                            }
                        } else {
                            ruleSelectParam.hide();
                        }
                    }
                });
            }
        };
        const handleAppliesToAll = function() {
            let groupFields = $("#" + groupName + "_repeat");
            groupFields.on("cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete", function() {
                handleAppliesToAll();
            });
            for (let i = 0; i < groupFields.children().length - 1; i++) {
                let currentGroup = $("#" + groupID + i);
                let ruleAppliesAll = currentGroup.find('[class*="' + ruleAppliesAllID + '"]').find(".cmb2-option");
                let ruleAppliesCategories = currentGroup.find('[class*="' + ruleAppliesCategoriesID + '"]');
                ruleAppliesAll.change(function() {
                    handleAppliesToAll();
                });
                if (ruleAppliesAll.prop("checked")) {
                    ruleAppliesCategories.hide();
                } else {
                    ruleAppliesCategories.show();
                }
            }
        };
        handleAppliesToAll();
        handleRuleSelection();
    });
})(jQuery);

const CB_POST_TYPES_WITH_METABOXES = [ "cb_location", "cb_item", "cb_timeframe", "cb_booking", "cb_restriction", "cb_map" ];

(function waitForWP() {
    if (window.wp && wp.data && wp.data.select && wp.data.subscribe) {
        init();
        return;
    }
    setTimeout(waitForWP, 50);
})();

function init() {
    const unsubscribe = wp.data.subscribe(() => {
        const editor = wp.data.select("core/editor");
        if (!editor || typeof editor.getCurrentPostType !== "function") return;
        const postType = editor.getCurrentPostType();
        if (!postType) return;
        unsubscribe();
        if (!CB_POST_TYPES_WITH_METABOXES.includes(postType)) {
            return;
        }
        const prefs = wp.data.select("core/preferences");
        if (prefs && typeof prefs.get === "function") {
            const isOpen = prefs.get("core/edit-post", "metaBoxesMainIsOpen");
            if (undefined === isOpen) {
                wp.data.dispatch("core/preferences").set("core/edit-post", "metaBoxesMainIsOpen", true);
            }
        }
    });
}

(function($) {
    "use strict";
    $(function() {
        let typeInput = $("#export-type");
        let locationFields = $("#location-fields");
        let itemFields = $("#item-fields");
        let userFields = $("#user-fields");
        let exportTimerangeStart = $("#export-timerange-start");
        let exportTimerangeEnd = $("#export-timerange-end");
        let inProgress = $("#timeframe-export-in-progress");
        let inProgressSpan = $("#timeframe-export-in-progress span");
        let done = $("#timeframe-export-done");
        let failed = $("#timeframe-export-failed");
        let failedSpan = $("#timeframe-export-failed span");
        let doneSpan = $("#timeframe-export-done span");
        $("#timeframe-export-start").on("click", function(event) {
            event.preventDefault();
            let settings = {
                exportType: typeInput.val(),
                locationFields: locationFields.val(),
                itemFields: itemFields.val(),
                userFields: userFields.val(),
                exportStartDate: exportTimerangeStart.val(),
                exportEndDate: exportTimerangeEnd.val()
            };
            let progress = "0/0 bookings exported";
            let data = {
                settings: settings,
                progress: progress
            };
            doneSpan.hide();
            failedSpan.hide();
            inProgress.show();
            const runExport = data => {
                $.post(cb_ajax_export_timeframes.ajax_url, {
                    _ajax_nonce: cb_ajax_export_timeframes.nonce,
                    action: "cb_export_timeframes",
                    data: data
                }, function(data) {
                    if (data.success) {
                        done.show();
                        doneSpan.text(data.message);
                        inProgress.hide();
                        const blob = new Blob([ data.csv ]);
                        const filename = data.filename;
                        const link = document.createElement("a");
                        link.href = URL.createObjectURL(blob);
                        link.download = filename;
                        link.click();
                    } else if (data.error) {
                        failed.show();
                        failedSpan.text(data.message);
                        inProgress.hide();
                    } else {
                        inProgressSpan.text(data.progress);
                        runExport(data);
                    }
                });
            };
            runExport(data);
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        const holidayLoadButton = $("#holiday_load_btn");
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
        if (holidayLoadButton.length) {
            var fillHolidays = (year, state) => {
                var holidays = feiertagejs.getHolidays(year, state);
                holidays.forEach(holiday => {
                    var date = new Date(holiday.date);
                    addHolidayToInput(date);
                });
            };
            holidayLoadButton.click(function() {
                fillHolidays($("#_cmb2_holidayholiday_year").val(), $("#_cmb2_holidayholiday_state").val());
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
        const mapSettingsForm = $("#cmb2-metabox-cb_map-custom-fields");
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
        const copyToClipboard = function(element) {
            let code = $(element).find("code")[0];
            let text = code.innerText;
            navigator.clipboard.writeText(text).then(function() {
                let button = $(element).find(".button");
                let buttonText = button.text();
                button.text("✓");
                button.disabled = true;
                setTimeout(function() {
                    button.text(buttonText);
                    button.disabled = false;
                }, 2e3);
            });
        };
        const copyToClipboardButton = $("#shortcode-field").find(".button");
        copyToClipboardButton.on("click", function() {
            copyToClipboard($("#shortcode-field"));
        });
        function handleCustomFileInput(fileSelectorID, fileInputFields) {
            const markerFileSelect = document.querySelector(fileSelectorID);
            const handleSelectCustomMarker = function() {
                showFieldset(fileInputFields);
                if (markerFileSelect.value === "") {
                    hideFieldset(fileInputFields);
                }
            };
            handleSelectCustomMarker();
            const observerConfig = {
                attributes: true,
                childList: false,
                subtree: false
            };
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === "value") {
                        handleSelectCustomMarker();
                    }
                });
            });
            observer.observe(markerFileSelect, observerConfig);
        }
        if (mapSettingsForm.length) {
            handleCustomFileInput("#custom_marker_media", [ $("#marker_icon_width"), $("#marker_icon_height"), $("#marker_icon_anchor_x"), $("#marker_icon_anchor_y") ]);
            handleCustomFileInput("#custom_marker_cluster_media", [ $("#marker_cluster_icon_width"), $("#marker_cluster_icon_height") ]);
            handleCustomFileInput("#marker_item_draft_media", [ $("#marker_item_draft_icon_width"), $("#marker_item_draft_icon_height"), $("#marker_item_draft_icon_anchor_x"), $("#marker_item_draft_icon_anchor_y") ]);
        }
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        $("#orphans-migration-start").on("click", function(event) {
            event.preventDefault();
            $("#orphans-migration-in-progress").show();
            let checkedBoxes = $(".post-checkboxes:checkbox:checked");
            let ids = [];
            checkedBoxes.each(function() {
                ids.push($(this).val());
            });
            let data = ids;
            $.post(cb_ajax_orphaned_booking_migration.ajax_url, {
                _ajax_nonce: cb_ajax_orphaned_booking_migration.nonce,
                action: "cb_orphaned_booking_migration",
                data: data
            }).done(function(data) {
                if (data.success) {
                    $("#orphans-migration-in-progress").hide();
                    $("#orphans-migration-done").show();
                    $("#orphans-migration-done span").text(data.message);
                    ids.forEach(function(id) {
                        $("#row-booking-" + id).remove();
                    });
                } else {
                    $("#orphans-migration-in-progress").hide();
                    $("#orphans-migration-failed").show();
                    $("#orphans-migration-failed span").text(data.message);
                }
            });
        });
    });
})(jQuery);

(function($) {
    "use strict";
    $(function() {
        if ($("#upgrade-fields").length == 0) {
            $(".cmb2-id-upgrade-header").hide();
        }
        $("#cmb2-metabox-migration #run-upgrade").on("click", function(event) {
            event.preventDefault();
            $("#upgrade-in-progress").show();
            $("#run-upgrade").hide();
            let data = {
                progress: {
                    task: 0,
                    page: 1
                }
            };
            const runUpgrade = data => {
                $.post(cb_ajax_run_upgrade.ajax_url, {
                    _ajax_nonce: cb_ajax_run_upgrade.nonce,
                    action: "cb_run_upgrade",
                    data: data
                }, function(data) {
                    if (data.success) {
                        $("#upgrade-in-progress").hide();
                        $("#upgrade-done").show();
                    } else {
                        runUpgrade(data);
                    }
                });
            };
            runUpgrade(data);
        });
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
        const form = $("input[name=post_type][value=cb_restriction]").parent("form").find("#cb_restriction-custom-fields");
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
        const SELECTION_MANUAL = 0;
        const SELECTION_CATEGORY = 1;
        const SELECTION_ALL = 2;
        const timeframeRepetitionInput = $("#timeframe-repetition");
        const locationSelectionInput = $("#location-select");
        const itemSelectionInput = $("#item-select");
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
            const emailBookingCodesList = $("#email-booking-codes-list");
            const cronEmailBookingCodesList = $("#cron-email-booking-code");
            const boxSendEntireTimeframeCodes = $("#timeframe-bookingcodes-sendall");
            const linkSendEntireTimeframeCodes = $("#email-booking-codes-list-all");
            const linkSendCurrentMonth = $("#email-booking-codes-list-current");
            const linkSendNextMonth = $("#email-booking-codes-list-next");
            const singleLocationSelection = $(".cmb2-id-location-id");
            const multiLocationSelection = $(".cmb2-id-location-id-list");
            const singleItemSelection = $(".cmb2-id-item-id");
            const multiItemSelection = $(".cmb2-id-item-id-list");
            const categoryLocationSelection = $(".cmb2-id-location-category-ids");
            const categoryItemSelection = $(".cmb2-id-item-category-ids");
            const holidayField = $(".cmb2-id--cmb2-holiday");
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
            const bookingCodeSet = [ createBookingCodesInput, bookingCodesList, bookingCodesDownload, showBookingCodes, emailBookingCodesList, cronEmailBookingCodesList ];
            const bookingCodeConfigSet = [ showBookingCodes, bookingCodesList, bookingCodesDownload, emailBookingCodesList, cronEmailBookingCodesList ];
            const form = $("input[name=post_type][value=cb_timeframe]").parent("form");
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
            const migrateSingleSelection = () => {
                if (typeInput.val() != HOLIDAYS_ID) {
                    return;
                }
                const singleItemSelectionOption = singleItemSelection.find("option:selected");
                if (singleItemSelectionOption.prop("value")) {
                    const multiItemSelectionOption = multiItemSelection.find(`input[value=${singleItemSelectionOption.prop("value")}]`);
                    if (multiItemSelectionOption) {
                        multiItemSelectionOption.prop("checked", true);
                    }
                    singleItemSelectionOption.prop("selected", false);
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
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
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
                if (selectedType == HOLIDAYS_ID) {
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
                if (selectedType == HOLIDAYS_ID) {
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
                if (selectedType == HOLIDAYS_ID) {
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
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
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
                        } else {
                            holidayField.hide();
                        }
                    } else {
                        manualDateField.hide();
                        manualDatePicker.hide();
                        showFieldset(repetitionStartInput);
                        showFieldset(repetitionEndInput);
                    }
                    if (selectedRepetition === REPETITION_WEEKLY) {
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
                if (repStart && fullday && type === BOOKABLE_ID) {
                    showFieldset(bookingCodeSet);
                    if (!createBookingCodesInput.prop("checked")) {
                        hideFieldset(bookingCodeConfigSet);
                        showBookingCodes.prop("checked", false);
                    } else {
                        showFieldset(bookingCodeConfigSet);
                    }
                    if (!repEnd) {
                        boxSendEntireTimeframeCodes.hide();
                    } else {
                        boxSendEntireTimeframeCodes.show();
                    }
                }
            };
            handleBookingCodesSelection();
            form.find("input, select, textarea").on("keyup change paste", function() {
                linkSendEntireTimeframeCodes.addClass("disabled");
                linkSendCurrentMonth.addClass("disabled");
                linkSendNextMonth.addClass("disabled");
            });
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