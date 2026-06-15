# GBFS

This interface provides data of [locations](../first-steps/create-location),
[items](../first-steps/create-item) and their availability via
[timeframes](../first-steps/booking-timeframes-manage) in a standardized schema.
Currently, version 3.1-RC3 of the _General Bikeshare Feed Specification_ ([GBFS](https://www.gbfs.org/documentation/)) is supported with the following routes:

* station_status.json
* station_information.json
* system_information.json
* vehicle_status.json
* vehicle_availability.json
* vehicle_types.json
* gbfs.json (Discovery)

## Regarding vehicle_types.json

Since CommonsBooking is primarily used to rent out cargo bikes, the API returns `cargo_bicycle` as the default value for `form_factor`. As `propulsion_type` is a required field, the default value here is set to `human`. If this were not the case, much more additional information about the propulsion method would need to be provided.

::: info Fixed Issue
Since June 2026 (version 2.11) the [issue regarding a wrong display of availability](https://github.com/wielebenwir/commonsbooking/issues/1388) is fixed.
