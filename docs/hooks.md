# Hooks

- [Actions](#actions)
- [Filters](#filters)

## Actions

### `commonsbooking_mail_sent`

*raises mail_sent action with error info*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$action` |  | 
`$result` |  | 

Source: [./src/Messages/BookingCodesMessage.php](src/Messages/BookingCodesMessage.php), [line 247](src/Messages/BookingCodesMessage.php#L247-L258)

### `commonsbooking_mail_sent`

*Fires after a mail is sent via the plugin.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$action` | `string` | the action of the message (see implementation).
`$result` | `bool\|\WP_Error` | true if successful, false otherwise

Source: [./src/Messages/Message.php](src/Messages/Message.php), [line 258](src/Messages/Message.php#L258-L264)

### `commonsbooking_unschedule`

*Unschedule hook, fires on deaction of Plugin.*


**Changelog**

Version | Description
------- | -----------
`2.8.0` | 

Source: [./src/Plugin.php](src/Plugin.php), [line 67](src/Plugin.php#L67-L72)

### `commonsbooking_before_booking-single`


Source: [./templates/booking-single.php](templates/booking-single.php), [line 28](templates/booking-single.php#L28-L28)

### `commonsbooking_after_booking-single`


Source: [./templates/booking-single.php](templates/booking-single.php), [line 215](templates/booking-single.php#L215-L215)

### `commonsbooking_before_timeframe-calendar`


Source: [./templates/timeframe-calendar.php](templates/timeframe-calendar.php), [line 14](templates/timeframe-calendar.php#L14-L14)

### `commonsbooking_after_timeframe-calendar`


Source: [./templates/timeframe-calendar.php](templates/timeframe-calendar.php), [line 162](templates/timeframe-calendar.php#L162-L162)

### `commonsbooking_before_location-calendar-header`


Source: [./templates/location-calendar-header.php](templates/location-calendar-header.php), [line 5](templates/location-calendar-header.php#L5-L5)

### `commonsbooking_after_location-calendar-header`


Source: [./templates/location-calendar-header.php](templates/location-calendar-header.php), [line 43](templates/location-calendar-header.php#L43-L43)

### `commonsbooking_before_item-single`


Source: [./templates/item-single.php](templates/item-single.php), [line 12](templates/item-single.php#L12-L12)

### `commonsbooking_after_item-single`


Source: [./templates/item-single.php](templates/item-single.php), [line 53](templates/item-single.php#L53-L53)

### `commonsbooking_before_location-single`


Source: [./templates/location-single.php](templates/location-single.php), [line 12](templates/location-single.php#L12-L12)

### `commonsbooking_after_location-single`


Source: [./templates/location-single.php](templates/location-single.php), [line 51](templates/location-single.php#L51-L51)

### `commonsbooking_before_item-calendar-header`


Source: [./templates/item-calendar-header.php](templates/item-calendar-header.php), [line 5](templates/item-calendar-header.php#L5-L5)

### `commonsbooking_after_item-calendar-header`


Source: [./templates/item-calendar-header.php](templates/item-calendar-header.php), [line 14](templates/item-calendar-header.php#L14-L14)

## Filters

### `commonsbooking_booking-rules`

*Default list of booking rules that get applied before booking confirmation.*

<code>
    // My foo bar
    $test = add_filter( 'commonsbooking_booking-rules', [] );
</code>

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$defaultRuleSet` | `\CommonsBooking\Service\BookingRule[]` | list of booking rule objects

**Changelog**

Version | Description
------- | -----------
`2.9` | bigger refactoring # TODO
`2.7.4` | 

Source: [./src/Service/BookingRule.php](src/Service/BookingRule.php), [line 331](src/Service/BookingRule.php#L331-L344)

### `commonsbooking_booking_filter`

*Default assoc array of row data and the booking object, which gets added to the booking list data result.*

See $rowData in this function, for the valid keys.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$rowData` | `array` | assoc array of one row booking data
`$booking` | `\CommonsBooking\Model\Booking` | booking model of one row booking data

**Changelog**

Version | Description
------- | -----------
`2.7.3` | 

Source: [./src/View/Booking.php](src/View/Booking.php), [line 239](src/View/Booking.php#L239-L249)

### `commonsbooking_emailcodes_rendertable`

*Default rendering of the booking code table in the specified target.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$renderedTable` | `string` | rendering of booking codes list as html string
`$bookingCodes` | `\CommonsBooking\View\CommonsBooking\Model\BookingCode[]` | list of booking codes
`$renderTarget` | `string` | where email is rendered (email\|timeframe_form)

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/View/BookingCodes.php](src/View/BookingCodes.php), [line 565](src/View/BookingCodes.php#L565-L579)

### `commonsbooking_privileged_roles`

*Default list of privilege roles*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$privilegedRolesDefaults` | `string[]` | list of roles as strings that are privileged roles

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/Model/Timeframe.php](src/Model/Timeframe.php), [line 203](src/Model/Timeframe.php#L203-L210)

### `commonsbooking_manager_roles`

*Default list of manager roles*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$managerRoles` | `string[]` | list of allowed manager roles that is returned by {@see \CommonsBooking\Repository\UserRepository::getManagerRoles()}

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/Repository/UserRepository.php](src/Repository/UserRepository.php), [line 27](src/Repository/UserRepository.php#L27-L34)

### `commonsbooking_admin_roles`

*Default list of admin roles*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$adminRoles` | `string[]` | list of allowed admin roles that are returned by {@see \CommonsBooking\Repository\UserRepository::getAdminRoles()}

**Changelog**

Version | Description
------- | -----------
`2.8.3` | 

Source: [./src/Repository/UserRepository.php](src/Repository/UserRepository.php), [line 44](src/Repository/UserRepository.php#L44-L51)

### `commonsbooking_tag_{$key}_{$property}`

*Default value for post type properties.*

The dynamic part of the hook $key is the name of the post type and the $property is the name of the meta
field.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$result` | `string\|null` | from property lookup

**Changelog**

Version | Description
------- | -----------
`2.7.1` | refactored filter name from cb_tag_* to its current form
`2.1.1` | 

Source: [./src/CB/CB.php](src/CB/CB.php), [line 56](src/CB/CB.php#L56-L67)

### `commonsbooking_before_send_location_reminder_mail`

*Default location booking reminder message*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$sendMessageToBeFiltered` | `\CommonsBooking\Messages\LocationBookingReminderMessage` | object to be sent.

**Changelog**

Version | Description
------- | -----------
`2.9.2` | 

Source: [./src/Messages/LocationBookingReminderMessage.php](src/Messages/LocationBookingReminderMessage.php), [line 82](src/Messages/LocationBookingReminderMessage.php#L82-L89)

### `commonsbooking_emailcodes_addical`

*Default value (from option settings) whether adding the ical attachment to booking codes email.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$cbSettingsOption` | `bool` | 
`$timeframe` | `\CommonsBooking\Model\Timeframe` | for which the booking codes are sent

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/Messages/BookingCodesMessage.php](src/Messages/BookingCodesMessage.php), [line 61](src/Messages/BookingCodesMessage.php#L61-L73)

### `commonsbooking_emailcodes_icalevent_title`

*Default title of booking codes ical event*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$unfilteredTitle` | `string` | default title
`$bookingCode` | `\CommonsBooking\Model\BookingCode` | object

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/Messages/BookingCodesMessage.php](src/Messages/BookingCodesMessage.php), [line 204](src/Messages/BookingCodesMessage.php#L204-L212)

### `commonsbooking_emailcodes_icalevent_desc`

*Default description of booking codes ical event*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$unfilteredDesc` | `string` | default description
`$bookingCode` | `\CommonsBooking\Model\BookingCode` | object

**Changelog**

Version | Description
------- | -----------
`2.9.0` | 

Source: [./src/Messages/BookingCodesMessage.php](src/Messages/BookingCodesMessage.php), [line 219](src/Messages/BookingCodesMessage.php#L219-L231)

### `commonsbooking_mail_to`

*E-Mail message recipient*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$recipient` | `string` | email address recipient
`$messageAction` | `string` | email action (see valid actions of the implementing message class)

**Changelog**

Version | Description
------- | -----------
`2.7.3` | refactored filter name from cb_mail_to
`2.1.1` | 

Source: [./src/Messages/Message.php](src/Messages/Message.php), [line 101](src/Messages/Message.php#L101-L110)

### `commonsbooking_mail_subject`

*E-Mail message subject*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$subject` | `string` | email subject
`$messageAction` | `string` | email action (see valid actions of the implementing message class)

**Changelog**

Version | Description
------- | -----------
`2.7.3` | refactored filter name from cb_mail_subject
`2.1.1` | 

Source: [./src/Messages/Message.php](src/Messages/Message.php), [line 125](src/Messages/Message.php#L125-L134)

### `commonsbooking_mail_body`

*E-Mail message body*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$body` | `string` | email body
`$messageAction` | `string` | email action (see valid actions of the implementing message class)

**Changelog**

Version | Description
------- | -----------
`2.7.3` | refactored filter name from cb_mail_body
`2.1.1` | 

Source: [./src/Messages/Message.php](src/Messages/Message.php), [line 145](src/Messages/Message.php#L145-L154)

### `commonsbooking_mail_attachment`

*E-Mail attachment*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attachment` | `array\|null` | 
`$messageAction` | `string` | email action (see valid actions of the implementing message class)

**Changelog**

Version | Description
------- | -----------
`2.7.3` | 

Source: [./src/Messages/Message.php](src/Messages/Message.php), [line 165](src/Messages/Message.php#L165-L173)

### `commonsbooking_widget_title`

*Default widget title*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$unfilteredTitle` | `string` | of the widget

**Changelog**

Version | Description
------- | -----------
`2.10.0` | uses commonsbooking prefix
`2.4.0` | 

Source: [./src/Wordpress/Widget/UserWidget.php](src/Wordpress/Widget/UserWidget.php), [line 42](src/Wordpress/Widget/UserWidget.php#L42-L50)

### `commonsbooking_custom_metadata`

*Default list of cmb2 meta boxes definitions.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$metaDataFields` | `array` | of arrays with [id, name, type, desc] keys.

**Changelog**

Version | Description
------- | -----------
`2.9.2` | 

Source: [./src/Wordpress/CustomPostType/CustomPostType.php](src/Wordpress/CustomPostType/CustomPostType.php), [line 123](src/Wordpress/CustomPostType/CustomPostType.php#L123-L130)

### `commonsbooking_get_template_part`

*Allow 3rd party plugin filter template file from their plugin*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$template` | `string` | template path
`$slug` | `string` | slug
`$name` | `string` | name
`$plugin_slug` | `string` | plugin slug

**Changelog**

Version | Description
------- | -----------
`2.2.4` | 

Source: [./includes/Template.php](includes/Template.php), [line 50](includes/Template.php#L50-L60)

### `commonsbooking_template_tag`

*Default template content*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$template` | `string` | content of template after tag replacement

**Changelog**

Version | Description
------- | -----------
`2.7.3` | with commonsbooking prefix
`2.1.1` | 

Source: [./includes/TemplateParser.php](includes/TemplateParser.php), [line 29](includes/TemplateParser.php#L29-L37)

### `commonsbooking_isCurrentUserAdmin`

*Default value if current user is admin.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$isAdmin` | `bool` | true or false, if current user is admin
`$user` | `null\|\WP_User` | current user

**Changelog**

Version | Description
------- | -----------
`2.10.0` | add $user param
`2.4.3` | 

Source: [./includes/Users.php](includes/Users.php), [line 178](includes/Users.php#L178-L187)

### `commonsbooking_isCurrentUserCBManager`

*Default value if current user is cb manager.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$isManager` | `bool` | true or false, if current user is cb manager
`$user` | `\WP_User` | current user

**Changelog**

Version | Description
------- | -----------
`2.5.0` | 

Source: [./includes/Users.php](includes/Users.php), [line 220](includes/Users.php#L220-L228)

### `commonsbooking_isCurrentUserSubscriber`

*Default value if current user is subscriber.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$isSubscriber` | `bool` | true or false, if current user is subscriber
`$user` | `\WP_User` | current user

**Changelog**

Version | Description
------- | -----------
`2.5.0` | 

Source: [./includes/Users.php](includes/Users.php), [line 236](includes/Users.php#L236-L244)


<p align="center"><a href="https://github.com/pronamic/wp-documentor"><img src="https://cdn.jsdelivr.net/gh/pronamic/wp-documentor@main/logos/pronamic-wp-documentor.svgo-min.svg" alt="Pronamic WordPress Documentor" width="32" height="32"></a><br><em>Generated by <a href="https://github.com/pronamic/wp-documentor">Pronamic WordPress Documentor</a> <code>1.2.0</code></em><p>

