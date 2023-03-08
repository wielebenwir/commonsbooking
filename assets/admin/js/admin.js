"use strict";

Object.defineProperty(exports, "__esModule", {
    value: true
});

exports.addTranslation = addTranslation;

exports.getHolidayByDate = getHolidayByDate;

exports.getHolidays = getHolidays;

exports.getLanguage = getLanguage;

exports.isHoliday = isHoliday;

exports.isSpecificHoliday = isSpecificHoliday;

exports.isSunOrHoliday = isSunOrHoliday;

exports.setLanguage = setLanguage;

function _toConsumableArray(arr) {
    return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
}

function _nonIterableSpread() {
    throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

function _iterableToArray(iter) {
    if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
}

function _arrayWithoutHoles(arr) {
    if (Array.isArray(arr)) return _arrayLikeToArray(arr);
}

function _createForOfIteratorHelper(o, allowArrayLike) {
    var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
    if (!it) {
        if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
            if (it) o = it;
            var i = 0;
            var F = function F() {};
            return {
                s: F,
                n: function n() {
                    if (i >= o.length) return {
                        done: true
                    };
                    return {
                        done: false,
                        value: o[i++]
                    };
                },
                e: function e(_e) {
                    throw _e;
                },
                f: F
            };
        }
        throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }
    var normalCompletion = true, didErr = false, err;
    return {
        s: function s() {
            it = it.call(o);
        },
        n: function n() {
            var step = it.next();
            normalCompletion = step.done;
            return step;
        },
        e: function e(_e2) {
            didErr = true;
            err = _e2;
        },
        f: function f() {
            try {
                if (!normalCompletion && it["return"] != null) it["return"]();
            } finally {
                if (didErr) throw err;
            }
        }
    };
}

function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;
    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
    return arr2;
}

var germanTranslations = {
    NEUJAHRSTAG: "Neujahrstag",
    HEILIGEDREIKOENIGE: "Heilige Drei Könige",
    KARFREITAG: "Karfreitag",
    OSTERSONNTAG: "Ostersonntag",
    OSTERMONTAG: "Ostermontag",
    TAG_DER_ARBEIT: "Tag der Arbeit",
    CHRISTIHIMMELFAHRT: "Christi Himmelfahrt",
    PFINGSTSONNTAG: "Pfingstsonntag",
    PFINGSTMONTAG: "Pfingstmontag",
    FRONLEICHNAM: "Fronleichnam",
    MARIAHIMMELFAHRT: "Mariä Himmelfahrt",
    DEUTSCHEEINHEIT: "Tag der Deutschen Einheit",
    REFORMATIONSTAG: "Reformationstag",
    ALLERHEILIGEN: "Allerheiligen",
    BUBETAG: "Buß- und Bettag",
    ERSTERWEIHNACHTSFEIERTAG: "1. Weihnachtstag",
    ZWEITERWEIHNACHTSFEIERTAG: "2. Weihnachtstag",
    WELTKINDERTAG: "Weltkindertag",
    WELTFRAUENTAG: "Weltfrauentag",
    AUGSBURGER_FRIEDENSFEST: "Augsburger Friedensfest"
};

var allHolidays = [ "NEUJAHRSTAG", "HEILIGEDREIKOENIGE", "KARFREITAG", "OSTERSONNTAG", "OSTERMONTAG", "TAG_DER_ARBEIT", "CHRISTIHIMMELFAHRT", "MARIAHIMMELFAHRT", "PFINGSTSONNTAG", "PFINGSTMONTAG", "FRONLEICHNAM", "DEUTSCHEEINHEIT", "REFORMATIONSTAG", "ALLERHEILIGEN", "BUBETAG", "ERSTERWEIHNACHTSFEIERTAG", "ZWEITERWEIHNACHTSFEIERTAG", "WELTKINDERTAG", "WELTFRAUENTAG", "AUGSBURGER_FRIEDENSFEST" ];

var allRegions = [ "BW", "BY", "BE", "BB", "HB", "HE", "HH", "MV", "NI", "NW", "RP", "SL", "SN", "ST", "SH", "TH", "BUND", "AUGSBURG", "ALL" ];

var defaultLanguage = "de";

var currentLanguage = defaultLanguage;

var translations = {
    de: germanTranslations
};

