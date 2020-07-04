/**
 * @TODO: Reduce redundancy, use state machine
 */

document.addEventListener("DOMContentLoaded", function(event) {

    let bookingForm = $('#booking-form');
    if(bookingForm.length) {
        // Updates Time-selects so that no wrong time ranges can be selected
        const initSelectHandler = () => {
            const bookingForm = $('#booking-form');
            const startSelect = bookingForm.find('select[name=start-date]');
            const endSelect = bookingForm.find('select[name=end-date]');

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

        $.post(
            cb_ajax.ajax_url,
            {
                _ajax_nonce: cb_ajax.nonce,
                action: "calendar_data",
                item: $('#booking-form input[name=item-id]').val(),
                location: $('#booking-form input[name=location-id]').val()
            },
            function(data) {
                let picker = new Litepicker(
                    {
                        'minDate': data['startDate'],
                        'maxDate': data['endDate'],
                        "element": document.getElementById('litepicker'),
                        "inlineMode": true,
                        "firstDay": 1,
                        "lang": 'de-DE',
                        "numberOfMonths": 2,
                        "numberOfColumns": 2,
                        "singleMode": false,
                        "showWeekNumbers": false,
                        "autoApply": true,
                        "days": data['days'],
                        "lockDays": data['lockDays'],
                        "bookedDays": data['bookedDays'],
                        "partiallyBookedDays": data['partiallyBookedDays'],
                        "holidays": data['holidays'],
                        "bookedDaysInclusivity": "[]",
                        "anyBookedDaysAsCheckout": false,
                        "highlightedDays": data['highlightedDays'],
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
                            }
                        },
                        resetBtnCallback: () => {
                            $('#booking-form').hide();
                        },
                        // TODO: Implement month change.
                        onChangeMonth: function(date, idx) {
                            const startDate = moment(date.format('YYYY-MM-DD')).format('YYYY-MM-DD');
                            const endDate = moment(date.format('YYYY-MM-DD')).add('months', 2).date(0).format('YYYY-MM-DD');
                            console.log(startDate, endDate)

                            $.post(
                                cb_ajax.ajax_url,
                                {
                                    _ajax_nonce: cb_ajax.nonce,
                                    action: "calendar_data",
                                    item: $('#booking-form input[name=item-id]').val(),
                                    location: $('#booking-form input[name=location-id]').val(),
                                    sd: startDate,
                                    ed: endDate
                                },
                                function(data) {
                                    picker.setLockDays(data['lockDays']);
                                    picker.setBookedDays(data['bookedDays']);
                                    picker.setHolidays(data['holidays']);
                                    picker.setHighlightedDays(data['highlightedDays']);
                                    picker.setDateRange(new Date(data['startDate']), new Date(data['endDate']));
                                }
                            );
                        },
                        // TODO: Implement year change.
                        onChangeYear: function(date, idx) {
                        },
                        onSelect: function(date1, date2) {
                            let bookingForm = $('#booking-form');
                            bookingForm.show();
                            const day1 = data['days'][moment(date1).format('YYYY-MM-DD')];
                            const day2 = data['days'][moment(date2).format('YYYY-MM-DD')];
                            const startDate = moment(date1).format('DD.MM.YYYY');
                            const endDate = moment(date2).format('DD.MM.YYYY');

                            let startSelect = $('#booking-form select[name=start-date]');
                            $('.time-selection.start-date span.date').text(startDate);
                            updateSelectSlots(startSelect, day1['slots'], 'start', day1['fullDay']);
                            if(day1['fullDay']) {
                                $('.time-selection.start-date').find('label, select').hide();
                            } else {
                                $('.time-selection.start-date').find('label, select').show();
                            }

                            let endSelect = $('#booking-form select[name=end-date]');
                            $('.time-selection.end-date span.date').text(endDate);
                            updateSelectSlots(endSelect, day2['slots'], 'end', day2['fullDay']);
                            if(day2['fullDay']) {
                                $('.time-selection.end-date').find('label, select').hide();
                            } else {
                                $('.time-selection.end-date').find('label, select').show();
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
            }
        )
    }
});
