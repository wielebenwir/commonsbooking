function CB_Map() {
    var cb_map = {};

    cb_map.settings = null;
    cb_map.translation = null;
    cb_map.map = null;
    cb_map.markers = null;
    cb_map.messagebox = null;
    cb_map.location_data = [];

    cb_map.tile_servers = {
        1: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        2: 'https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png',
        3: 'https://tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png',
        4: 'https://tiles.lokaler.de/osmbright-20171212/{z}/{x}/{y}/tile@1x.jpeg'
    }

    cb_map.init_filters = function ($) {
        cb_map.filters = new CB_Map_Filters($, cb_map);
    }

    cb_map.init_map = function () {
        var tile_server_url = cb_map.tile_servers[this.settings.base_map];
        var attribution = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors - <a href="https://www.openstreetmap.org/copyright">License</a>';
        if (this.settings.show_location_distance_filter) {
            attribution += ' | Address search by <a href="https://nominatim.openstreetmap.org/">Nominatim</a>'
        }
        var map_options = {
            scrollWheelZoom: this.settings.scrollWheelZoom
        }
        var tileLayer_options = {
            minZoom: this.settings.zoom_min,
            maxZoom: this.settings.zoom_max,
            attribution: attribution
        }

        // set up the map
         var map = new L.Map('cb-map-' + this.settings.cb_map_id,map_options);

        //create messagebox
        this.messagebox = L.control.messagebox({timeout: 60000}).addTo(map);

        //create scale
        if (this.settings.show_scale) {
            L.control.scale({imperial: false, updateWhenIdle: true, position: 'topright'}).addTo(map);
        }

        // create the tile layer with correct attribution
        var osm = new L.TileLayer(tile_server_url, tileLayer_options);

        map.setView(new L.LatLng(this.settings.lat_start, this.settings.lon_start), this.settings.zoom_start);
        map.addLayer(osm);

        this.map = map;

        map.on('popupopen', function (e) {
            jQuery(e.popup._container).find('.cb-map-popup-item-availability').overscroll({
                direction: 'horizontal'
            });
        });

        //export button
        if (this.settings.enable_map_data_export) {
            L.easyButton('dashicons dashicons-download', function (btn, map) {
                CB_Map.export('geojson', cb_map, {});
            }).addTo(map);
        }

        //get location data
        this.get_location_data(true);
    },

        cb_map.get_location_data = function (init) {
            var that = this;
            var data = {
                'nonce': this.settings.nonce,
                'action': 'cb_map_locations',
                'cb_map_id': this.settings.cb_map_id
            };

            this.map.spin(true);

            jQuery.post(this.settings.data_url, data, function (response) {
                cb_map.location_data = response;

                that.render_locations(cb_map.location_data, init);

            }).always(function () {
                that.map.spin(false);
            });

        },

        cb_map.render_item_availability = function (availability) {
            var markup = '';

            availability.forEach(function (day) {
                var timestamp = Date.parse(day.date);
                var date = new Date(timestamp);
                var show_date = date.getDate();
                show_date = show_date <= 9 ? '0' + show_date : show_date;
                var show_month = date.getMonth() + 1;
                show_month = show_month <= 9 ? '0' + show_month : show_month;
                var date_string = show_date + '.<br>' + show_month + '.';
                markup += '<div class="cb-map-popup-item-availability-day ' + day.status + '">' + date_string + '</div>'
            });

            return markup;
        }

    cb_map.render_locations = function (data, init, center_position) {
        if (data.length == 0) {
            this.messagebox.show(cb_map.translation['NO_LOCATIONS_MESSAGE']);
        }

        var that = this;

        var markers;

        // As the documentation states, a valid cluster radius is:
        //   => 10px to enable  clustering
        //   == 0px  to disable clustering 
        //   1px-9px are ignored and 10px is assumed
        if(this.settings.max_cluster_radius == undefined || (0 < this.settings.max_cluster_radius < 10)) {
            this.settings.max_cluster_radius = 10;
        }

        if (this.settings.max_cluster_radius > 0) {
            var marker_cluster_options = {
                showCoverageOnHover: false,
                maxClusterRadius: this.settings.max_cluster_radius
            };

            if (this.settings.marker_cluster_icon) {
                marker_cluster_options.iconCreateFunction = function (cluster) {
                    var child_count = cluster.getChildCount();

                    var c = ' marker-cluster-';
                    if (child_count < 10) {
                        c += 'small';
                    } else if (child_count < 100) {
                        c += 'medium';
                    } else {
                        c += 'large';
                    }

                    return new L.DivIcon({
                        html: '<div class="cb-map-marker-cluster-icon" style="line-height: ' + that.settings.marker_cluster_icon.size.height + 'px; background-image: url(' + that.settings.marker_cluster_icon.url + ')"><span>' + child_count + '</span></div>',
                        className: 'marker-cluster',
                        iconSize: new L.Point(that.settings.marker_cluster_icon.size.width, that.settings.marker_cluster_icon.size.height)
                    });
                }
            }

            markers = L.markerClusterGroup(marker_cluster_options);
        } else {
            // No clustering
            // NOTE layergroup uses another api
            markers = L.layerGroup();
        }

        var custom_marker_icon;
        if (this.settings.custom_marker_icon) {
            custom_marker_icon = L.icon(this.settings.custom_marker_icon);
        }
        var item_draft_marker_icon;
        if (this.settings.item_draft_marker_icon) {
            item_draft_marker_icon = L.icon(this.settings.item_draft_marker_icon);
        }

        var date_format_options = {year: 'numeric', month: '2-digit', day: '2-digit'};

        //iterate data and add markers
        jQuery.each(data, function (index, location) {

            var marker_options = {};

            //item names
            var item_names = [];
            popup_items = '';
            var item_statuses = [];
            location.items.forEach(function (item) {
                item_names.push(item.name);

                var item_thumb_image = item.thumbnail ? '<img src="' + item.thumbnail + '" alt="' + item.name + '">' : '';

                popup_items += '<div class="cb-map-popup-item">'
                    + '<div class="cb-map-popup-item-thumbnail">'
                    + item_thumb_image
                    + '</div>';

                popup_items += '<div class="cb-map-popup-item-info">';
                popup_items += '<div class="cb-map-popup-item-link">'

                if (item.status == 'publish') {
                    popup_items += '<b><a href="' + item.link + '">' + item.name + '</a></b>';
                } else {
                    popup_items += '<b>' + item.name + '</b> - ' + cb_map.translation['COMING_SOON'];
                }

                //popup_items += '<span class="dashicons dashicons-calendar-alt"></span>';

                if (item.timeframe_hints && item.timeframe_hints.length > 0) {
                    popup_items += ' (';

                    for (var t = 0; t < item.timeframe_hints.length; t++) {
                        if (t > 0) {
                            popup_items += ', '
                        }

                        var timeframe_hint = item.timeframe_hints[t];

                        var date = new Date(timeframe_hint.timestamp * 1000);
                        var formatted_date_string = date.toLocaleDateString(cb_map.settings.locale, date_format_options);
                        popup_items += cb_map.translation[timeframe_hint.type.toUpperCase()] + ' ' + formatted_date_string;
                    }

                    popup_items += ') ';
                }

                popup_items += '</div>';

                if (cb_map.settings.show_item_availability && item.availability) {
                    popup_items += '<div class="cb-map-popup-item-availability">' + cb_map.render_item_availability(item.availability) + '</div>';
                }

                popup_items += '<div class="cb-map-popup-item-desc">' + item.short_desc + '</div>';
                popup_items += '</div></div>';

                item_statuses.push(item.status);
            });

            //icon
            var marker_icon;
            if (item_statuses.includes('publish') && cb_map.settings.preferred_status_marker_icon == 'publish') {
                if (custom_marker_icon) {
                    marker_icon = custom_marker_icon;
                }
            } else {
                if (item_draft_marker_icon) {
                    marker_icon = item_draft_marker_icon;
                }
            }

            if (marker_icon) {
                marker_options.icon = marker_icon;
            }

            var marker = L.marker([location.lat, location.lon], marker_options);

            marker.bindTooltip(item_names.toString(), {permanent: that.settings.marker_tooltip_permanent})

            //popup content
            var popup_content = '<div class="cb-map-location-info-name">';
            popup_content += '<b><a href="' + location.location_link + '">' + location.location_name + '</a></b>';
            //popup_content += '<span id="location-zoom-in-' + that.settings.cb_map_id + '-' + index + '" class="dashicons dashicons-search"></span>';
            popup_content += '</div>';
            popup_content += '<div  class="cb-map-location-info-address">' + location.address.street + ', ' + location.address.zip + ' ' + location.address.city + '</div>';

            if (that.settings.show_location_opening_hours && location.opening_hours) {
                // we hide opening hours because this field doesnt exist in cb2 - it was used in cb1
                // TODO: can be removed in future version of the map
                //popup_content += '<div class="cb-map-location-info-opening-hours"><b><i>' + cb_map.translation['OPENING_HOURS'] + ':</i></b><br>' + location.opening_hours + '</div>'
            }

            if (that.settings.show_location_contact && location.contact) {
                // we hide contact info because we only want to show it during the booking process
                // TODO: can be removed in future versions of the map
                //popup_content += '<div class="cb-map-location-info-contact"><b><i>' + cb_map.translation['CONTACT'] + ':</i></b><br>' + location.contact + '</div>'
            }

            popup_content += popup_items;

            var popup = L.DomUtil.create('div', 'cb-map-location-info');
            popup.innerHTML = popup_content;
            marker.bindPopup(popup);

            markers.addLayer(marker);

            //set map view to location and zoom in
            jQuery('#location-zoom-in-' + that.settings.cb_map_id + '-' + index, popup).on('click', function () {
                that.map.closePopup();
                that.map.setView(new L.LatLng(location.lat, location.lon), that.settings.zoom_max);
            });

        });

        //this.map.addLayer(markers);
        markers.addTo(this.map);

        that.markers = markers;

        //adjust map section to marker bounds based on settings
        if ((!init && this.settings.marker_map_bounds_filter) || (init && this.settings.marker_map_bounds_initial)) {
            if (Object.keys(data).length > 0) {

                // If max_cluster_radius == 0, markers is a layerGroup and doesn't define getBounds, 
                //  so the next if statement will fail, when center_position happens to be undefined
                // TODO why center_position can be undefined
                if (markers.getBounds === undefined && center_position === undefined) {
                    center_position = {
                        lat: data.map( location => location.lat ).reduce( (a, b) => a+b) / data.length,
                        lon: data.map( location => location.lon ).reduce( (a, b) => a+b) / data.length
                    }
                }

                //keep center position & set bounds based on markers to show around
                if (center_position) {
                    var max_delta_lat = 0;
                    var max_delta_lng = 0;

                    markers.eachLayer(function (marker) {
                        var lat_lng = marker.getLatLng()

                        var delta_lat = Math.abs(lat_lng.lat - center_position.lat);
                        var delta_lng = Math.abs(lat_lng.lng - center_position.lon);
                        if (delta_lat > max_delta_lat) {
                            max_delta_lat = delta_lat;
                        }
                        if (delta_lng > max_delta_lng) {
                            max_delta_lng = delta_lng;
                        }
                    });

                    var bounds = [
                        [center_position.lat + max_delta_lat, center_position.lon + max_delta_lng],
                        [center_position.lat - max_delta_lat, center_position.lon - max_delta_lng]
                    ];

                    that.map.fitBounds(bounds);
                } else {
                    that.map.fitBounds(markers.getBounds());
                }
            } else {
                if (center_position) {
                    this.map.setView(new L.LatLng(center_position.lat, center_position.lon), this.settings.zoom_start);
                } else {
                    this.map.setView(new L.LatLng(this.settings.lat_start, this.settings.lon_start), this.settings.zoom_start);
                }

            }
        }

    }

    return cb_map;
}
