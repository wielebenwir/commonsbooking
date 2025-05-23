/**
 * @TODO: Reduce redundancy, use state machine
 */

document.addEventListener("DOMContentLoaded", function (event) {

    if (typeof calendarData !== 'undefined') {
        // Assign data from outer html to local variable.
        let globalCalendarData = calendarData;
        let globalPickedStartDate = false;

        const fadeOutCalendar = () => {
            jQuery('#litepicker .litepicker .container__days').css('visibility', 'hidden');
        }

        const fadeInCalendar = () => {
            jQuery('#litepicker .litepicker .container__days').fadeTo('fast', 1);
        }

        // Updates Time-selects so that no wrong time ranges can be selected
        const initSelectHandler = () => {
            const startSelect = bookingForm.find('select[name=repetition-start]');
            startSelect.change(function () {
                updateEndSelectTimeOptions();
            });
        };

        // Update End-Date Select, so that only relevant selections are possible
        const updateEndSelectTimeOptions = () => {
            const bookingForm = jQuery('#booking-form');
            const startSelect = bookingForm.find('select[name=repetition-start]');
            const endSelect = bookingForm.find('select[name=repetition-end]');
            const startValue = startSelect.val();
            let bookedElementBefore = false;
            let firstAvailableOptionSelected = false;

            endSelect.find('option').each(function () {
                // Disable element if its smaller than startvalue, booked or if there is an disabled element before
                if (jQuery(this).val() < startValue || bookedElementBefore || this.dataset.booked == "true") {
                    jQuery(this).attr('disabled', 'disabled');
                    jQuery(this).prop("selected", false)
                } else {
                    jQuery(this).removeAttr('disabled');
                    if (!firstAvailableOptionSelected) {
                        jQuery(this).prop("selected", true);
                        firstAvailableOptionSelected = true;
                    }
                }

                // Check if current item is booked AND bigger than startValue
                if (jQuery(this).val() > startValue && this.dataset.booked == "true") {
                    bookedElementBefore = true;
                }
            });
        };

        // Updates select options by time slots array
        const updateSelectSlots = (select, slots, type = 'start', fullday = false) => {
            select.empty().attr('required', 'required');
            jQuery.each(slots, function (index, slot) {
                let option = new Option(slot['timestart'] + ' - ' + slot['timeend'], slot['timestamp' + type], fullday, fullday);
                if (slot['disabled']) {
                    option.disabled = true;
                }
                if (slot['timeframe']['locked']) {
                    option.disabled = true;
                    option.dataset.booked = true;
                }
                select.append(option);
            });
        };

        const isMobile = () => {
            const isPortrait = getOrientation() === 'portrait';
            return window.matchMedia(`(max-device-${isPortrait ? 'width' : 'height'}: ${480}px)`).matches;
        }

        const getOrientation = () => {
            if (window.matchMedia('(orientation: portrait)').matches) {
                return 'portrait';
            }
            return 'landscape';
        }

        // Init start date selection
        const initStartSelect = (date) => {
            globalPickedStartDate = date;
            const day1 = globalCalendarData['days'][moment(date).format('YYYY-MM-DD')];
            const startDate = moment(date).format('DD.MM.YYYY');

            // Hide select hint for start, show for end again
            jQuery('.time-selection.repetition-start').find('.hint-selection').hide();
            jQuery('.time-selection.repetition-end').find('.hint-selection').show();

            // Show reset button as first calender selection is done
            jQuery('#resetPicker').css("display", "inline-block");

            // Show calendarNotice as first calender selection is done
            jQuery('#calendarNotice').css("display", "inherit");

            // Hide end date selection if new start date was chosen
            let endSelectData = jQuery(
                '#booking-form select[name=repetition-end],' +
                '#booking-form .time-selection.repetition-end .date'
            );
            endSelectData.hide();
            jQuery('#booking-form input[type=submit]').attr('disabled', 'disabled');

            // update select slots
            let startSelect = jQuery('#booking-form select[name=repetition-start]');
            jQuery('.time-selection.repetition-start span.date').text(startDate);
            updateSelectSlots(startSelect, day1['slots'], 'start', day1['fullDay']);

            // hide time selection if we have a full day slot
            if (day1['fullDay']) {
                jQuery('.time-selection.repetition-start').find('select').hide();
            } else {
                jQuery('.time-selection.repetition-start').find('select').show();
            }
        }

        // // update start select slots to avoid invalid timeslot selections
        const updateStartSelect = () => {
            const sameDay = jQuery('div.repetition-start span.date').text() === jQuery('div.repetition-end span.date').text();

            // only if it's not one single day selected we need to check
            if (!sameDay) {
                jQuery.fn.reverse = [].reverse;
                const startSelect = jQuery('#booking-form select[name=repetition-start]');
                var startHasDisabled = false
                jQuery('option', startSelect).each(
                    function () {
                        if (jQuery(this).attr('disabled') === 'disabled') {
                            startHasDisabled = true
                        }
                    }
                )

                // if there are booked/disabled slots in start selection, we need to disable all slots before
                // the last disabled slot and set the following one to selected
                if (startHasDisabled) {
                    var lastOption = false;
                    jQuery('option', startSelect).reverse().each(function () {
                        let self = jQuery(this)

                        if (lastOption && lastOption.attr('disabled') === 'disabled') {
                            self.attr('disabled', 'disabled');
                        } else {
                            if (self.attr('disabled') !== 'disabled') {
                                self.attr('selected', 'selected')
                            }

                            lastOption = self
                        }
                    });
                }
            }
        }

        // Init end date selection
        const initEndSelect = (date) => {
            const day2 = globalCalendarData['days'][moment(date).format('YYYY-MM-DD')];
            const endDate = moment(date).format('DD.MM.YYYY');

            // Hide select hint
            jQuery('.time-selection.repetition-end').find('.hint-selection').hide();

            // update select slots
            let endSelect = jQuery('#booking-form select[name=repetition-end]');
            jQuery('.time-selection.repetition-end span.date').text(endDate);
            updateSelectSlots(endSelect, day2['slots'], 'end', day2['fullDay']);

            // show end date selection if new start date was chosen
            let endSelectData = jQuery(
                '#booking-form select[name=repetition-end],' +
                '#booking-form .time-selection.repetition-end .date'
            );
            endSelectData.show();
            jQuery('#booking-form input[type=submit]').removeAttr('disabled');

            updateEndSelectTimeOptions();

            // hide time selection if we have a full day slot
            if (day2['fullDay']) {
                jQuery('.time-selection.repetition-end').find('select').hide();
            } else {
                jQuery('.time-selection.repetition-end').find('select').show();
            }
        }

        /**
         * Count overbooked days (holiday or lock-day) in range from calendar data
         * @param start
         * @param end
         */
        const countOverbookedDays = (start,end) => {
            const startDay     = globalCalendarData['days'][moment(start).format('YYYY-MM-DD')];
            const endDay       = globalCalendarData['days'][moment(end).format('YYYY-MM-DD')];
            let startDate      = globalCalendarData['days'][moment(start).format('YYYY-MM-DD')];
            let endDate        = globalCalendarData['days'][moment(end).format('YYYY-MM-DD')];
            let overbookedDays = 0;
            for (let day in globalCalendarData['days']) {
                //iterate through all days in range and count the overbooked days (either holidays or locked days)
                if (moment(day).isBetween(moment(start).format('YYYY-MM-DD'), moment(end).format('YYYY-MM-DD'))) {
                    if (globalCalendarData['days'][day]['holiday'] || globalCalendarData['days'][day]['locked']) {
                        overbookedDays++;
                    }
                }
            }
            jQuery('input[name="days-overbooked"]').val(overbookedDays);
        }

        // returns columns in relation to viewport
        const getCalendarColumns = () => {
            let columns = 2;
            if (isMobile()) {
                columns = 1;

                // Landscape mode
                if (window.innerHeight < window.innerWidth) {
                    columns = 2;
                }
            }
            return columns;
        };

        // updates columns for calendar
        const updateCalendarColumns = (picker) => {
            picker.setOptions({
                "numberOfMonths": getCalendarColumns(),
                "numberOfColumns": getCalendarColumns()
            });
        };

        // init datepicker
        let numberOfMonths = getCalendarColumns();
        let numberOfColumns = numberOfMonths;

        let picker = false;
        const initPicker = () => {
            picker = new Litepicker({
                "element": document.getElementById('litepicker'),
                "minDate": moment().format('YYYY-MM-DD'),
                "startDate": moment().isAfter(globalCalendarData['startDate']) ? moment().format('YYYY-MM-DD') : globalCalendarData['startDate'],
                "scrollToDate": true,
                "inlineMode": true,
                "firstDay": 1,
                "countLockedDays": globalCalendarData['countLockDaysInRange'],
                "countLockedDaysMax": globalCalendarData['countLockDaysMaxDays'],
                "lang": globalCalendarData['lang'],
                "numberOfMonths": numberOfMonths,
                "numberOfColumns": numberOfColumns,
                "moveByOneMonth": true,
                "singleMode": false,
                "showWeekNumbers": false,
                "autoApply": true,
                "bookedDaysInclusivity": "[]",
                "anyBookedDaysAsCheckout": false,
                "disallowBookedDaysInRange": true,
                "disallowPartiallyBookedDaysInRange": true,
                "disallowLockDaysInRange": globalCalendarData['disallowLockDaysInRange'],
                "disallowHolidaysInRange": globalCalendarData['disallowLockDaysInRange'], //we treat holidays as locked days
                "mobileFriendly": true,
                "mobileCalendarMonthCount": globalCalendarData['mobileCalendarMonthCount'],
                "selectForward": true,
                "useResetBtn": true,
                "maxDays": globalCalendarData['maxDays'],
                "buttonText": {
                    apply: globalCalendarData['i18n.buttonText.apply'],
                    cancel: globalCalendarData['i18n.buttonText.cancel'],
                },
                onAutoApply: (datePicked) => {
                    if (datePicked) {
                        jQuery('#booking-form').show();
                        jQuery('.cb-notice.date-select').hide();
                    }
                },
                resetBtnCallback: () => {
                    jQuery('#booking-form').hide();
                    jQuery('.cb-notice.date-select').show();
                },
                onChangeMonth: function (date, idx) {
                    fadeOutCalendar()
                    const startDate = moment(date.format('YYYY-MM-DD')).format('YYYY-MM-DD');
                    // Last day of month before
                    const calStartDate = moment(date.format('YYYY-MM-DD')).date(0).format('YYYY-MM-DD');
                    // first day of next month selection
                    const calEndDate = moment(date.format('YYYY-MM-DD')).add(numberOfMonths, 'months').date(1).format('YYYY-MM-DD');

                    jQuery.post(
                        cb_ajax.ajax_url,
                        {
                            _ajax_nonce: cb_ajax.nonce,
                            action: "cb_calendar_data",
                            item: jQuery('#booking-form input[name=item-id]').val(),
                            location: jQuery('#booking-form input[name=location-id]').val(),
                            sd: calStartDate,
                            ed: calEndDate
                        },
                        function (data) {
                            // Add new day-data to global calendar data object
                            jQuery.extend(globalCalendarData.days, data.days);

                            updatePicker(data);
                            picker.gotoDate(startDate);
                        }
                    );
                }
            });
            jQuery('#litepicker .litepicker').hide();

            // If orientation changes, update columns for calendar
            jQuery(window).on("orientationchange", function (event) {
                updateCalendarColumns(picker);
            });
        };

        // update datepicker data
        const updatePicker = (globalCalendarData) => {
            fadeOutCalendar()
            picker.setOptions(
                {
                    "minDate": moment().isAfter(globalCalendarData['startDate']) ? moment().format('YYYY-MM-DD') : globalCalendarData['startDate'],
                    "maxDate": globalCalendarData['endDate'],
                    "startDate": moment().isAfter(globalCalendarData['startDate']) ? moment().format('YYYY-MM-DD') : globalCalendarData['startDate'],
                    "days": globalCalendarData['days'],
                    "maxDays": globalCalendarData['maxDays'],
                    "lockDays": globalCalendarData['lockDays'],
                    "countLockedDays": globalCalendarData['countLockDaysInRange'],
                    "bookedDays": globalCalendarData['bookedDays'],
                    "partiallyBookedDays": globalCalendarData['partiallyBookedDays'],
                    "highlightedDays": globalCalendarData['highlightedDays'],
                    "holidays": globalCalendarData['holidays'],
                    onDaySelect: function (date, datepicked) {
                        if (
                            datepicked >= 0
                        ) {
                            let bookingForm = jQuery('#booking-form');
                            bookingForm.show();

                            // Start-Date selected or End-Date == Start-Date selected
                            if (datepicked == 1) {
                                initStartSelect(date);
                                // Start-Date !== End-Date
                                jQuery('.cb-notice.date-select').hide();
                            }

                            // End-Date Selected
                            if (datepicked == 2) {
                                initEndSelect(date);
                                updateStartSelect();
                                countOverbookedDays(globalPickedStartDate,date);
                            }
                        }
                    },
                    onSelect: function (date1, date2) {
                        let bookingForm = jQuery('#booking-form');
                        bookingForm.show();
                        jQuery('.cb-notice.date-select').hide();

                        const day1 = globalCalendarData['days'][moment(date1).format('YYYY-MM-DD')];
                        const day2 = globalCalendarData['days'][moment(date2).format('YYYY-MM-DD')];

                        initEndSelect(date2);

                        if (!day1['fullDay'] || !day2['fullDay']) {
                            initSelectHandler();
                        }
                    }
                }
            );
            fadeInCalendar();
        };

        // Resets date selection
        const resetDatepickerSelection = () => {
            picker.clearSelection();
            globalPickedStartDate = false;
            jQuery('.hint-selection').show();
            jQuery('.time-selection .date').text('');
            jQuery('.time-selection select').hide();
            jQuery('#resetPicker').hide();
            jQuery('#calendarNotice').hide();
            jQuery('#booking-form input[type=submit]').attr('disabled', 'disabled');
        }

        // Click handler for reset button
        jQuery('#resetPicker').on('click', function (e) {
            e.preventDefault();
            resetDatepickerSelection();
        })

        let bookingForm = jQuery('#booking-form');
        if (bookingForm.length) {
            initPicker();
            updatePicker(globalCalendarData);
        }

    }
});
