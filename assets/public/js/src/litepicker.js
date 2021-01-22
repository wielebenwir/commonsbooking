/**
 * @TODO: Reduce redundancy, use state machine
 */

document.addEventListener("DOMContentLoaded", function (event) {

    if(typeof data !== 'undefined') {
        // Assign data from outer html to local variable.
        let globalCalendarData = data;

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
            endSelect.find('option').each(function () {
                if (jQuery(this).val() < startValue) {
                    jQuery(this).attr('disabled', 'disabled');
                    jQuery(this).prop("selected", false)
                }
            });
        };

        // Updates select options by time slots array
        const updateSelectSlots = (select, slots, type = 'start', fullday = false) => {
            select.empty().attr('required', 'required');
           jQuery.each(slots, function (index, slot) {
                let option = new Option(slot['timestart'] + ' - ' + slot['timeend'], slot['timestamp' + type], fullday, fullday);
                if(slot['disabled']) {
                    option.disabled = true;
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
            const day1 = globalCalendarData['days'][moment(date).format('YYYY-MM-DD')];
            const startDate = moment(date).format('DD.MM.YYYY');

            // Hide select hint for start, show for end again
            jQuery('.time-selection.repetition-start').find('.hint-selection').hide();
            jQuery('.time-selection.repetition-end').find('.hint-selection').show();

            // Hide end date selection if new start date was chosen
            let endSelectData = jQuery(
                '#booking-form select[name=repetition-end],' +
                '#booking-form .time-selection.repetition-end .date'
            );
            endSelectData.hide();
            jQuery('#booking-form input[type=submit]').attr('disabled','disabled');

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
                "inlineMode": true,
                "firstDay": 1,
                "lang": 'de-DE',
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
                "mobileFriendly": true,
                "selectForward": true,
                "useResetBtn": true,
                "maxDays": globalCalendarData['maxDays'],
                "buttonText": {
                    apply: 'Buchen',
                    cancel: 'Abbrechen',
                },
                onAutoApply: (datePicked) => {
                    if(datePicked) {
                        jQuery('#booking-form').show();
                        jQuery('.cb-notice.date-select').hide();
                    }
                },
                resetBtnCallback: () => {
                    jQuery('#booking-form').hide();
                    jQuery('.cb-notice.date-select').show();
                },
                onChangeMonth: function(date, idx) {
                    fadeOutCalendar()
                    const startDate = moment(date.format('YYYY-MM-DD')).format('YYYY-MM-DD');
                    // Last day of month before
                    const calStartDate = moment(date.format('YYYY-MM-DD')).date(0).format('YYYY-MM-DD');
                    // first day of next month selection
                    const calEndDate = moment(date.format('YYYY-MM-DD')).add(numberOfMonths,'months').date(1).format('YYYY-MM-DD');

                   jQuery.post(
                        cb_ajax.ajax_url,
                        {
                            _ajax_nonce: cb_ajax.nonce,
                            action: "calendar_data",
                            item: jQuery('#booking-form input[name=item-id]').val(),
                            location: jQuery('#booking-form input[name=location-id]').val(),
                            sd: calStartDate,
                            ed: calEndDate
                        },
                        function(data) {
                            // Add new day-data to global calendar data object
                            globalCalendarData.days = {...globalCalendarData.days, ...data.days }

                            updatePicker(data);
                            picker.gotoDate(startDate);
                        }
                    );
                }
            });
            jQuery('#litepicker .litepicker').hide();

            // If orientation changes, update columns for calendar
            jQuery( window ).on( "orientationchange", function( event ) {
                updateCalendarColumns(picker);
            });
        };

        // update datepicker data
        const updatePicker = (globalCalendarData) => {
            fadeOutCalendar()
            picker.setOptions(
                {
                    "minDate": moment().isAfter(globalCalendarData['startDate']) ? moment().format('YYYY-MM-DD') : data['startDate'],
                    "maxDate": globalCalendarData['endDate'],
                    "days": globalCalendarData['days'],
                    "maxDays": globalCalendarData['maxDays'],
                    "lockDays": globalCalendarData['lockDays'],
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
                            jQuery('#fullDayInfo').text('');
                            initSelectHandler();
                        } else {
                            jQuery('#fullDayInfo').text(globalCalendarData['location']['fullDayInfo']);
                        }
                    }
                }
            );
            fadeInCalendar();
        };

        let bookingForm = jQuery('#booking-form');
        if(bookingForm.length) {
            if(typeof data !== 'undefined') {
                initPicker();
                updatePicker(globalCalendarData);
            }
        }
    }
});
