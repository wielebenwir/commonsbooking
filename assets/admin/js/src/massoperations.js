(function ($) {
    'use strict';
    $(function () {

        $('#orphans-migration-start').on('click', function (event) {
            event.preventDefault();

            $('#orphans-migration-in-progress').show();

            let checkedBoxes = $('.post-checkboxes:checkbox:checked');
            let ids = [];
            checkedBoxes.each(function () {
                ids.push($(this).val());
            });
            let data = ids;

            $.post(
                cb_ajax_orphaned_booking_migration.ajax_url,
                {
                    _ajax_nonce: cb_ajax_orphaned_booking_migration.nonce,
                    action: "cb_orphaned_booking_migration",
                    data: data
                }).done(function (data) {
                if (data.success) {
                    $('#orphans-migration-in-progress').hide();
                    $('#orphans-migration-done').show();
                    $('#orphans-migration-done span').text(data.message);
                    //now remove all rows that have been migrated
                    ids.forEach(function (id) {
                        $('#row-booking-' + id).remove();
                    });
                } else {
                    $('#orphans-migration-in-progress').hide();
                    $('#orphans-migration-failed').show();
                    $('#orphans-migration-failed span').text(data.message);
                }
            });
        })
    });
})(jQuery);
