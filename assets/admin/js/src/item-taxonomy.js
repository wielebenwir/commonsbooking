(function ($) {
    'use strict';
    $(function () {
        const taxonomyBoxes =  $("#cmb2-metabox-_cb_taxonomy_metabox");
        if (taxonomyBoxes.length) {
            const mapMarkerUploadInput = $('#_cb_map_marker_id');
            const mapMarkerColor = $( '.cmb2-id--cb-map-marker-color' );

            const handleMapMarkerSelection = function() {
                if (mapMarkerUploadInput.val() !== "") {
                    mapMarkerColor.show();
                } else {
                    mapMarkerColor.hide();
                }
            }
            //TODO: Prevent polling. Find event handler that will be called when upload input value changes programmatically.
            setInterval(handleMapMarkerSelection, 500);
        }
    });
})(jQuery);
