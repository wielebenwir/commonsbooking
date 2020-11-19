/**
 * @TODO: Reduce redundancy, use state machine
 */

document.addEventListener("DOMContentLoaded", function (event) {

    const fadeOutCalendar = () => {
        $('#litepicker .litepicker .container__days').css('visibility', 'hidden');
    }

    const fadeInCalendar = () => {
        $('#litepicker .litepicker .container__days').fadeTo('fast', 1);
    }

    // Updates Time-selects so that no wrong time ranges can be selected
    const initSelectHandler = () => {
        const bookingForm = $('#booking-form');
        const startSelect = bookingForm.find('select[name=repetition-start]');
        const endSelect = bookingForm.find('select[name=repetition-end]');

        startSelect.change(function () {
            const startValue = $(this).val();
            endSelect.find('option').each(function () {
                if ($(this).val() < startValue) {
                    $(this).attr('disabled', 'disabled');
                    $(this).prop("selected", false)
                } else {
                    $(this).removeAttr('disabled');
                }
            });
        });
    };

    // Updates select options by time slots array
    const updateSelectSlots = (select, slots, type = 'start', fullday = false) => {
        select.empty().attr('required', 'required');
        $.each(slots, function (index, slot) {
            select.append(
                new Option(slot['timestart'] + ' - ' + slot['timeend'], slot['timestamp' + type], fullday, fullday)
            );
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
        const day1 = data['days'][moment(date).format('YYYY-MM-DD')];
        const startDate = moment(date).format('DD.MM.YYYY');

        // Hide select hint for start, show for end again
        $('.time-selection.repetition-start').find('.hint-selection').hide();
        $('.time-selection.repetition-end').find('.hint-selection').show();

        // Hide end date selection if new start date was chosen
        let endSelectData = $(
            '#booking-form select[name=repetition-end],' +
            '#booking-form .time-selection.repetition-end .date'
        );
        endSelectData.hide();
        $('#booking-form input[type=submit]').attr('disabled','disabled');

        // update select slots
        let startSelect = $('#booking-form select[name=repetition-start]');
        $('.time-selection.repetition-start span.date').text(startDate);
        updateSelectSlots(startSelect, day1['slots'], 'start', day1['fullDay']);

        // hide time selection if we have a full day slot
        if (day1['fullDay']) {
            $('.time-selection.repetition-start').find('select').hide();
        } else {
            $('.time-selection.repetition-start').find('select').show();
        }
    }

    // Init end date selection
    const initEndSelect = (date) => {
        const day2 = data['days'][moment(date).format('YYYY-MM-DD')];
        const endDate = moment(date).format('DD.MM.YYYY');

        // Hide select hint
        $('.time-selection.repetition-end').find('.hint-selection').hide();

        // update select slots
        let endSelect = $('#booking-form select[name=repetition-end]');
        $('.time-selection.repetition-end span.date').text(endDate);
        updateSelectSlots(endSelect, day2['slots'], 'end', day2['fullDay']);

        // show end date selection if new start date was chosen
        let endSelectData = $(
            '#booking-form select[name=repetition-end],' +
            '#booking-form .time-selection.repetition-end .date'
        );
        endSelectData.show();
        $('#booking-form input[type=submit]').removeAttr('disabled');

        // hide time selection if we have a full day slot
        if (day2['fullDay']) {
            $('.time-selection.repetition-end').find('select').hide();
        } else {
            $('.time-selection.repetition-end').find('select').show();
        }
    }

    // init datepicker
    let numberOfMonths = 2;
    let numberOfColumns = 2;

    if (isMobile()) {
        switch (screen.orientation.angle) {
            case -90:
            case 90:
                numberOfMonths = 2;
                numberOfColumns = 2;
                break;

            default:
                numberOfMonths = 1;
                numberOfColumns = 1;
                break;
        }
    }

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
            "disallowLockDaysInRange": data['disallowLockDaysInRange'],
            "mobileFriendly": true,
            "selectForward": true,
            "useResetBtn": true,
            "maxDays": data['maxDays'],
            "buttonText": {
                apply: 'Buchen',
                cancel: 'Abbrechen',
            },
            onAutoApply: (datePicked) => {
                if(datePicked) {
                    $('#booking-form').show();
                    $('.cb-notice.date-select').hide();
                }
            },
            resetBtnCallback: () => {
                $('#booking-form').hide();
                $('.cb-notice.date-select').show();
            },
            onChangeMonth: function(date, idx) {
                fadeOutCalendar()
                const startDate = moment(date.format('YYYY-MM-DD')).format('YYYY-MM-DD');
                // Last day of month before
                const calStartDate = moment(date.format('YYYY-MM-DD')).date(0).format('YYYY-MM-DD');
                // first day of next month selection
                const calEndDate = moment(date.format('YYYY-MM-DD')).add(numberOfMonths,'months').date(1).format('YYYY-MM-DD');

                $.post(
                    cb_ajax.ajax_url,
                    {
                        _ajax_nonce: cb_ajax.nonce,
                        action: "calendar_data",
                        item: $('#booking-form input[name=item-id]').val(),
                        location: $('#booking-form input[name=location-id]').val(),
                        sd: calStartDate,
                        ed: calEndDate
                    },
                    function(data) {
                        updatePicker(data);
                        picker.gotoDate(startDate);
                    }
                );
            }
        });
        $('#litepicker .litepicker').hide();
    };


    // update datepicker data
    const updatePicker = (data) => {
        fadeOutCalendar()
        picker.setOptions(
            {
                "minDate": moment().isAfter(data['startDate']) ? moment().format('YYYY-MM-DD') : data['startDate'],
                "maxDate": data['endDate'],
                "days": data['days'],
                "maxDays": data['maxDays'],
                "lockDays": data['lockDays'],
                "bookedDays": data['bookedDays'],
                "partiallyBookedDays": data['partiallyBookedDays'],
                "highlightedDays": data['highlightedDays'],
                "holidays": data['holidays'],
                onDaySelect: function (date, datepicked) {
                    if (
                        datepicked >= 0
                    ) {
                        let bookingForm = $('#booking-form');
                        bookingForm.show();

                        // Start-Date selected or End-Date == Start-Date selected
                        if (datepicked == 1) {
                            initStartSelect(date);
                            // Start-Date !== End-Date
                            $('.cb-notice.date-select').hide();
                        }
                        
                        // End-Date Selected
                        if (datepicked == 2) {
                            initEndSelect(date);
                        }
                    }
                },
                onSelect: function (date1, date2) {
                    let bookingForm = $('#booking-form');
                    bookingForm.show();
                    $('.cb-notice.date-select').hide();

                    const day1 = data['days'][moment(date1).format('YYYY-MM-DD')];
                    const day2 = data['days'][moment(date2).format('YYYY-MM-DD')];

                    initStartSelect(date1);
                    initEndSelect(date2);

                    if (!day1['fullDay'] || !day2['fullDay']) {
                        $('#fullDayInfo').text('');
                        initSelectHandler();
                    } else {
                        $('#fullDayInfo').text(data['location']['fullDayInfo']);
                    }
                }
            }
        );
        fadeInCalendar();
    };

    let bookingForm = $('#booking-form');
    if(bookingForm.length) {
        if(typeof data !== 'undefined') {
            initPicker();
            updatePicker(data);
        }
    }
});
