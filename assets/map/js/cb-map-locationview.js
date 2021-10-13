var cb_map_locationview = {
    defaults: {},
    marker: null,
    map: null,

    init_map: function (latitude, longitude, add_marker) {
        // set up the map
        map = new L.Map('cb_locationview_map');

        // create the tile layer with correct attribution
        var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        var osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
        var osm = new L.TileLayer(osmUrl, {minZoom: 10, maxZoom: 19, attribution: osmAttrib});

        map.setView(new L.LatLng(latitude, longitude), 18);
        map.addLayer(osm);

        if (add_marker) {
            this.add_marker(latitude, longitude);
        }

        this.map = map;
    },

    add_marker: function (latitude, longitude) {
        var that = this;

        this.marker = L.marker([latitude, longitude], {
            draggable: false,
            autoPan: false
        }).addTo(map);

        this.marker.on('dragend', function (e) {
            jQuery('#geo_latitude').val(that.marker.getLatLng().lat);
            jQuery('#geo_longitude').val(that.marker.getLatLng().lng);
        });

    },

    set_marker_position: function (lat, lng) {
        if (!this.marker) {
            this.add_marker(lat, lng);
        } else {
            var newLatLng = new L.LatLng(lat, lng);
            this.marker.setLatLng(newLatLng);
        }

        this.map.panTo(new L.LatLng(lat, lng));

    },

    search: function () {

        var url = 'https://nominatim.openstreetmap.org/search';
        var params = {
            street: jQuery('#_cb_location_street').val(),
            city: jQuery('#_cb_location_city').val(),
            postalcode: jQuery('#_cb_location_postcode').val(),
            format: 'json',
            limit: 1
        }

        jQuery.getJSON(url, params, function (data) {

            if (data.length > 0) {
                cb_map_locationview.init_map(data[0].lat, data[0].lon, true);
                jQuery('#geo_latitude').val(data[0].lat);
                jQuery('#geo_longitude').val(data[0].lon);

            } else {
                cb_map_locationview.init_map(
                    cb_map_locationview.defaults.latitude || 52.49333,
                    cb_map_locationview.defaults.longitude || 13.37933,
                    true
                );
            }

        });
    },

    is_lat_lon(latitude, longitude) {
        return isNaN(parseFloat(latitude)) || isNaN(parseFloat(longitude)) ? false : true;
    }
}

jQuery(document).ready(function ($) {

    var $latitude = jQuery('#geo_latitude');
    var $longitude = jQuery('#geo_longitude');

    //set initial marker: check if lat/lon is given, otherwise search nominatim
    if (!cb_map_locationview.is_lat_lon($latitude.val(), $longitude.val())) {
        cb_map_locationview.search();
    } else {
        cb_map_locationview.init_map(parseFloat($latitude.val()), parseFloat($longitude.val()), true);
    }

});
