(function ($) {
    'use strict';
    $(function () {
        const mapSettingsForm = $("#cmb2-metabox-cb_map-custom-fields");

        const hideFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').hide();
            });
        };

        /**
         * Show set-elements.
         * @param set
         */
        const showFieldset = function (set) {
            $.each(set, function () {
                $(this).parents('.cmb-row').show();
            });
        };

        const copyToClipboard = function (element) {
            let code = $(element).find('code')[0];
            let text = code.innerText;
            navigator.clipboard.writeText(text).then(function () {
                let button = $(element).find('.button');
                let buttonText = button.text();
                button.text('✓');
                button.disabled = true;
                setTimeout(function () {
                    button.text(buttonText);
                    button.disabled = false;
                }, 2000);
            });
        }

        const copyToClipboardButton = $('#shortcode-field').find('.button');
        copyToClipboardButton.on('click', function () {
            copyToClipboard($('#shortcode-field'));
        });

        function handleCustomFileInput(fileSelectorID, fileInputFields) {
            const markerFileSelect = document.querySelector(fileSelectorID);
            const handleSelectCustomMarker = function () {
                showFieldset(fileInputFields);
                if (markerFileSelect.value === '') {
                    hideFieldset(fileInputFields);
                }
            };

            handleSelectCustomMarker();

            const observerConfig = {attributes: true, childList: false, subtree: false};
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.attributeName === 'value') {
                        handleSelectCustomMarker();
                    }
                });
            });
            observer.observe(markerFileSelect, observerConfig);
        }

        if (mapSettingsForm.length) {
            handleCustomFileInput(
                '#custom_marker_media',
                [
                    $('#marker_icon_width'),
                    $('#marker_icon_height'),
                    $('#marker_icon_anchor_x'),
                    $('#marker_icon_anchor_y')
                ]);
            handleCustomFileInput(
                '#custom_marker_cluster_media',
                [
                    $('#marker_cluster_icon_width'),
                    $('#marker_cluster_icon_height')
                ]
            );
            handleCustomFileInput(
                '#marker_item_draft_media',
                [
                    $('#marker_item_draft_icon_width'),
                    $('#marker_item_draft_icon_height'),
                    $('#marker_item_draft_icon_anchor_x'),
                    $('#marker_item_draft_icon_anchor_y')
                    ]
            );
        }
    });
})(jQuery);
