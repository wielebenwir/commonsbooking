A Leaflet plugin to display a temporary message on a map
([Demo](https://www.grendelman.net/leaflet/))

# Leaflet.Messagebox

Leaflet.Messagebox is a simple control to display a temporary message, like an
error, on a [Leaflet](http://leafletjs.com/) map. The message is hidden after
a configurable timeout.

## Using the Messagebox

There a are two ways to add the messagebox to the map. First:

    var options = { timeout: 5000 }
    var box = L.control.messagebox(options).addTo(map);

or, add it on map initialization:

    var map = L.map( 'mapdiv', {'messagebox': true, ...} );
    map.messagebox.options.timeout = 5000;

Then, show a message:

    box.show( 'This is the message' );

or, when implicitly used with the map:

    map.messagebox.show( 'This is the message' );

## Available Options:

There are only two options:

`position:` (string) The standard Leaflet.Control position parameter. Optional, defaults to 'topright'

`timeout:` (integer) The duration the messagebox is shown in milliseconds. Optional, defaults to 3000 (3 sec).

## Styling ##

The messagebox can be styled with CSS, see [the css file]( leaflet-messagebox.css) for details.

# License

Leaflet.Messagebox is free software. Please see [LICENSE](LICENSE) for details.
