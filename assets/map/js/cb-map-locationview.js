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

    },
}

jQuery(document).ready(function ($) {
    cb_map_locationview.init_map(parseFloat(cb_map_locationview.defaults.latitude), parseFloat(cb_map_locationview.defaults.longitude), true);
});
