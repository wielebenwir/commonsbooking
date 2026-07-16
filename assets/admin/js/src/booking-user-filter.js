(function ($) {
    'use strict';

    $(function () {
        const config = window.cbBookingUserFilter;
        if (!config) {
            return;
        }

        const input = $('#' + config.inputId);
        const userIdInput = $('#' + config.userIdInputId);
        if (
            input.length !== 1 ||
            userIdInput.length !== 1 ||
            typeof input.autocomplete !== 'function'
        ) {
            return;
        }

        input.on('input', function () {
            userIdInput.val('');
        });

        input.autocomplete({
            delay: 250,
            minLength: config.minimumLength,
            source(request, respond) {
                $.ajax({
                    url: config.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: config.action,
                        nonce: config.nonce,
                        term: request.term,
                    },
                })
                    .done(function (response) {
                        const results =
                            response && response.success && Array.isArray(response.data)
                                ? response.data
                                : [];
                        respond(results);
                    })
                    .fail(function () {
                        respond([]);
                    });
            },
            focus(event, ui) {
                input.attr('aria-activedescendant', 'cb-booking-user-' + ui.item.id);
                event.preventDefault();
            },
            select(event, ui) {
                input.val(ui.item.value);
                userIdInput.val(ui.item.id);
                return false;
            },
            open() {
                input.attr('aria-expanded', 'true');
            },
            close() {
                input.attr('aria-expanded', 'false');
            },
            messages: {
                noResults: config.noResults,
                results(count) {
                    return count === 1
                        ? config.oneResult
                        : config.multipleResults.replace('%d', count);
                },
            },
        });

        const autocomplete = input.autocomplete('instance');
        if (!autocomplete) {
            return;
        }

        const widget = input.autocomplete('widget');
        input
            .attr({
                role: 'combobox',
                'aria-autocomplete': 'list',
                'aria-expanded': 'false',
                'aria-controls': widget.attr('id'),
            })
            .on('keydown', function () {
                input.removeAttr('aria-activedescendant');
            });

        widget
            .addClass('cb-booking-user-autocomplete')
            .attr('role', 'listbox')
            .removeAttr('tabindex');

        autocomplete._renderItem = function (list, item) {
            return $('<li>', {
                id: 'cb-booking-user-' + item.id,
                role: 'option',
            })
                .append($('<div>').text(item.label))
                .appendTo(list);
        };
    });
})(jQuery);
