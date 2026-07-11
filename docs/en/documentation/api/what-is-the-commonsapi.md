# What is the CommonsAPI?

The CommonsAPI enables individual local CommonsBooking plugins to be connected to central platforms (such as nationwide directories of free cargo bikes or other overarching portals) via an interface. The [activation](./commonsbooking-api) and permissions can, of course, be configured individually.

[Technical details can be found here](./commonsbooking-api).

##  How the CommonsAPI and CommonsHub work

![](/img/logo-api-items.png) Initiatives lend out commons via CommonsBooking (or other software).

![](/img/logo-api-connects.png) The CommonsBooking plugin publishes (pushes) data in the CommonsAPI format. ![](/img/logo-api-commonshub.png) External portals, which we call CommonsHub, display the commons across platforms, e.g., on a map.

## Current status (July 2025)

The CommonsAPI is included in all CommonsBooking installations but is not automatically activated. 
A CommonsHub is technically feasible, and over the past year we have done the necessary groundwork with [@commonsbooking/frontend](https://www.npmjs.com/package/@commonsbooking/frontend) to abstract the data structure so that integrating the CommonsAPI should theoretically be possible. 
However, developing a CommonsHub is currently not a priority on our end, as we are occupied with maintaining the core plugin. 
But feel free to contact us if you would like to develop a CommonsHub, and we will support you with advice and assistance.
