#  Configure booking rules (Since 2.9)


Booking rules are just one of the many ways to restrict the booking of items by users.
You can also use the [timeframe settings](../first-steps/booking-timeframes-manage) to 
configure user roles that may book an item or restrict booking by setting a password for the [item](../first-steps/create-item).

Using booking rules, you can restrict the use of items by users across the installation.
For instance, if you want to prevent a user from booking multiple items on the same day
or limit excessive use, you can achieve this using booking rules.

Most rules apply instance-wide. So, for example when you use the rule "Maximum booked days per week"
and apply it to all items, users can only book x days per week across the entire instance.
For this reason, it is also possible to apply the rule only to items of certain categories.
Using this feature, you could define that a specific category of items can only be booked for 2 days per week
and another category for 3 days per week.

## Difference to the "Maximum" setting in the timeframe
In the [Timeframe settings](../first-steps/booking-timeframes-manage) it is possible to set
the maximum amount of days an item can be booked in a row (single booking). So users could create multiple bookings which then exceed the maximum value.
Booking rules, on the other hand, define how many days a user can book in total per week, month, or within a specific time period.
They allow you to restrict the use of items by users and prevent them from creating excessive bookings.
Additionally, booking rules can be applied to multiple items. So if you have several items, users can only book them for as many days as defined in the booking rule.

## Setting up booking rules

The settings for booking rules can be found under "Settings" -> "CommonsBooking" ->
in the "Restrictions" tab. Scroll down all the way to the bottom. There you can add or delete rules.

##  Types of rules

###  Forbid simultaneous bookings

Prevents users from booking more than one item on the same day.
When a specific item category is set, this rule only applies to items that share the same category.

###  Prohibit chain-bookings

Prevents users from circumventing the maximum booking limit (default 3 days)
in a way that they create two consecutive bookings for the same item.
When this rule is activated, users must leave at least one day between bookings.

###  Maximum booked days per week

Defines how many days a user can book per week (either for all items or for items of specific categories).
Starting from the day set as the reset day, the new week will start.
So for example, if Monday is set as the reset day and only one day per week
can be booked, the user can book on both Sunday and Monday.

###  Maximum booked days per month

Defines how many days a user can book per month (either for all items or for items of specific categories).
Starting from the day set as the reset day, the new month will begin.
So for example, if the 15th is set as the reset day and only one day per month
is bookable, the user can book on both the 14th and the 15th.

###  Maximum of bookable days in time period

Defines how many days a user can book an item over a specific period of days.
Counting starts from the middle of the period so when, for instance, 30 days are set,
the 15 days before and after the given booking are considered as the period to look at.

##  Count canceled bookings towards quota

When this option is enabled, canceled bookings also count towards the maximum bookable days for booking rules. The following applies:

  * Booking canceled before the start of the booking period **do not** count towards the quota
  * For bookings canceled during the booking period the days from the start of the booking period until the cancellation are counted. For example, if a booking is made from Monday to Wednesday and canceled on Tuesday, it counts for 2 days and not for 3.

###  Exempt groups from all booking rules

Using a [small code snippet](../administration/hooks-and-filters) you can set
a role to be permanently exempt from all booking rules. This way, you do not have to manually add the role to each rule.

```php
add_filter('commonsbooking_privileged_roles', function($privileged_roles) {
    $privileged_roles[] = 'editor';
    return $privileged_roles;
});
```

The above snippet would add the role "Editor" with the slug `editor` as a "privileged" role.

In addition to that, all administrators and CB managers assigned to the affected item/location are always exempted.
[Learn more about permission management](./documentation/basics/permission-management)
