#  Timeframes: Configure a location's holidays

__

Timeframes can not only make items available at locations, but also determine
specific closing days of the locations. Since version 2.9, CommonsBooking
can automatically apply public holidays to one or more locations.

:::info
This feature is unfortunately only available for German public holidays.
Other public holidays need to be added manually.
:::

In order to configure holidays, you need to create a timeframe with the type
"Holidays or location closed". This timeframe can then be applied to one or
more locations. You can use "Manual selection" to manually select
specific locations, select them by category or apply the rule to all locations.

The same can be done for items. Please note that if you select "All" items,
that means that all items at the locations defined above are affected,
and not all items in the entire instance. To select all items across
the entire instance, you need to set both location and item to "All".

# Blocking an item regularly

To regularly remove an item from rental, the time frame type `Blocked (not Overbookable)` can be used.
If, for example, an item is to be blocked every day from 08:00 AM to 10:00 AM for a week, the start and end of the week must be entered
as the start and end dates and the repetition set to `Daily`. However, if the item is to be blocked continuously for an entire week,
the repetition must be set to `No repetition`.

:::tip
For maintenance periods, it is better to create a [booking restriction](/en/documentation/first-steps/manage-booking-restrictions), as this also allows you to notify the users whose bookings are in the affected time range.
:::

#  Automatically import holidays

To define individual, non-contiguous days for a timeframe, you need to set the
timeframe repetition to "Manual repetition". Then you can import the holidays
for your state and year using the the fields below. You can also manually add
further days using the date picker.

# Overbooking

A timeframe of the type "Holidays or location closed" is overbookable if
overbooking is configured in the location settings ( [more about this](/en/documentation/first-steps/create-location#overbooking) ).
This means that an item can be kept by the user over the period,
if they picked it up before and will return it after the holiday.
