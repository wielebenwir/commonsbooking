# Problems and answers

### Calendar widget display in the admin area

If there are problems displaying the calendar in the booking admin area (the admin backend), see the image below on the right, one possible solution is to disable or remove and reinstall the ["Lightstart" (wp-maintenance-mode) plugin](https://wordpress.org/plugins/wp-maintenance-mode). 
The issue is an incompatibility between Lightstart and CommonsBooking and not a bug in CommonsBooking's code.
The problem does not occur after reinstalling Lightstart. More details on [GitHub in the CommonsBooking source repository](https://github.com/wielebenwir/commonsbooking/issues/1646).

![](/img/backend-booking-list-bug.png)

### Incompatible theme: GridBulletin

The latest version of [GridBulletin](https://wordpress.org/themes/gridbulletin) is incompatible with CommonsBooking.
Problems occur when the footer is enabled. One concrete issue is the missing booking calendar on the item page. From a technical perspective, the required JavaScript sources from CommonsBooking are not being loaded. The root cause within the GridBulletin theme or a solution has not yet been found.
