(function ($) {
    'use strict';
    $(function () {

        $('#orphans-migration-start').on('click', function (event) {
            event.preventDefault();

            $('#orphans-migration-in-progress').show();

            $.post(
                cb_ajax_orphaned_booking_migration.ajax_url,
                {
                    _ajax_nonce: cb_ajax_orphaned_booking_migration.nonce,
                    action: "cb_orphaned_booking_migration"
                }
            ).done(function () {
                $('#orphans-migration-in-progress').hide();
                $('#orphans-migration-done').show();
            }).fail(function () {
                $('#orphans-migration-in-progress').hide();
                $('#orphans-migration-failed').show();
            });
        })
    });
})(jQuery);
