#  Export timeframes and bookings

__

You can find the data export under Settings -> Export. There, you can export timeframes and bookings in CSV format to archive them or analyze them using statistical programs.

# Export

First, you select which type of data you want to export (timeframes, bookings,...)

By default, basic fields of a timeframe are exported (see below).

Additionally, you can export any additional fields for locations, items, and users. Since we do not know if you are using any custom fields like phone numbers or others, the export does not include any additional fields by default. In order to include them, simply provide the identifier of the corresponding meta field.

# Analysis tools

* [cb-statistics by inSPEYERed](https://inspeyered.github.io/cb-statistics/)
* [R-Script to determine yearly utilization](https://gist.github.com/hansmorb/b4de840ed98f5b26d46ee51a1907b8b7)

# Contents of the export

The resulting CSV file is separated by semicolons.
By default, the following fields are included in an export:

  * **Timeframe or booking details**
    * ID of the timeframe / booking: `ID`
    * Author of the booking / timeframe: `post_author`
    * Creation date: `post_date`
    * Creation date (GMT): `post_date_gmt`
    * Post-Content (normally empty): `post_content`
    * Post-Excerpt (normally empty): `post_excerpt`
    * Title of the timeframe / booking: `post_title`
    * Status of the timeframe / booking: `post_status`
    * Uniquely identifiable name (slug): `post_name`
    * type (i.e. Booking, timeframe, restriction): `type`
    * * Note: this field is localized, i.e. in an English installation it says "booking", in a German one "Buchung"*
    * Repetition of the timeframe: `timeframe-repetition`
    * Hourly booking enabled or booking of the entire slot: `grid`
    * The amount of days that are bookable at max for the corresponding timeframe: `timeframe-max-days`
    * Whole day is bookable / was booked: `full-day`
    * Start of the timeframe / booking in ISO 8601 format: `repetition-start`
    * End of the timeframe / booking in ISO 8601 format: `repetition-end`
    * Start time of the timeframe / booking: `start-time`
    * End time of the booking / timeframe: `end-time`
    * Pickup time the way it is displayed to the user: `pickup`
    * Return time the way it is displayed to the user: `return`
    * Booking code: `booking-code`
    * Booking comment: `comment`
  * **Location details**
    * Name of the location: `location-post_title`
  * **Item details**
    * Name of the item: `item-post_title`
  * **User details**
    * First name: `user-firstname`
    * Last name: `user-lastname`
    * Login: `user-login`

