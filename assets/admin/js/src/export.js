(function ($) {
    'use strict';
    $(function () {
        let typeInput = $('#export-type');
        let locationFields = $('#location-fields');
        let itemFields = $('#item-fields');
        let userFields = $('#user-fields');
        let exportTimerangeStart = $('#export-timerange-start');
        let exportTimerangeEnd = $('#export-timerange-end');
        let inProgress = $('#timeframe-export-in-progress');
        let inProgressSpan = $('#timeframe-export-in-progress span');
        let done = $('#timeframe-export-done');
        let failed = $('#timeframe-export-failed');
        let failedSpan = $('#timeframe-export-failed span');
        let doneSpan = $('#timeframe-export-done span');
        $('#timeframe-export-start').on('click', function (event) {
            event.preventDefault();

            let settings = {
                exportType: typeInput.val(),
                locationFields: locationFields.val(),
                itemFields: itemFields.val(),
                userFields: userFields.val(),
                exportStartDate: exportTimerangeStart.val(),
                exportEndDate: exportTimerangeEnd.val(),
            };
            let progress = "0/0 bookings exported";
            let data = {
                settings: settings, progress: progress
            };

            // Initialize visibility
            doneSpan.hide();
            failedSpan.hide();
            inProgress.show();


            const runExport = (data) => {
                $.post(cb_ajax_export_timeframes.ajax_url, {
                    _ajax_nonce: cb_ajax_export_timeframes.nonce, action: "cb_export_timeframes", data: data
                }, function (data) {
                    if (data.success) {
                        done.show();
                        doneSpan.text(data.message);
                        inProgress.hide();
                        const blob = new Blob([data.csv]);
                        const filename = data.filename;
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = filename;
                        link.click();
                    } else if (data.error) {
                        failed.show();
                        failedSpan.text(data.message);
                        inProgress.hide();
                    } else {
                        inProgressSpan.text(data.progress);
                        //run the export until it's done
                        runExport(data);
                    }
                });
            };
            runExport(data);
        });
    });
})(jQuery);