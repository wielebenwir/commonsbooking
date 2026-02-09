# Templates
__

**Settings -> CommonsBooking -> Tab: Templates**


## E-Mail Templates

In the templates, you can define the content of the booking emails and the sender address of the booking emails. 

::: tip
If you want to restore the default text, simply empty the fields for all entries that you want to reset and save the changes, the default templates will then be loaded again.
:::

To integrate data from the booking (such as items, booking period, etc.) into the email, CommonsBooking uses so-called [Template Tags](../administration/template-tags). These are placeholders that are then replaced in the email with the corresponding data.

The default templates already include the most important template tags. You can use them anywhere in the templates. You may also use HTML tags in the templates and add additional template tags, if the ones included by default are not sufficient.

[ An overview about the use of template tags can be found here](../administration/template-tags)

## iCalendar Files

CommonsBooking is able to generate a .ics file from the bookings made, which is compatible with most digital calendars. Just like in the email templates, you may use template tags. The resulting calendar file is attached to the email and users can import it into their digital calendar. Most email programs support this import with one click. Currently, canceling a booking does not yet delete the generated calendar entry.

Additionally, you can also create a subscribable calendar. [Learn more about the iCalendar Feed here](./documentation/manage-bookings/icalendar-feed).

## Template and booking process messages

In this section you will find various text blocks that are output at different points of the booking process. The fields each contain a description of the use of the text block.

### User details on booking page

In this section you define which user data is displayed in the booking detail view. Adress data (street), phone numbers, ..., for instance can be added here. CommonsBooking does not manage user data itself. [Please add user fields using external plugins](../administration/custom-registration-user-fields). Please check how the field names are called in your user management plugin and add them accordingly. You can also use simple HTML formatting in the template, e.g. for line breaks (`<br>`).

Consider this example to display the field "phone" and the field "address" from the user data:
```
{{[Phone: ]user:phone}} <br>
{{[Address: ]user:address }}
```
::: warning
Please note that the field names (e.g. "phone" and "address") must be written exactly as they are stored by your user management plugin.
:::

In the square brackets is the label that should be displayed before the respective value.

## Image formatting

::: warning
This feature is currently not working. We are working on a solution.
:::

When using the shortcodes [cb_items] or [cb_locations] on a page, CommonsBooking generates corresponding list views with preview images of the items and locations. In this setting you can adjust the default size of these preview images.

## Color schemes

All colors in the CommonsBooking user interface are customizable. To reset colors to their default values, you can click the "Clear" button in the corresponding color field and then save your changes. The default value should now be set for the respective field.