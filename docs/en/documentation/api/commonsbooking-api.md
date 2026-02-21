# Using the CommonsBooking API

This article explains how you can use the CommonsBooking API.
But [what is the CommonsBooking API?](what-is-the-commonsapi)

CommonsBooking has its own API through which you can conveniently access plugin data via a web interface or make the data available to other platforms and services.
The API is based on the [REST API implemented in WordPress](https://developer.wordpress.org/rest-api).

::: tip Activating the API
The API is disabled by default and there are no connections to external services or platforms.
You activate the API via the settings (see below) and the checkbox "Enable API".
:::

## General settings

You can access the API via _Settings_ -> _CommonsBooking_ -> Tab: _API / Export_.

  * Enable API: Generally activates API access
  * API access without API key: If this option is enabled, the API can be accessed without an individual API key. This setting is useful if you want to share your data with multiple platforms.
  * API permissions: The interface can be enabled for different endpoints or requesting pages. To control access rights accordingly, you can also create multiple different permissions.

## Settings per API permission

  * Interface name: Any name for your internal designation of the API
  * Interface activated: The API permission is only activated when this checkbox is checked.
  * Push URL: Here you can enter the URL of the receiving system. CommonsBooking will call this URL whenever the data changes, so that the remote system is informed about a data change and can retrieve it via a separate API call. This enables real-time data exchange.
  * API key: Here you can enter a custom API key. If the key is set, the requesting system must provide the parameter apikey= _[key]_ in every query.

## API route specification

**Availability**

  * Description: Shows the availability of items at locations
  * Route: `/wp-json/commonsbooking/v1/availability`
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.availability.schema.json

**Items**

  * Description: Returns data for all published items including the associated availability and locations
  * Route: `/wp-json/commonsbooking/v1/items`
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.items.schema.json

**Locations**

  * Description: Returns a list of all locations including geo-coordinates
  * Route: `/wp-json/commonsbooking/v1/locations`
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.locations.schema.json

**Projects**

  * Description: Returns the basic data of the WordPress instance
  * Route: `/wp-json/commonsbooking/v1/projects`
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.projects.schema.json