function addTranslation(isoCode, newTranslation) {
    var code = isoCode.toLowerCase();
    var defaultTranslation = translations[defaultLanguage];
    var missingFields = false;
    var _iterator = _createForOfIteratorHelper(allHolidays), _step;
    try {
        for (_iterator.s(); !(_step = _iterator.n()).done; ) {
            var holiday = _step.value;
            if (!newTranslation[holiday]) {
                missingFields = true;
                newTranslation[holiday] = defaultTranslation[holiday];
            }
        }
    } catch (err) {
        _iterator.e(err);
    } finally {
        _iterator.f();
    }
    if (missingFields) {
        console.warn("[feiertagejs] addTranslation: you did not add all holidays in your translation! Took German as fallback");
    }
    translations[code] = newTranslation;
}

function setLanguage(isoCode) {
    var code = isoCode.toLowerCase();
    if (!translations[code]) {
        throw new TypeError("[feiertagejs] tried to set language to ".concat(code, " but the translation is missing. Please use addTranslation(isoCode,object) first"));
    }
    currentLanguage = isoCode;
}

function getLanguage() {
    return currentLanguage;
}

function isSunOrHoliday(date, region) {
    checkRegion(region);
    return date.getDay() === 0 || isHoliday(date, region);
}

function isHoliday(date, region) {
    checkRegion(region);
    var year = date.getFullYear();
    var internalDate = toUtcTimestamp(date);
    var holidays = getHolidaysAsUtcTimestamps(year, region);
    return holidays.indexOf(internalDate) !== -1;
}

function getHolidayByDate(date) {
    var region = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : "ALL";
    checkRegion(region);
    var holidays = getHolidaysOfYear(date.getFullYear(), region);
    return holidays.find(function(holiday) {
        return holiday.equals(date);
    });
}

function checkRegion(region) {
    if (region === null || region === undefined) {
        throw new Error("Region must not be undefined or null");
    }
    if (allRegions.indexOf(region) === -1) {
        throw new Error("Invalid region: ".concat(region, "! Must be one of ").concat(allRegions.toString()));
    }
}

function checkHolidayType(holidayName) {
    if (holidayName === null || holidayName === undefined) {
        throw new TypeError("holidayName must not be null or undefined");
    }
    if (allHolidays.indexOf(holidayName) === -1) {
        throw new Error('feiertage.js: invalid holiday type "'.concat(holidayName, '"! Must be one of ').concat(allHolidays.toString()));
    }
}

function isSpecificHoliday(date, holidayName) {
    var region = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : "ALL";
    checkRegion(region);
    checkHolidayType(holidayName);
    var holidays = getHolidaysOfYear(date.getFullYear(), region);
    var foundHoliday = holidays.find(function(holiday) {
        return holiday.equals(date);
    });
    if (!foundHoliday) {
        return false;
    }
    return foundHoliday.name === holidayName;
}

function getHolidays(year, region) {
    var y;
    if (typeof year === "string") {
        y = parseInt(year, 10);
    } else {
        y = year;
    }
    checkRegion(region);
    return getHolidaysOfYear(y, region);
}

function getHolidaysAsUtcTimestamps(year, region) {
    var holidays = getHolidaysOfYear(year, region);
    return holidays.map(function(holiday) {
        return toUtcTimestamp(holiday.date);
    });
}

function getHolidaysOfYear(year, region) {
    var easterDate = getEasterDate(year);
    var karfreitag = addDays(new Date(easterDate.getTime()), -2);
    var ostermontag = addDays(new Date(easterDate.getTime()), 1);
    var christiHimmelfahrt = addDays(new Date(easterDate.getTime()), 39);
    var pfingstsonntag = addDays(new Date(easterDate.getTime()), 49);
    var pfingstmontag = addDays(new Date(easterDate.getTime()), 50);
    var holidays = [].concat(_toConsumableArray(getCommonHolidays(year)), [ newHoliday("KARFREITAG", karfreitag), newHoliday("OSTERMONTAG", ostermontag), newHoliday("CHRISTIHIMMELFAHRT", christiHimmelfahrt), newHoliday("PFINGSTMONTAG", pfingstmontag) ]);
    addHeiligeDreiKoenige(year, region, holidays);
    addEasterAndPfingsten(year, region, easterDate, pfingstsonntag, holidays);
    addFronleichnam(region, easterDate, holidays);
    addMariaeHimmelfahrt(year, region, holidays);
    addReformationstag(year, region, holidays);
    addAllerheiligen(year, region, holidays);
    addBussUndBetttag(year, region, holidays);
    addWeltkindertag(year, region, holidays);
    addWeltfrauenTag(year, region, holidays);
    addRegionalHolidays(year, region, holidays);
    return holidays.sort(function(a, b) {
        return a.date.getTime() - b.date.getTime();
    });
}

