# Create Bookings & Admin Booking

Bookings should generally only be created and canceled via the corresponding calendar of an item (i.e., in the frontend).

## Create a booking as an admin / Admin booking

Users with admin permissions can also create bookings via the backend (CommonsBooking admin interface). However, please note the following:

  * No plausibility check is performed on bookings created this way. Incorrect settings can lead to problems in calendar display.
  * The booking status must be manually set to "confirmed" via the status selection (box in the upper right).
  * Note: Bookings with the status "unconfirmed" are automatically deleted after approximately 10 minutes via a WordPress cron job. This also applies to admin bookings.
  * The booking can also be created for another user. To do this, select the login name in the "User" field. The booking will then also appear in the booking list of the user.
  * If the booking is set to "confirmed" and the entry is saved, a normal booking confirmation will be sent to the email address of the selected user.

