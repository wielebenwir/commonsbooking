# Configure cache

::: warning Technical!
Some technical expertise is needed to determine whether caching is working correctly in your installation. That said, we've tried to make using caching as simple as possible.
:::

You can find the cache settings for your site in the **"CommonsBooking->Options->Advanced options"** tab.
The cache stores data of frequently requested queries and optimizes response times of your web server.

* The file-based cache is enabled by default.
* Alternatively you can enable the [Redis](http://redis.io) based cache. You need to provide the DSN for this. You can ask your web host for support.
* We generally do not recommend disabling the cache, but if you wish to do so, you can select "Cache disabled" as the cache adapter.

When CommonsBooking fails to activate due to an issue with the cache, you can disable the cache by default
by adding the following code snippet. [Read more about how to use code snippets](./hooks-and-filters).
This is only recommended when other methods of disabling the cache are not working.

```php
add_filter('commonsbooking_disableCache', function() {
    return true;
} );
```


## Troubleshooting

::: danger Experimental
A misconfigured cache can slow down your site!
:::

* If bookings do not appear immediately after booking, you can try clearing the cache using the "Clear cache" button.
  Reappearing bookings after clearing the cache
  can be an indicator that the cache is not
  working properly. In that case, try a different path for the cache or a different cache adapter.

* If your site is very slow, this may also indicate a problem with the cache.
  More about this: [The site is very slow](../faq/site-slow).

* **Periodical cache warmup through cron job**:
  :::warning
  This setting was developed for some very specific edge cases and probably does not apply to you. It is also experimental and may lead to unintended consequences. For instance, we were not able to determine if the cache will be cleared on time after a booking. You should probably set the cronjob to run fairly frequently if you want to use this feature.
  :::
  If your site is rarely accessed but contains many items or bookings, it may be that the first access to the site is very slow.
  If this becomes a problem, you can have the cache warmed up regularly. You can do this by enabling the "Periodic warmup through cron job" option.
  After the option is enabled, you can configure how often the cache should be warmed up automatically. This can lead to higher server load if the cache is warmed up very frequently.
  In order for this to work, WP-Cron must be hooked into the system task scheduler. See here: [Hooking WP-Cron Into the System Task Scheduler](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/)

## Known problems

In the past, there have been issues with WordPress caching plugins such as 'Redis Object Cache'.
For this reason, we advise against using such plugins.

## Discussion

On the technical side, CommonsBooking uses the Symfony-Cache interfaces.

A discussion about the performance of CommonsBooking can be found here: https://github.com/wielebenwir/commonsbooking/discussions/1465.
We are aware of the performance issues with instances that manage a large number of items and bookings, and we are actively working to improve them.
