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
        const ruleParam3ID = "rule-param3";
        const handleRuleSelection = function() {
            let groupFields = $("#" + groupName + "_repeat");
            groupFields.on("cmb2_add_row cmb2_remove_row cmb2_shift_rows_complete", function() {
                handleRuleSelection();
            });
            for (let i = 0; i < groupFields.children().length - 1; i++) {
                let currentGroup = $("#" + groupID + i);
                let ruleSelector = currentGroup.find("#" + groupName + "_" + i + "_" + ruleSelectorID);
                let ruleDescription = currentGroup.find('[class*="' + ruleDescriptionID + '"]').find(".cmb2-metabox-description");
                let ruleParam1 = currentGroup.find('[class*="' + ruleParam1ID + '"]');
                let ruleParam1Desc = ruleParam1.find(".cmb2-metabox-description");
                let ruleParam2 = currentGroup.find('[class*="' + ruleParam2ID + '"]');
                let ruleParam2Desc = ruleParam2.find(".cmb2-metabox-description");
                let ruleParam3 = currentGroup.find('[class*="' + ruleParam3ID + '"]');
                let ruleParam3Desc = ruleParam3.find(".cmb2-metabox-description");
                ruleSelector.change(function() {
                    handleRuleSelection();
                });
                const selectedRule = $("option:selected", ruleSelector).val();
                cb_booking_rules.forEach(rule => {
                    if (rule.name == selectedRule) {
                        ruleDescription.text(rule.description);
                        if (rule.hasOwnProperty("params") && rule.params.length > 0) {
                            switch (rule.params.length) {
                              case 1:
                                ruleParam1.show();
                                ruleParam2.hide();
                                ruleParam3.hide();
                                ruleParam1Desc.text(rule.params[0]);
                                break;

                              case 2:
                                ruleParam1.show();
                                ruleParam2.show();
                                ruleParam3.hide();
                                ruleParam1Desc.text(rule.params[0]);
                                ruleParam2Desc.text(rule.params[1]);
                                break;

                              case 3:
                                ruleParam1.show();
                                ruleParam2.show();
                                ruleParam3.show();
                                ruleParam1Desc.text(rule.params[0]);
                                ruleParam2Desc.text(rule.params[1]);
                                ruleParam3Desc.text(rule.params[2]);
                            }
                        } else {
                            ruleParam1.hide();
                            ruleParam2.hide();
                            ruleParam3.hide();
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
        handleRuleSelection();
        handleAppliesToAll();
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
            const showBookingCodes = $("#show-booking-codes");
            const createBookingCodesInput = $("#create-booking-codes");
            const bookingCodesDownload = $("#booking-codes-download");
            const bookingCodesList = $("#booking-codes-list");
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
                if (selectedType == 2) {
                    maxDaysSelect.show();
                    advanceBookingDays.show();
                    allowUserRoles.show();
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
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