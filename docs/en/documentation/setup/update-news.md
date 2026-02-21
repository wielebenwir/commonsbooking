# Update information

Detailed release information can be found on the [CommonsBooking WordPress page](https://de.wordpress.org/plugins/commonsbooking/#developers).

## Notes on updating to version > 2.6

This update brings several new features and improvements. Here is an overview
of the most important changes. Feel free to use the docs to learn about the
new features.

**Important note:**
If you had bookable timeframes defined with the following settings:
_– Full day = enabled
– Start date set
– Repetition: No repetition
– No end date_
then these were previously shown in the calendar as full-day booking windows
without an end date.
After the update, with this configuration **only the start date is shown as
bookable**.
To make all days bookable again, select **Repetition = daily**.
This change is based on an updated logic. The previous display worked but was
not consistent.

**Template changes**

  * As part of the extensions, almost all template files were changed. If you
    modified files in the /templates directory, we recommend backing them up
    in advance and checking after the update which adjustments you want to
    integrate into the new templates.

**New features**

  * **Bookings as a separate menu item** with a clearer backend overview. Bookings are no longer listed under timeframes but in a new dedicated menu item "Bookings".
  * **Dashboard:** Overhauled dashboard now shows today's pickups and returns.
  * Reminder emails: Borrowers receive reminder and feedback emails before and after a booking.
  * **Manage usage restrictions:** Restrictions can now be managed. These can be notes about defects or missing parts or a total breakdown (e.g. due to repair). Bookings that fall within the affected timeframe are automatically canceled in case of a total breakdown, and an info email is sent to users and CB Managers. Notes are shown in the booking calendar and users can be notified of changes.
  * A **map view** can now be enabled for the location page. The setting can be activated on the location.
  * **Customizable booking confirmation text** on the booking page ("Your booking was confirmed"). Can now be adjusted in Settings -> Templates.
  * **Maximum advance booking period** is now configurable. By default, it is set to 365 days. This setting applies to all existing timeframes. It is configured on the timeframe. The timeframe can thus be created for a longer or unlimited period, but users can only book up to x days in advance from today.
  * **Thumbnail size** in item and location lists is now adjustable (Settings -> Templates -> Image formatting).
  * **GBFS API** integrated to enable standardized data exchange with other mobility platforms.
  * **Calendar legend:** The booking calendar now has a legend to display the colors and settings of the calendar.
  * **For experts:**
    Metadata sets (custom attributes / fields can be added to items or categories).
    API extended (selective API releases possible).

**Expanded and adjusted**

  * The map view no longer shows pickup notes and contact details in the small preview popup, since these should only be shown during the booking process. This option was also removed from the map settings.
  * In the export function, custom fields created by CommonsBooking are displayed so they can be added to the export.
  * The booking list has been revised. The design was updated and booking status integrated.
  * The export function has been expanded with additional standard fields (name of the borrower, etc.).
  * In the booking calendar, the time selection can now be reset.
  * Booking codes are now also displayed in the booking list (My Bookings).
  * Pickup notes are now displayed differently in the booking calendar and booking confirmation. Note: Template change. If you made manual template changes, please review and adjust them if necessary.
  * When cancellations occur, the cancellation time is stored and shown at the top of the booking detail view in the status message.

**Fixed bugs**

  * Locations in timeframe editing are now sorted alphabetically.
  * On misconfigured servers, there could be an error related to geocoding that prevented saving locations. Switching to a different software library should have fixed this.
  * Minor adjustments to ensure compatibility with WordPress 5.9 and PHP 8.

All changelog info also at: https://de.wordpress.org/plugins/commonsbooking/#developers
