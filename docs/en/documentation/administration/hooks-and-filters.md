#  Hooks and filters

__

##  Action Hooks

Using hooks (https://developer.wordpress.org/plugins/hooks/), you can insert your own
code snippets at specific points in the CommonsBooking templates. This allows you to
add your own code to the templates without having to replace the template files.

Code snippets are usually very short pieces of PHP code that can be included via a
[ Child Theme ](https://developer.wordpress.org/themes/advanced-topics/child-themes)
or through special code snippet plugins (e.g. Code Snippets). No advanced PHP knowledge is
required, it is however also possible to use these snippets to deeply interfere with the
functionality of CommonsBooking or even to make the booking system unusable. If you see examples
in the documentation, these are reasonably safe and tested. However, a certain residual risk remains.
If you encounter problems, please feel free to contact us. However, please also provide
all code snippets you are using. This will help us to better understand the problem.

Action hooks are patterned according to the principle

`commonsbooking_(before/after)_(template-file)`

Using _add_action_ you can integrate your own callback function. Example:

```php
function itemsingle_callback() {
    // what should appear before the item single template
}
add_action( 'commonsbooking_before_item-single', 'itemsingle_callback' );
```

###  Overview of all of the action hooks:

  * commonsbooking_before_booking-single
  * commonsbooking_after_booking-single
  * commonsbooking_before_location-calendar-header
  * commonsbooking_after_location-calendar-header
  * commonsbooking_before_item-calendar-header
  * commonsbooking_after_item-calendar-header
  * commonsbooking_before_location-single
  * commonsbooking_after_location-single
  * commonsbooking_before_timeframe-calendar
  * commonsbooking_after_timeframe-calendar
  * commonsbooking_before_item-single
  * commonsbooking_after_item-single
  * commonsbooking_mail_sent

### Hooks in the context of an object (since 2.10.8)

Some action hooks also additionally pass the post ID of the current object and an instance of the object
as a \CommonsBooking\Model\<object class> object. Those are:

  * `commonsbooking_before_booking-single` and `commonsbooking_after_booking-single`
    * Parameters: `int $booking_id`, `\CommonsBooking\Model\Booking $booking`
  * `commonsbooking_before_location-single` and `commonsbooking_after_location-single`
    * Parameters: `int $location_id`, `\CommonsBooking\Model\Location $location`
  * `commonsbooking_before_item-single` and `commonsbooking_after_item-single`
    * Parameters: `int $item_id`, `\CommonsBooking\Model\Item $item`
  * `commonsbooking_before_item-calendar-header` and `commonsbooking_after_item-calendar-header`
    * Parameters: `int $item_id`, `\CommonsBooking\Model\Item $item`
  * `commonsbooking_before_location-calendar-header` and `commonsbooking_after_location-calendar-header`
    * Parameters: `int $location_id`, `\CommonsBooking\Model\Location $location`

Example usage:
```php
function my_cb_before_booking_single( $booking_id, $booking ) {
    echo 'Booking ID: ' . $booking_id;
    echo 'The booking status is ' . $booking->getStatus();
}
add_action( 'commonsbooking_before_booking-single', 'my_cb_before_booking_single', 10, 2 );
```

##  Filter hooks

Filter hooks (https://developer.wordpress.org/plugins/hooks/filters) work
just like action hooks, but with the difference that the callback function
receives a value, modifies it, and then returns it.

###  Overview of all filter hooks:

  * [commonsbooking_isCurrentUserAdmin](/en/documentation/basics/permission-management#filterhook-isCurrentUserAdmin)
  * commonsbooking_isCurrentUserSubscriber
  * commonsbooking_get_template_part
  * commonsbooking_template_tag
  * commonsbooking_tag_$key_$property
  * commonsbooking_booking_filter
  * commonsbooking_mail_to
  * commonsbooking_mail_subject
  * commonsbooking_mail_body
  * commonsbooking_mail_attachment
  * commonsbooking_disableCache

There are also filter hooks that allow you to add additional user roles
akin to the CB Manager that can manage items and locations.
Read more: [Permission management](/en/documentation/basics/permission-management) (not translated yet)

In addition to that, there are filter hooks that allow you to change the default
values when creating timeframes. More about that [here](/en/documentation/advanced-functionality/change-timeframe-creation-defaults)

###  Filter Hook: commonsbooking_tag_$key_$property

::: tip
Since version 2.10.9 the object context is also passed to this filter hook.
The examples below only apply to versions >= 2.10.9.
:::

This filter hook allows you to modify the default behavior of template tags.
The value of $key and $property need to be replaced with the respective key and property of the template tag.
$key corresponds to the post_type of the object (e.g. `cb_location`, `cb_item`, ...), while $property corresponds to the property / meta field of the template tag to be overwritten (e.g. `_cb_location_email`, `phone`, ...).
You may also define your own template tags and use this filter hook to define their behavior.

####  Example: Overwrite who receives booking e-mails

This filter hook can be used in a staging environment to override
who receives booking confirmation e-mails.

```php
/**
 * This adds a filter to send all booking confirmations to one email address.
 */
add_filter('commonsbooking_tag_cb_location__cb_location_email', function($value) {
    return 'yourname@example.com';
});
```

#### Example: Define a custom function for an item's template tags

This hook will be called for the template tag <span v-pre>`{{item:yourFunction}}`</span>.
Possible use cases include, for example, lock codes that are generated by another function based on booking data.
In this example the item's ID is simply returned.

```php
add_filter('commonsbooking_tag_cb_item_yourFunction', function( $value, $obj) {
    // $obj is in this case an instance of the class \CommonsBooking\Model\Item, but it can also be another model or WP_Post
    return $obj->ID;
}, 10, 2);
```

### Filter `commonsbooking_mobile_calendar_month_count`

::: tip Since version 2.10.5
:::

How many months are displayed in the mobile calendar view can be adjusted using this filter.


```php
// Sets the mobile calendar view to display 2 month
add_filter('commonsbooking_mobile_calendar_month_count', fn(): int => 2);
```
