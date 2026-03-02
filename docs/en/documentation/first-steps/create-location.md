#  Create location


## Location description
Use the large text editor at the top of the location page to describe
your location in detail (type of location, association with the project, ...).
This description will be shown on the location detail page.

## Location image

You can display an image of the location in the frontend. The featured image
can be set on the right side under the "Publish" button.

## Address

Fill out the address to this location using the form fields provided.

## Set / Update GPS and map

When saving the location, GPS coordinates are usually retrieved automatically
for the address provided. In case that didn't work, coordinates can also be
retrieved manually after saving the location by clicking the button below.
You can also enter the coordinates manually.

## Show location map on item view

If enabled, a map with the location of the item will be displayed on the item detail page,
alongside the booking calendar.

![](/img/item-locationmap.png){width="400"}
_The page of an item with the location map enabled, address and location image._

## General location information

In the location information, you can define the information that will be
provided to the user regarding pickup and contact to the location:

* **Location email**: Email addresses that should receive important emails about activities at the location (e.g. bookings, booking restrictions, booking codes). Multiple addresses can be entered, separated by commas.
* **Send copy of bookings / cancellations to location email**: Enabling this option will send a copy of the booking and cancellation emails to the email addresses specified above.
* **Pickup instructions** (opening hours, pickup process, etc.) will be displayed on the item page and throughout the booking process. The information provided here is public and visible even without a booking or registration.
* **Location contact information** (email and phone number) will only be displayed on the confirmation page after booking. If you want users to see some information only after booking, you can enter it here.
* **Location admin(s)**: Select one or more users to allow them to edit and manage this specific location. More about this: [Permission management](../basics/permission-management) (not translated yet)

## Overbooking settings {#overbooking}

::: info
In the following, we will use the term "Overbooking" to describe a booking period that spans over days where an item can neither be picked up or dropped off. This term is misleading and there is an [ongoing discussion on how to fix this issue.](https://github.com/wielebenwir/commonsbooking/issues/1858)
:::

When creating a timeframe (see [Timeframes](../first-steps/booking-timeframes-manage) ),
you can choose whether bookings are only possible on certain days of the week.
This way, you can configure items that are only available for booking on weekdays, for example.
This also means, that pickup and drop-off can only occur on these days. If you want
to allow users to book items over periods where no pickup or drop-off is possible,
for example over the weekend, you can allow the overbooking of blocked days.

**Use global location settings**

By default, the overbooking settings are defined in the CommonsBooking settings
under the General tab. If this checkbox is not checked, the location will use the settings defined below.

**Allow locked day overbooking**

Enables or disables the overbooking of blocked days.

**Count locked days when overbooking**

Under certain conditions, it can be useful to allow users to book
an item longer than usually possible when they are overbooking.
If, for instance, a user would like to book an an item at a location
that is closed on weekends, they might want to pick it up on Friday
and return it on Monday. When the maximum booking duration is set to 3 days,
they would not be able to make that booking unless the overbooked days are
not counted towards the maximum booking duration. Below, you can see some screenshots
further illustrating the overbooking settings and their effects on the calendar.
The timeframe in the examples has a maximum booking duration of 3 days.

![](/img/overbooking-nocount.png){data-zoomable}
_Overbooking enabled without counting any of the overbooked days_

![](/img/overbooking-countall.png){data-zoomable}
_Overbooking enabled while counting every overbooked day (the weekend is not bookable by the user,
because that would exceed the maximum booking duration of three days)_

![](/img/overbooking-countone.png){data-zoomable}
_Only the first day is counted towards the quota. The rest is ignored. The user can therefore book an item over the weekend but has
to return it on Monday._