function getCommonHolidays(year) {
    return [ newHoliday("NEUJAHRSTAG", makeDate(year, 1, 1)), newHoliday("TAG_DER_ARBEIT", makeDate(year, 5, 1)), newHoliday("DEUTSCHEEINHEIT", makeDate(year, 10, 3)), newHoliday("ERSTERWEIHNACHTSFEIERTAG", makeDate(year, 12, 25)), newHoliday("ZWEITERWEIHNACHTSFEIERTAG", makeDate(year, 12, 26)) ];
}

function addRegionalHolidays(year, region, feiertageObjects) {
    if (region === "AUGSBURG") {
        feiertageObjects.push(newHoliday("AUGSBURGER_FRIEDENSFEST", makeDate(year, 8, 8)));
    }
}

function addHeiligeDreiKoenige(year, region, feiertageObjects) {
    if (region === "BW" || region === "BY" || region === "AUGSBURG" || region === "ST" || region === "ALL") {
        feiertageObjects.push(newHoliday("HEILIGEDREIKOENIGE", makeDate(year, 1, 6)));
    }
}

function addEasterAndPfingsten(year, region, easterDate, pfingstsonntag, feiertageObjects) {
    if (region === "BB" || region === "ALL") {
        feiertageObjects.push(newHoliday("OSTERSONNTAG", easterDate), newHoliday("PFINGSTSONNTAG", pfingstsonntag));
    }
}

function addFronleichnam(region, easterDate, holidays) {
    if (region === "BW" || region === "BY" || region === "AUGSBURG" || region === "HE" || region === "NW" || region === "RP" || region === "SL" || region === "ALL") {
        var fronleichnam = addDays(new Date(easterDate.getTime()), 60);
        holidays.push(newHoliday("FRONLEICHNAM", fronleichnam));
    }
}

function addMariaeHimmelfahrt(year, region, holidays) {
    if (region === "SL" || region === "BY" || region === "AUGSBURG" || region === "ALL") {
        holidays.push(newHoliday("MARIAHIMMELFAHRT", makeDate(year, 8, 15)));
    }
}

function addReformationstag(year, region, holidays) {
    if (year === 2017 || region === "NI" || region === "BB" || region === "HB" || region === "HH" || region === "MV" || region === "SN" || region === "ST" || region === "TH" || region === "SH" || region === "ALL") {
        holidays.push(newHoliday("REFORMATIONSTAG", makeDate(year, 10, 31)));
    }
}

function addAllerheiligen(year, region, holidays) {
    if (region === "BW" || region === "BY" || region === "AUGSBURG" || region === "NW" || region === "RP" || region === "SL" || region === "ALL") {
        holidays.push(newHoliday("ALLERHEILIGEN", makeDate(year, 11, 1)));
    }
}

function addBussUndBetttag(year, region, holidays) {
    if (region === "SN" || region === "ALL") {
        var bussbettag = getBussBettag(year);
        holidays.push(newHoliday("BUBETAG", makeDate(bussbettag.getUTCFullYear(), bussbettag.getUTCMonth() + 1, bussbettag.getUTCDate())));
    }
}

function addWeltkindertag(year, region, holidays) {
    if (year >= 2019 && (region === "TH" || region === "ALL")) {
        holidays.push(newHoliday("WELTKINDERTAG", makeDate(year, 9, 20)));
    }
}

function addWeltfrauenTag(year, region, feiertageObjects) {
    if (year <= 2018) {
        return;
    }
    if (region === "BE" || region === "ALL") {
        feiertageObjects.push(newHoliday("WELTFRAUENTAG", makeDate(year, 3, 8)));
    }
    if (region === "MV" && year >= 2023) {
        feiertageObjects.push(newHoliday("WELTFRAUENTAG", makeDate(year, 3, 8)));
    }
}

