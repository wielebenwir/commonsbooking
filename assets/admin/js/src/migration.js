(function ($) {
    'use strict';
    $(function () {
        $('#cmb2-metabox-migration #migration-start').on('click', function (event) {
            event.preventDefault();
            $('#migration-state').show();
            $('#migration-in-progress').show();

            const runMigration = (data) => {
                $.post(
                    cb_ajax.ajax_url,
                    {
                        _ajax_nonce: cb_ajax.nonce,
                        action: "start_migration",
                        data: data,
                        geodata: $('#get-geo-locations').is(':checked')
                    },
                    function (data) {
                        let allComplete = true;
                        $.each(data, function (index, value) {
                            $('#' + index + '-index').text(value.index);
                            $('#' + index + '-count').text(value.count);
                            if (value.complete == "0") {
                                allComplete = false;
                            }
                        });

                        if (!allComplete) {
                            runMigration(data);
                        } else {
                            $('#migration-in-progress').hide();
                            $('#migration-done').show();
                        }
                    }
                );
            };
            runMigration(false);
        });

        $('#cmb2-metabox-migration #booking-update-start').on('click', function (event) {
            event.preventDefault();

            const runMigration = (data) => {
                $.post(
                    cb_ajax.ajax_url,
                    {
                        _ajax_nonce: cb_ajax.nonce,
                        action: "start_booking_migration"
                    }
                );
            };

            $('#booking-migration-in-progress').show();
            runMigration(false);
            $('#booking-migration-in-progress').hide();
            $('#booking-migration-done').show();
        })

    });
})(jQuery);
