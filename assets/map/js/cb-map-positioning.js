var cb_map_positioning = {
    defaults: {},
    marker: null,
    map: null,

    init_map: function (latitude, longitude, add_marker) {
        // set up the map
        map = new L.Map('cb_positioning_map');

        // possible fix to avoid missing tiles, found on: https://stackoverflow.com/questions/38832273/leafletjs-not-loading-all-tiles-until-moving-map
        // also see https://github.com/wielebenwir/commonsbooking/issues/1060
        map.on("load", function() { setTimeout(() => {
            map.invalidateSize();
        }, 500); });

        // create the tile layer with correct attribution
        var osmUrl = 'https://{s}.tile.osm.org/{z}/{x}/{y}.png';
        var osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
        var osm = new L.TileLayer(osmUrl, {minZoom: 10, maxZoom: 17, attribution: osmAttrib});

        map.setView(new L.LatLng(latitude, longitude), 10);
        map.addLayer(osm);

        if (add_marker) {
            this.add_marker(latitude, longitude);
        }

        map.setZoom(17);

        this.map = map;
    },

    add_marker: function (latitude, longitude) {
        var that = this;

        this.marker = L.marker([latitude, longitude], {
            draggable: true,
            autoPan: true
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

        const noAddressFound = function () {
            // show error message if address couldn't be found
            if(document.getElementById("_cb_location_street").value.length != 0)
            {
                jQuery( "#nogpsresult" ).show();
            }
            cb_map_positioning.init_map(
                cb_map_positioning.defaults.latitude || 50.937531,
                cb_map_positioning.defaults.longitude || 6.960279,
                false
            );
        }


        jQuery.getJSON(url, params, function (data) {

            if (data.length > 0) {
                jQuery( "#nogpsresult" ).hide();
                jQuery('#geo_latitude').val(data[0].lat);
                jQuery('#geo_longitude').val(data[0].lon);
                cb_map_positioning.set_marker_position( data[0].lat, data[0].lon );
  
            } else {
                noAddressFound();
            }

        })
        .fail(function() {
            noAddressFound();
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
    if (!cb_map_positioning.is_lat_lon($latitude.val(), $longitude.val())) {
        cb_map_positioning.search();
    } else {
        cb_map_positioning.init_map(parseFloat($latitude.val()), parseFloat($longitude.val()), true);
    }


    //event listeners on lat/lon $input - reposition marker
    $latitude.change(function () {
        if (cb_map_positioning.is_lat_lon($latitude.val(), $longitude.val())) {
            cb_map_positioning.set_marker_position(parseFloat($latitude.val()), parseFloat($longitude.val()));
        }
    });
    $longitude.change(function () {
        if (cb_map_positioning.is_lat_lon($latitude.val(), $longitude.val())) {
            cb_map_positioning.set_marker_position(parseFloat($latitude.val()), parseFloat($longitude.val()));
        }
    });

});