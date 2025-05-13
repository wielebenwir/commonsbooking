function CB_Map_Filters($, cb_map) {
  var that = this;

  this.current_address = '';
  this.position_marker = null;
  this.position = null;

  this.show_filters = {
    location_distance: false,
    item_availability: false,
    cb_item_categories: false
  };

  this.$form = null;

  this.init = function($) {
    var $filter_container = $('<div class="cb-map-filters cb-wrapper cb-filter"></div>');

    this.show_filters.location_distance = cb_map.settings.show_location_distance_filter;
    this.show_filters.item_availability =  cb_map.settings.show_item_availability_filter;
    this.show_filters.cb_item_categories = Object.keys(cb_map.settings.filter_cb_item_categories).length > 0

    if(this.show_filters.location_distance || this.show_filters.item_availability || this.show_filters.cb_item_categories) {
      var $form = $('<form></form');
      this.$form = $form;

      var $filter_options = $('<div class="cb-filter-options"></div>');

      if(this.show_filters.location_distance) {
        this.init_distance_filter($, $filter_options);
      }

      if(this.show_filters.item_availability) {
        this.init_availability_filter($, $filter_options);
      }

      if(this.show_filters.cb_item_categories) {
        this.init_category_filter($, $filter_options);
      }

      $form.append($filter_options);

      var $filter_button = $('<button type="button">' + cb_map.translation['FILTER'] + '</button>');

      $filter_button.click(function(event) {
        event.preventDefault();
        $filter_button.blur();

        if(that.show_filters.location_distance) {
          var $address = $form.find('input[name="position_address"]').first();

          that.do_geo_search($address.val(), function() {that.do_filtering($form) });
        }
        else {
          that.do_filtering($form);
        }

      });

      $button_wrapper = $('<div class="cb-map-button-wrapper"></div>');
      $button_wrapper.append($filter_button);
      $form.append($button_wrapper);

      $filter_container.append($form);
      $filter_container.insertAfter($('#cb-map-' + cb_map.settings.cb_map_id));
    }
  }

  this.do_geo_search = function(address, callback) {

    if(address.length > 0 && address != that.current_address) {
      that.current_address = address;

      if(that.position) {
        cb_map.map.removeLayer(that.position_marker);
        that.position_marker = null;
        that.position = null;
        that.current_address = '';
      }

      var post_data = {
        //'nonce': this.settings.nonce,
  			action: 'cb_map_geo_search',
        query: address,
        cb_map_id: cb_map.settings.cb_map_id
  		};

      //TODO: button block
      var $button = that.$form.find('button.geo-search')
      $button.prop("disabled", true);

      jQuery.post(cb_map.settings.data_url, post_data, function(response) {
        var data = JSON.parse(response);
        var $address = that.$form.find('input[name="position_address"]').first();

        if(data.length > 0) {
          var result = data[0];

          that.position = {
            lat: parseFloat(result.lat),
            lon: parseFloat(result.lon)
          }

          //create position marker
          var icon = new L.Icon({
            iconUrl: cb_map.settings.asset_path + 'images/marker-icon-2x-black.png',
            shadowUrl: cb_map.settings.asset_path + 'images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
          });

          that.position_marker = L.marker([that.position.lat, that.position.lon], {icon: icon});
          cb_map.map.addLayer(that.position_marker);

          cb_map.map.setView(new L.LatLng(that.position.lat, that.position.lon));
        }
        else {
          $address.addClass('highlight-border');
          setTimeout(function() {
            $address.removeClass('highlight-border');
          }, 2000);
        }

        typeof callback == 'function' && callback();

      }).fail(function(response) {
        if(response.status == 408) {
          alert(cb_map.translation['GEO_SEARCH_UNAVAILABLE']);
        }
        else {
          alert(cb_map.translation['GEO_SEARCH_ERROR']);
        }

      }).always(function() {
        $button.prop("disabled", false);
      });
    }
    else {
      typeof callback == 'function' && callback();
    }
  }

  this.do_filtering = function($form) {
    var filters = {
      cb_item_categories: []
    };
    var data = $form.serializeArray();
    data.forEach(function(obj) {
      if(obj.name.indexOf('cb_item_categories') > -1) {
        filters.cb_item_categories.push(obj.value);
      }
      else {
        filters[obj.name] = obj.value;
      }
    });

    //set default values
    if(filters.day_count > 0) {
      var date_start_min_timestamp = Date.parse(cb_map.settings.filter_availability.date_min);
      var date_end_max_timestamp = Date.parse(cb_map.settings.filter_availability.date_max);

      if(!filters.date_start) {
        filters.date_start = $('input[name="date_start"]').attr('min');
      }
      if(!filters.date_end) {
        filters.date_end = $('input[name="date_end"]').attr('max');
      }

      var date_start_timestamp = Date.parse(filters.date_start);
      var date_end_timestamp = Date.parse(filters.date_end);

      //ensure dates are inside allowed bounds
      if(date_start_timestamp < date_start_min_timestamp) {
        filters.date_start = cb_map.settings.filter_availability.date_min;
        $('input[name="date_start"]').val(cb_map.settings.filter_availability.date_min);
      }
      if(date_end_timestamp > date_end_max_timestamp) {
        filters.date_end = cb_map.settings.filter_availability.date_max;
        $('input[name="date_end"]').val(cb_map.settings.filter_availability.date_max);
      }

      //ensure date_end is not less then date_start, flip if needed
      if(date_end_timestamp < date_start_timestamp) {
        var date_tmp = filters.date_start;
        filters.date_start = filters.date_end;
        filters.date_end = date_tmp;

         $('input[name="date_start"]').val(filters.date_start);
         $('input[name="date_end"]').val(filters.date_end);
      }
    }

    var location_data = JSON.parse(JSON.stringify(cb_map.location_data)); //TODO: use a more efficient way of object cloning
    location_data = that.apply_filters(location_data, filters);

    if(cb_map.markers) {
      cb_map.markers.clearLayers();
    }

    cb_map.render_locations(location_data, false, that.position);
  }

  this.init_distance_filter = function($, $filter_options) {
    var $container = $('<div class="cb-map-distance-filter"><div class="cb-map-filter-group-label">' + cb_map.translation['DISTANCE'] + '</div></div>');
    var $wrapper = $('<div class="cb-map-filter-group"></div>');
    $container.append($wrapper);

    var $geo_search_input_group = $('<div class="cb-map-filter-input-group"></div>');

    var $undo_geo_search_button = $('<button type="button" class="undo-geo-search no-right-radius"><span class="dashicons dashicons-no"></span></button>');
    $geo_search_input_group.append($undo_geo_search_button);
    var $address = $('<input type="text" id="position_address" class="no-left-radius no-right-radius" name="position_address" placeholder="' + cb_map.translation['ADDRESS'] + '"></input>');
    $geo_search_input_group.append($address);
    var $geo_search_button = $('<button class="geo-search no-left-radius"><span class="dashicons dashicons-location-alt"></span></button>');
    $geo_search_input_group.append($geo_search_button);
    $wrapper.append($geo_search_input_group);

    var $cb_map_distance_group = $('<div class="cb-map-filter-distance-group"></div>');

    var $distance_input = $('<input name="max_distance" id="max_distance" class="cb-map-distance" type="number" min="0" value="2.5" step="0.25"></input>');
    $cb_map_distance_group.append($distance_input);
    $cb_map_distance_group.append('<label for="max_distance">km</label>');
    $wrapper.append($cb_map_distance_group);

    $undo_geo_search_button.click(function(event) {
      event.preventDefault();
      $undo_geo_search_button.blur();

      if(that.position) {
        cb_map.map.removeLayer(that.position_marker);
        that.position_marker = null;
        that.position = null;
      }

      $address.val('');
    });

    $geo_search_button.click(function(event) {
      event.preventDefault();
      $geo_search_button.blur();

      var address = $address.val();
      that.do_geo_search(address);

    });

    $filter_options.append($container);
  }

  this.init_availability_filter = function($, $filter_options) {

    var $container = $('<div class="cb-map-availability-filter"><div class="cb-map-filter-group-label">' + cb_map.translation['AVAILABILITY'] + '</div></div>');
    var $wrapper = $('<div class="cb-map-filter-group"></div>');
    $container.append($wrapper);

    var $date_start_input = $('<input type="date" id="date_start" name="date_start" min="' + cb_map.settings.filter_availability.date_min + '" max="' + cb_map.settings.filter_availability.date_max + '" value="' + cb_map.settings.filter_availability.date_min + '"></input>');
    var $date_end_input = $('<input type="date" id="date_end" name="date_end" min="' + cb_map.settings.filter_availability.date_min + '" max="' + cb_map.settings.filter_availability.date_max + '"  value="' + cb_map.settings.filter_availability.date_max + '"></input>');
    var $day_count_select = $('<select name="day_count"></select>')
    for(var d = 1; d <= cb_map.settings.filter_availability.day_count_max; d++) {
      var show_value = d == 0 ? '-' : d;
      $day_count_select.append('<option value="' + d + '">' + show_value + '</option>')
    }


    var $wrapper_from = $('<div class="date-col-from"></div>');
    $wrapper_from.append('<label>' + cb_map.translation['FROM'] + '</label>');
    $wrapper_from.append($date_start_input);

    var $wrapper_until = $('<div class="date-col-until"></div>');
    $wrapper_until.append('<label>' + cb_map.translation['UNTIL'] + '</label>');
    $wrapper_until.append($date_end_input);

    var $wrapper_atleast = $('<div class="date-col-atleast"></div>');
    $wrapper_atleast.append('<label>' + cb_map.translation['AT_LEAST'] + '</label>');
    $wrapper_atleast.append($day_count_select);
    $wrapper_atleast.append('<label>' + cb_map.translation['DAYS'] + '</label>');

   $wrapper.append( $wrapper_from);
   $wrapper.append( $wrapper_until);
   $wrapper.append( $wrapper_atleast);


    $filter_options.append($container);
  },

  this.init_category_filter = function($, $filter_options) {
    var $container = $('<div class="cb-map-category-filter"><div class="cb-map-filter-group-label">' + cb_map.translation['CATEGORIES'] + '</div></div>');
    var $wrapper = $('<div class="cb-map-filter-group"></div>');
    $container.append($wrapper);

    $.each(cb_map.settings.filter_cb_item_categories, function(group_index, group) {
      var $fieldset = $('<fieldset></fieldset>');
      if(group.name.length > 0) {
        $fieldset.append('<legend>' + group.name + '</legend>');
      }

      $.each(group.elements, function(element_index, category) {
        var $wrapper = $('<div class="cb-fieldgroup-row"></div>');
        var $input = $('<input type="checkbox" name="cb_item_categories[]" id="cb_item_categories' + category.cat_id  +'"  value="' + category.cat_id + '">')
        var $label = $('<label for="cb_item_categories' + category.cat_id + '"></label>');
        $label.html(category.markup);
        $wrapper.append($input);
        $wrapper.append($label);
        // $row.append($label);
        // $fieldset.append($input);
        // $fieldset.append($label);
        $fieldset.append($wrapper);

      });

      $wrapper.append($fieldset);
    });

    $filter_options.append($container);
  }

  this.apply_filters = function(location_data, filters) {
    //distance filters
    if(this.position) {
      location_data = this.apply_distance_filters(location_data, filters, this.position);
    }

    //availability filters
    if(filters.day_count > 0) {
      location_data = this.apply_item_availability_filters(location_data, filters);
    }

    //item category filters
    location_data = this.apply_item_category_filters(location_data, filters);

    return location_data;
  }

  this.apply_distance_filters = function(location_data, filters, position) {
    var filtered_locations = [];

    //filter the items
    location_data.forEach(function(location) {
      var distance = that.calc_distance(position, {lat: location.lat, lon: location.lon});
      if(distance <= filters.max_distance) {
        filtered_locations.push(location);
      }
    });

    return filtered_locations;
  }

  this.apply_item_category_filters = function(location_data, filters) {
    var user_categories = filters.cb_item_categories;
    var filtered_locations = [];

    //prepare category groups array
    var category_groups = [];
    var filter_cb_item_categories = cb_map.settings.filter_cb_item_categories;
    Object.keys(filter_cb_item_categories).forEach(function(groupId) {
      var group_elements = filter_cb_item_categories[groupId].elements;
      var group = [];

      group_elements.forEach(function(group_element) {
        group.push(group_element.cat_id);
      });

      category_groups.push(group);
    });

    //filter out category groups that are not present in user categories (because these have to be ignored)
    var filtered_category_groups = [];
    category_groups.forEach(function(category_group) {
      var filtered_group = [];

      category_group.forEach(function(category) {
        if(user_categories.includes(category.toString())) {
          filtered_group.push(category);
        }
      });

      if(filtered_group.length > 0) {
        filtered_category_groups.push(filtered_group);
      }

    });

    //filter the items
    location_data.forEach(function(location) {
      var items = location.items;
      location.items = [];
      items.forEach(function(item, item_index) {
        var is_valid = that.check_item_terms_against_categories(item.terms, filtered_category_groups);
        if(is_valid) {
          location.items.push(item);
        }
      });
    });

    location_data.forEach(function(location) {
      if(location.items.length > 0) {
        filtered_locations.push(location);
      }
    });

    return filtered_locations;

  }

  this.check_item_terms_against_categories = function(item_terms, category_groups) {
    var valid_groups_count = 0;

    category_groups.forEach(function(group) {
      for(var i = 0; i < item_terms.length; i++) {
        var term = item_terms[i];

        if(group.includes(term)) {
          valid_groups_count++;
          break;
        }
      };
    });

    return valid_groups_count == Object.keys(category_groups).length;
  }

  this.apply_item_availability_filters = function(location_data, filters) {
    var filtered_locations = [];

    location_data.forEach(function(location) {
      var items = location.items;
      location.items = [];
      items.forEach(function(item) {
        item.availability = that.reduce_availability(item.availability, filters.date_start, filters.date_end);
        var max_free_days_in_row = that.get_max_free_days_in_row(item.availability);

        if(max_free_days_in_row >= filters.day_count) {
          location.items.push(item);
        }
      });
    });

    //only show locations with items
    location_data.forEach(function(location) {
      if(location.items.length > 0) {
        filtered_locations.push(location);
      }
    });

    return filtered_locations;
  }

  this.reduce_availability = function(availability, date_start, date_end) {
    var inside = false;
    var updated_availability = [];

    for(var d = 0; d < availability.length; d++) {
      var day = availability[d];

      if(day.date === date_start) {
        inside = true;
      }

      if(inside) {
        updated_availability.push(day)
      }

      if(day.date === date_end) {
        inside = false;
      }
    }

    return updated_availability;
  }

  /**
  convert availability to sequences to ease up counting of free days in a row
  */
  this.calc_availability_sequences = function(availability) {
    var availability_sequences = [];

    availability.forEach(function(day) {
      if(availability_sequences.length == 0) {
        availability_sequences.push({
          status: day.status,
          count: 1
        });
      }
      else {
        if(availability_sequences[availability_sequences.length - 1].status == day.status) {
          availability_sequences[availability_sequences.length - 1].count++;
        }
        else {
          availability_sequences.push({
            status: day.status,
            count: 1
          });
        }
      }
    });

    return availability_sequences;
  }

  this.get_max_free_days_in_row = function(availability) {

    var availability_sequences = this.calc_availability_sequences(availability);

    var max_free_days_in_row = 0;
    var current_free_days_in_row = 0;

    availability_sequences.forEach(function(availability_sequence, seq_id) {
      if(availability_sequence.status == "available") {
        current_free_days_in_row += availability_sequence.count;
      }

      //closing days (value == "location-holiday") count only if days before & after are free (value == "available")
      if(availability_sequence.status == "location-holiday") {
        if(seq_id > 0 && availability_sequences[seq_id - 1].status == "available" && availability_sequences[seq_id + 1] && availability_sequences[seq_id + 1].status == "available") {
          current_free_days_in_row += availability_sequence.count;
        }
      }

      //a row of free days end with a sequence status of booked, partially-booked, no timeframe or end of $availability_sequences
      if(availability_sequence.status == "booked" || availability_sequence.status == "partially-booked" || availability_sequence.status == "no-timeframe" ||  seq_id == availability_sequences.length - 1) {
        if(max_free_days_in_row < current_free_days_in_row) {
          max_free_days_in_row = current_free_days_in_row
        }
        current_free_days_in_row = 0;
      }
    });

    return max_free_days_in_row;
  }

  /**
  * see: https://www.movable-type.co.uk/scripts/latlong.html
  **/
  this.calc_distance = function(position1, position2) {
    const R = 6371e3;
    var φ1 = this.deg_to_rad(position1.lat),
        φ2 = this.deg_to_rad(position2.lat),
        Δλ = this.deg_to_rad(position2.lon - position1.lon);

    var x = Δλ * Math.cos((φ1 + φ2) / 2);
    var y = (φ2 - φ1);
    var d = Math.sqrt(x*x + y*y) * R;

    return d / 1000; //in km
  }

  this.deg_to_rad = function(deg) {
    return deg * (Math.PI / 180);
  }

  this.init($, cb_map);
}