function getEasterDate(year) {
    var C = Math.floor(year / 100);
    var N = year - 19 * Math.floor(year / 19);
    var K = Math.floor((C - 17) / 25);
    var I = C - Math.floor(C / 4) - Math.floor((C - K) / 3) + 19 * N + 15;
    I -= 30 * Math.floor(I / 30);
    I -= Math.floor(I / 28) * (1 - Math.floor(I / 28) * Math.floor(29 / (I + 1)) * Math.floor((21 - N) / 11));
    var J = year + Math.floor(year / 4) + I + 2 - C + Math.floor(C / 4);
    J -= 7 * Math.floor(J / 7);
    var L = I - J;
    var M = 3 + Math.floor((L + 40) / 44);
    var D = L + 28 - 31 * Math.floor(M / 4);
    return new Date(year, M - 1, D);
}

function getBussBettag(jahr) {
    var weihnachten = new Date(jahr, 11, 25, 12, 0, 0);
    var ersterAdventOffset = 32;
    var wochenTagOffset = weihnachten.getDay() % 7;
    if (wochenTagOffset === 0) {
        wochenTagOffset = 7;
    }
    var tageVorWeihnachten = wochenTagOffset + ersterAdventOffset;
    var bbtag = new Date(weihnachten.getTime());
    bbtag = addDays(bbtag, -tageVorWeihnachten);
    return bbtag;
}

function addDays(date, days) {
    var changedDate = new Date(date);
    changedDate.setDate(date.getDate() + days);
    return changedDate;
}

function makeDate(year, naturalMonth, day) {
    return new Date(year, naturalMonth - 1, day);
}

function newHoliday(name, date) {
    return {
        name: name,
        date: date,
        dateString: localeDateObjectToDateString(date),
        trans: function trans() {
            var lang = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : currentLanguage;
            console.warn('FeiertageJs: You are using "Holiday.trans() method. This will be replaced in the next major version with translate()"');
            return this.translate(lang);
        },
        translate: function translate() {
            var lang = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : currentLanguage;
            return lang === undefined || lang === null ? undefined : translations[lang][this.name];
        },
        getNormalizedDate: function getNormalizedDate() {
            return toUtcTimestamp(this.date);
        },
        equals: function equals(otherDate) {
            var dateString = localeDateObjectToDateString(otherDate);
            return this.dateString === dateString;
        }
    };
}

function localeDateObjectToDateString(date) {
    var normalizedDate = new Date(date.getTime() - date.getTimezoneOffset() * 60 * 1e3);
    normalizedDate.setUTCHours(0, 0, 0, 0);
    return normalizedDate.toISOString().slice(0, 10);
}

function toUtcTimestamp(date) {
    var internalDate = new Date(date);
    internalDate.setHours(0, 0, 0, 0);
    return internalDate.getTime();
}

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
            const bookingCodeTitle = $("#title-timeframe-booking-codes");
            const showBookingCodes = $("#show-booking-codes");
            const createBookingCodesInput = $("#create-booking-codes");
            const bookingCodesDownload = $("#booking-codes-download");
            const bookingCodesList = $("#booking-codes-list");
            const holidayField = $(".cmb2-id--cmb2-holiday");
            const holidayInput = $("#timeframe_manual_date");
            const holidayButton = $("#holiday_load_btn");
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
                    showFieldset(bookingCodeTitle);
                } else {
                    maxDaysSelect.hide();
                    advanceBookingDays.hide();
                    allowUserRoles.hide();
                    hideFieldset(bookingCodeTitle);
                    if (selectedType == 3 && selectedRepetition == "manual") {} else {}
                }
            };
            handleTypeSelection();
            typeInput.change(function() {
                handleTypeSelection();
            });
            const handleRepititionSelection = function() {
                const selectedRepetition = $("option:selected", timeframeRepetitionInput).val();
                const selectedType = $("option:selected", typeInput).val();
                if (selectedRepetition !== "manual") {
                    manualDateField.hide();
                    holidayField.hide();
                    holidayInput.val("");
                } else {
                    manualDateField.show();
                    if (selectedType == 3) {
                        holidayField.show();
                    } else {
                        holidayField.hide();
                        holidayInput.val("");
                    }
                }
            };
            handleRepititionSelection();
            timeframeRepetitionInput.change(function() {
                handleRepititionSelection();
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
                    if (selectedType == "manual") {
                        manualDateField.show();
                        hideFieldset(repetitionStartInput);
                        hideFieldset(repetitionEndInput);
                    } else {
                        manualDateField.hide();
                        showFieldset(repetitionStartInput);
                        showFieldset(repetitionEndInput);
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
            const handleHolidayLoading = function() {
                const today = new Date();
                console.log(isHoliday(today, "BW"));
            };
            holidayButton.click(function() {
                handleHolidayLoading();
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