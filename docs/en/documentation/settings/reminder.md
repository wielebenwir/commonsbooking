#  Reminder via email concerning bookings


In addition to the emails that users receive when confirming or canceling a booking, CommonsBooking can also send reminder emails to users and locations before the start or after the end of a booking. These can be activated and deactivated individually. This page provides an overview of all email notifications sent by CommonsBooking.

## To users

CommonsBooking will send the following emails to users, the text of which can be customized here: "Settings → CommonsBooking → Templates" or "Settings -> CommonsBooking -> Reminder"

  * **Booking confirmation / Cancellation confirmation**
    * Enabled by default
    * Template under "Settings → CommonsBooking → Templates"
    * Can also be sent as a blind carbon copy to additional email addresses (see below)

  * **Restriction notification**
    * Sent when "Send" is clicked in restriction
    * Template under "Settings → CommonsBooking → Restrictions"
    * Is sent by default as a blind carbon copy to station emails (see below) 

  * **Booking reminder** .
    * Not enabled by default
    * Template under "Settings → CommonsBooking → Reminder"
    * Intended purpose is to ask users whether the booking should remain or possibly be canceled.
  * **Email after booking has ended** :
    * Not enabled by default
    * One day after the booking, users receive an email where they can be asked if they had any problems or if they would to to donate etc.

## To your team

### Administrators

**WordPress page administrators** (under WordPress Settings→General → Administrator Email Address) receive booking and cancellation emails as blind carbon copies (BCC)

### Locations

  * **Locations** (under Location → Location Email) receive **booking and cancellation emails** if the option "Send a copy of bookings/cancellations by email to the location" is enabled at the location
  * **Locations** (under Location → Location Email) receive **reminders about bookings** if the option under "Settings → "Reminders → Reminder for locations before booking start" or "Reminder for locations before booking end" is set AND in the corresponding location the option "Reminder email for booking start" or "Reminder email for booking end" is enabled.

::: warning **Important**
It is not enough to enter people as [location managers](./documentation/basics/permission-management) for them to be notified. In order to receive emails, the recipients address must be entered in the field "Location → Location Email".
:::

### Item maintainers

* A maintainer of an item can be notified about booking restrictions by configuring the "Item → Item maintainer email" field. This can be used in a workflow where when damage to an item is reported by the location, the location creates a restriction which will automatically block the item from being booked and notify the items maintainer about the issue.