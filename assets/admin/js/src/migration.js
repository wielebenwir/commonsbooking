(function ($) {
    'use strict';
    $(function () {
        $('#cmb2-metabox-migration #migration-start').on('click', function (event) {
            event.preventDefault();

            $('#migration-state').show();

            const runMigration = (data) => {
                $.post(
                    cb_ajax.ajax_url,
                    {
                        _ajax_nonce: cb_ajax.nonce,
                        action: "start_migration",
                        data: data
                    },
                    function (data) {
                        let allComplete = true;
                        $.each(data, function (index, value) {
                            $('#' + index + '-count').text(value.index);
                            if (value.complete == "0") {
                                allComplete = false;
                            }
                        });

                        if (!allComplete) {
                            runMigration(data);
                        } else {
                            $('#migration-done').show();
                        }
                    }
                );
            };
            runMigration(false);
        })
    });
})(jQuery);
