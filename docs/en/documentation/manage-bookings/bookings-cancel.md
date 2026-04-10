# Cancel Bookings

Bookings should only be canceled via the frontend view (i.e., via the website, not via WordPress administration) in the current version.

Administrators have access to all bookings. If administrators or location managers (role cb_manager) want to cancel a booking for a user, please proceed as follows.

  * In WordPress administration (backend) under CommonsBooking, go to "Timeframes"
  * Then use the filters at the top to search for the booking, e.g., with the following filters:
    * Type = booking
    * Item = (select the item)
    * Start Date = enter the first booking day here
  * In the result list, click on the booking, then click on "Preview" on the right in the subsequent editor window
  * The booking will then open as it would appear to the user
  * Here you can now click "Cancel" to cancel the booking
  * A cancellation email will also be sent to the user
