/**
 * @TODO: Reduce redundancy, use state machine
 */

document.addEventListener("DOMContentLoaded", function(event) {
    // Updates Time-selects so that no wrong time ranges can be selected
    const initSelectHandler = () => {
        const bookingForm = $('#booking-form');
        const startSelect = bookingForm.find('select[name=repetition-start]');
        const endSelect = bookingForm.find('select[name=repetition-end]');

        startSelect.change(function () {
            const startValue = $(this).val();
            endSelect.find('option').each(function () {
                if($(this).val() < startValue) {
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
        select.empty().attr('required','required');
        $.each(slots, function(index, slot) {
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

    // init datepicker
    let numberOfMonths = 2;
    let numberOfColumns = 2;

    if(isMobile()) {
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

    let picker = new Litepicker({
        "element": document.getElementById('litepicker'),
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
        "disallowLockDaysInRange": true,
        "mobileFriendly": true,
        "selectForward": true,
        "useResetBtn": true,
        maxDays: 5,
        'buttonText': {
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

    // update datepicker data
    const updatePicker = (data) => {
        picker.setOptions(
            {
                'minDate': data['startDate'],
                'maxDate': data['endDate'],
                "days": data['days'],
                "lockDays": data['lockDays'],
                "bookedDays": data['bookedDays'],
                "partiallyBookedDays": data['partiallyBookedDays'],
                "highlightedDays": data['highlightedDays'],
                "holidays": data['holidays'],
                onSelect: function(date1, date2) {
                    let bookingForm = $('#booking-form');
                    bookingForm.show();
                    $('.cb-notice.date-select').hide();
                    
                    const day1 = data['days'][moment(date1).format('YYYY-MM-DD')];
                    const day2 = data['days'][moment(date2).format('YYYY-MM-DD')];
                    const startDate = moment(date1).format('DD.MM.YYYY');
                    const endDate = moment(date2).format('DD.MM.YYYY');

                    let startSelect = $('#booking-form select[name=repetition-start]');
                    $('.time-selection.repetition-start span.date').text(startDate);
                    updateSelectSlots(startSelect, day1['slots'], 'start', day1['fullDay']);
                    if(day1['fullDay']) {
                        $('.time-selection.repetition-start').find('label, select').hide();
                    } else {
                        $('.time-selection.repetition-start').find('label, select').show();
                    }

                    let endSelect = $('#booking-form select[name=repetition-end]');
                    $('.time-selection.repetition-end span.date').text(endDate);
                    updateSelectSlots(endSelect, day2['slots'], 'end', day2['fullDay']);
                    if(day2['fullDay']) {
                        $('.time-selection.repetition-end').find('label, select').hide();
                    } else {
                        $('.time-selection.repetition-end').find('label, select').show();
                    }

                    if(!day1['fullDay'] || !day2['fullDay']) {
                        $('#fullDayInfo').text('');
                        initSelectHandler();
                    } else {
                        $('#fullDayInfo').text(data['location']['fullDayInfo']);
                    }
                }
            }
        );
    };

    let bookingForm = $('#booking-form');
    if(bookingForm.length) {
        const startDate = moment().format('YYYY-MM-DD');
        const calStartDate = moment().date(1).format('YYYY-MM-DD');
        const calEndDate = moment().add(numberOfMonths + 2,'months').date(0).format('YYYY-MM-DD');
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
                picker.gotoDate(startDate)
            }
        )
    }
});
