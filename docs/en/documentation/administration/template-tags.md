# Template tags and placeholders for email templates

The following page shows you how to enrich the WordPress templates and automatic email messages with data from WordPress and the plugin.

The following use cases are covered:
* Personal salutation of users in a confirmation email
* Key code for opening a bike lock in a confirmation email
* Compartment or shelf number of an item

## Template tags and meta fields

You can use template tags in email templates or frontend templates.
These so-called tags help you access data from the plugin, e.g., the name of an item or the date of a booking.
A template tag for accessing the name of an item looks like this, for example: <span v-pre>`{{item:post_title}}`</span>

::: tip Tip
For some data, we have already created corresponding functions that output, for example, a formatted booking date.
Below you will find a complete list of all tags in the plugin.
Basically, you can use template tags to access all `wp_post`, `wp_postmeta` as well as `user` and `user_meta` data.
:::

The general access to the values behind the template tags works according to the following scheme:

* The first part defines whether you want to output data for an item or a location. Items and locations are custom post types in WordPress and therefore contain all the typical WordPress base data such as title, status, etc.
* With the part after the `:` you define the field to be displayed. This can be either a field from the `wp_post` table or `wp_postmeta`. Our template function recognizes this automatically and inserts the corresponding value.
* **An example:** You have created another field in postmeta for the item type, for example to provide information about the condition of the item. So you create another custom field in the WordPress editor, for example with the name `condition`.
  You can access this field as follows:
  - in email template: via <span v-pre>`{{item:condition}}`</span>
  - in frontend templates (in the `/template` folder) via the following function:
    ```php
    <?php echo CB::get('item', 'condition'); ?>
    ```

* For users, this works on the same principle. If you have created additional user_meta fields (e.g., street, phone number) via a user profile plugin like WP Members, you can access these fields via <span v-pre>`{{user:fieldname}}`</span> or via
  ```php
  <?php echo CB::get('user', 'fieldname'); ?>
  ```
* Bookings do not work according to this scheme, as there are some special features. To output additional booking data, you need programming knowledge. If you are missing something here, please write to us. We will see what we can make possible.

## List of CB tags

The following template tags are included in the templates created by default during installation.

| Field                                                                                                                |                Template tag                |
|---------------------------------------------------------------------------------------------------------------------|:------------------------------------------:|
| **User**                                                                                                            |                                            |
| First name                                                                                                          |           <span v-pre>`{{user:first_name}}`</span>            |
| Last name                                                                                                           |            <span v-pre>`{{user:last_name}}`</span>            |
| Email                                                                                                               |           <span v-pre>`{{user:user_email}}`</span>            |
| **Item**                                                                                                            |                                            |
| Item name                                                                                                           |           <span v-pre>`{{item:post_title}}`</span>            |
| **Location**                                                                                                        |                                            |
| Location name                                                                                                       |         <span v-pre>`{{location:post_title}}`</span>          |
| Location address                                                                                                    |      <span v-pre>`{{location:formattedAddress}}`</span>       |
| Location contact details                                                                                            | <span v-pre>`{{location:formattedContactInfoOneLine}}`</span> |
| **Booking**                                                                                                         |                                            |
| Booking start                                                                                                       |        <span v-pre>`{{booking:pickupDatetime}}`</span>        |
| Booking end                                                                                                         |        <span v-pre>`{{booking:returnDatetime}}`</span>        |
| Summarized booking period (e.g., from January 24, 4:00 PM to January 26, 12:00 PM)                                   |     <span v-pre>`{{booking:formattedBookingDate}}`</span>     |
| Pickup instructions                                                                                                 |     <span v-pre>`{{location:pickupInstructions}}`</span>      |
| Link to booking/cancellation                                                                                        |         <span v-pre>`{{booking:bookingLink}}`</span>          |
| Booking codes (only for day-wise bookings)                                                                          |     <span v-pre>`{{booking:formattedBookingCode}}`</span>     |
| Booking comment                                                                                                     |        <span v-pre>`{{booking:returnComment}}`</span>         |
| **Restrictions**: The template tags for User, Item, Location, and Booking as well as the following are possible    |                                            |
| Restriction start date incl. time                                                                                   |  <span v-pre>`{{restriction:formattedStartDateTime}}`</span>  |
| Expected restriction end date incl. time                                                                            |   <span v-pre>`{{restriction:formattedEndDateTime}}`</span>   |
| Notice text that was entered in the restriction                                                                     |           <span v-pre>`{{restriction:hint}}`</span>           |

## Other meta fields

When using CommonsBooking in combination with other plugins, their plugin prefix for meta fields must be used so that they are correctly referenced. The following is a non-exhaustive list:

* **User (Plugin UsersWP):**
  For newly created fields in UsersWP, use the prefix `uwp_meta_`: <span v-pre>`{{user:uwp_meta_address}}`</span>

## Using custom meta fields for locations and items

You can create additional fields for locations or items.

  * To do this, go to Settings -> Select "Advanced" tab
  * In the Meta data field, create the desired fields according to the syntax specified there. You can find an explanation of the syntax in the field description.
  * e.g., `item;ItemKeyCode;Lock code;text;Code` for the combination lock
  * You can now use this meta field in the email templates using the shortcodes mentioned above.
  * Example: <span v-pre>`{{ [The code for the combination lock is:] item:ItemKeyCode}}`</span>
  * The text in the square brackets `[ ]` serves as descriptive text that is output before the actual meta field. The advantage here is that the descriptive text including the value is only output if the dynamic field contains a value. Simple HTML codes are also allowed in this descriptive text (e.g., br, strong, etc.)

The following tutorial video shows the process step by step:

<iframe width="100%" height="547" src="https://www.youtube.com/embed/f4rr77GpB9o" title="CommonsBooking Tutorial Metafelder" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
