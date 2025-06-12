# Cache settings

--

::: warning Technical!
Some technical expertise is needed to determine whether caching is working correctly in your installation. That said, we've tried to make using caching as simple as possible.
:::

You can find the cache settings for your site in the **"Advanced options"** tab.
The cache stores data of frequently requested queries and optimizes response times of your web server.

* The file-based cache is enabled by default. 
* Alternativly you can enable the [REDIS](http://redis.io) based cache. You need to provide the DSN for this.  You can ask your web host for support.
* We generally do not recommend disabling the cache, but if you wish to do so, you can select "Cache disabled" as the cache adapter.

## Troubleshooting

::: danger Experimental
A misconfigured cache can slow down your site!
:::

* If bookings do not appear immediately after booking, you can try clearing the cache using the "Clear cache" button.
  Reappearing bookings after clearing the cache
  can be an indicator that the cache is not
  working properly. In that case, try a different path for the cache or a different cache adapter.

* If your site is very slow, this may also indicate a problem with the cache.
  More about this: [The site is very slow](/en/documentation/faq/site-slow).

## Known problems

In the past, there have been issues with wordpress caching plugins such as 'REDIS Object Cache'.
For this reason, we advise against using such plugins.

## Discussion

On the technical side, CommonsBooking uses the Symfony-Cache interfaces.

A discussion about the performance of CommonsBooking can be found here: https://github.com/wielebenwir/commonsbooking/discussions/1465 .
We are aware of the performance issues with instances that manage a large number of items and bookings, and we are actively working to improve them.
