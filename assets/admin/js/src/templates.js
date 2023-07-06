(function ($) {
    'use strict';
    $(function () {
        const hideFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").hide();
            });
        };
        const showFieldset = function(set) {
            $.each(set, function() {
                $(this).parents(".cmb-row").show();
            });
        };
        const emailform =  $("#templates");
        if (emailform.length) {
            const eventCreateCheckbox = $('#emailtemplates_mail-booking_ics_attach');
            const eventTitleInput =  $('#emailtemplates_mail-booking_ics_event-title');
            const eventDescInput = $('#emailtemplates_mail-booking_ics_event-description');
            const eventFieldSet = [eventTitleInput,eventDescInput];    

            const handleiCalAttachmentSelection = function() {
                showFieldset(eventFieldSet);
                if (!eventCreateCheckbox.prop("checked")) {
                    hideFieldset(eventFieldSet);
                    eventCreateCheckbox.prop("checked", false);
                }
            };        

            handleiCalAttachmentSelection();
            
            eventCreateCheckbox.click(function() {
                handleiCalAttachmentSelection();
            });
        }
    });
})(jQuery);
