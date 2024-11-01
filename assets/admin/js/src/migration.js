(function ($) {
    'use strict';
    $(function () {

        //conditionally hide entire field group "upgrade" if render function has not been called
        //(workaround for show_on_cb not working on field groups)
        if ($('#upgrade-fields').length == 0) {
            $('.cmb2-id-upgrade-header').hide();
        }

        $('#cmb2-metabox-migration #run-upgrade').on('click', function (event) {
            event.preventDefault();
            $('#upgrade-in-progress').show();
            $('#run-upgrade').hide();
            let data = {
                'progress' : {
                    'task': 0,
                    'page': 1,
                }
            };

            const runUpgrade = (data) => {
                $.post(
                    cb_ajax_run_upgrade.ajax_url,
                    {
                        _ajax_nonce: cb_ajax_run_upgrade.nonce,
                        action: "cb_run_upgrade",
                        data: data
                    },
                    function (data) {
                        if (data.success) {
                            $('#upgrade-in-progress').hide();
                            $('#upgrade-done').show();
                        } else {
                            runUpgrade(data)
                        }
                    });
            };
            runUpgrade(data);
        });

        $('#cmb2-metabox-migration #migration-start').on('click', function (event) {
            event.preventDefault();
            $('#migration-state').show();
            $('#migration-in-progress').show();

            const runMigration = (data) => {
                $.post(
                    cb_ajax_start_migration.ajax_url,
                    {
                        _ajax_nonce: cb_ajax_start_migration.nonce,
                        action: "cb_start_migration",
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

            $('#booking-migration-in-progress').show();

            $.post(
                cb_ajax_start_migration.ajax_url,
                {
                    _ajax_nonce: cb_ajax_start_migration.nonce,
                    action: "cb_start_booking_migration"
                }
            ).done(function () {
                $('#booking-migration-in-progress').hide();
                $('#booking-migration-done').show();
            }).fail(function () {
                $('#booking-migration-in-progress').hide();
                $('#booking-migration-failed').show();
            });
        })
    });
})(jQuery);
