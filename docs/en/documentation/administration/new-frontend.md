# New frontend (BETA)

The shortcode is called with `[cb_search id="4" ...]`.

`id` is the ID of the map that should be used as the basis for the settings.

The shortcode can be called multiple times on the page. All elements on the same page share a state. For example, filters and item lists can be displayed at different locations on the page – e.g., in a sidebar and the main content area – and still influence each other. Using multiple map IDs on the same page is currently not (yet) possible.

## Layouts

By default, the shortcode displays a filter with a map. Alternatively, one or more other layouts can be selected using the `layouts` parameter.
The following layouts are available:

  * **Filter**:
A filter to allow users to search and filter for items.

  * **List**:
A list view of the items.

  * **AvailabilityCalendar**:
The overview table with the availability of all items in the near future.

  * **MapWithAutoSidebar**:
A map with an item list that is automatically opened or closed as needed.

  * **LargeMapWithStaticSidebar**:
A large map with a left-aligned static sidebar containing the filter and item list.

The following command would display a filter together with an availability table:

`[cb_search id="4" layouts="Filter,AvailabilityCalendar"]`

## Display options

### `filter-expanded`

With this option, the filter is displayed without a collapse or dialog mechanism.
It comes in three variants:

  * `filter-expanded` (always active)
  * `filter-expanded-desktop` (only active at higher resolutions)
  * `filter-expanded-mobile` (only active at small resolutions)

Usage: `[cb_search id="4" layouts="Filter,List" filter-expanded]`

## Configuration (Advanced)

Finally, the shortcode can also directly define a configuration object that is passed to the [CB-Frontend library](https://github.com/wielebenwir/CB-Frontend). This step is more interesting for tech-savvy people for whom JSON is a familiar abbreviation. This way, complex logic for displaying markers on the map can be implemented, for example.

For this, a JSON object must be passed to the shortcode. This could look like this, for example:

```json
[cb_search id="4" layouts="Filter,List" filter-expanded]
{
  "map": {
    "markerIcon": {
      "renderers": [
        {
          "type": "category",
          "match": [
            { "categories": [6, 8], "renderers": [{ "type": "image", "url": "/assets/kasten-elektrisch.png" }] },
            { "categories": [6], "renderers": [{ "type": "image", "url": "/assets/elektrisch.png" }] },
            { "categories": [8], "renderers": [{ "type": "image", "url": "/assets/kasten.png" }] },
            { "categories": [12], "renderers": [{ "type": "image", "url": "/assets/3-raeder.png" }] },
            { "categories": [16], "renderers": [{ "type": "color", "color": "teal" }] }
          ]
        },
        { "type": "thumbnail" },
        { "type": "color", "color": "hsl(20 60% 80%)" }
      ]
    }
  }
}
[/cb_search]
```

With this configuration, the markers would first be assigned based on categories, for example, and either the thumbnail or ultimately a color would be used as a fallback option. Details of the configuration are explained in the [CB-Frontend library documentation](https://github.com/wielebenwir/CB-Frontend/blob/main/documentation/configuration.md).

