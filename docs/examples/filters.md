# Hooks and Filters

WordPress and WordPress plugins can be customized via so-called [action hooks and filters](https://developer.wordpress.org/plugins/hooks/).
The customizations are defined in PHP code, so for example you need to place them as PHP code in the themes `functions.php` of your website. 

## Actions

These are the [action hooks](https://developer.wordpress.org/plugins/hooks/actions/) exposed by the plugin.

## Filters

These are the [filter hooks](https://developer.wordpress.org/plugins/hooks/filters/) exposed by the plugin.

### Filter `commonsbooking_mobile_calendar_month_count`

Lets you define the number of months that are displayed in the mobile layout of the calendar view using a filter.

Example usage:

```php
// Sets the mobile calendar view to display 2 month
add_filter('commonsbooking_mobile_calendar_month_count', fn(): int => 2);
```
