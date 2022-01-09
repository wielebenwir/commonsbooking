(function ($) {
    'use strict';
    $(function () {
        const form = $('input[name=post_type][value=cb_restriction]').parent('form');

        // disable send button on change of form
        form.find('input, select, textarea').on('keyup change paste', function () {
            form.find('input[name=restriction-send]').prop("disabled", true);
        });

    });
})(jQuery);
