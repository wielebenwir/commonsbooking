var cb_map_locationview = {
    defaults: {},
    marker: null,
    map: null,

    init_map: function (latitude, longitude, add_marker) {
        // set up the map
        map = new L.Map('cb_locationview_map');

        // TODO generalize this part into method/class construct (see cb-map-positioning.js)
        // possible fix to avoid missing tiles, found on: https://stackoverflow.com/questions/38832273/leafletjs-not-loading-all-tiles-until-moving-map
        // also see https://github.com/wielebenwir/commonsbooking/issues/1060
        map.on("load", function() { setTimeout(() => {
            map.invalidateSize();
        }, 500); });

        // create the tile layer with correct attribution
        var osmUrl = 'https://{s}.tile.osm.org/{z}/{x}/{y}.png';
        var osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
        var osm = new L.TileLayer(osmUrl, {minZoom: 10, maxZoom: 17, attribution: osmAttrib});

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

    },
}

jQuery(document).ready(function ($) {
    cb_map_locationview.init_map(parseFloat(cb_map_locationview.defaults.latitude), parseFloat(cb_map_locationview.defaults.longitude), true);
});
