CB_Map.export = function(format, cb_map, options) {

  function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:appication/json;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
  }

  if(format == 'geojson') {
    var export_data = {
      "type": "FeatureCollection",
      "features": []
    };

    cb_map.location_data.forEach(function(location) {
      var item_names = [];
      location.items.forEach(function(item, i) {
        item_names.push(item.name);
      });

      var feature = {
        "type": "Feature",
        "properties": {
          "_umap_options": {
            "color": "#7fc600",
            "iconClass": "Drop",
            "iconUrl": "/uploads/pictogram/bicycle-24_OfIM8RO.png",
            "showLabel": true
          },
          "name": item_names.toString()
        },
        "geometry": {
          "type": "Point",
          "coordinates": [
            location.lon,
            location.lat
          ]
        }
      };

      export_data.features.push(feature);

    });

    download('cb_map_export.geojson', JSON.stringify(export_data));

  }
}
