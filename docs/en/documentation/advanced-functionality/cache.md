# Cache settings

--

::: warning Technical!
Technical expertise is necessary to understand if the Caching is functionally working.
Still we try to make the cache as easy to use as possible.
:::

You can find the cache settings for your site in the **"Advanced options"** tab.
The cache stores data of frequently requested queries and optimizes response times of your web server.

* The default setting enables the file-based cache.
* Alternativly you can enable the [REDIS](http://redis.io) based cache. You need the DSN. First ask your hosting provider if they support it.
* We generally do not recommend disabling the cache, but if you wish to do so, you can select "Cache disabled" as the cache adapter.

## Troubleshooting

::: danger Experimental
A wrongly configured cache can slow down your site!
:::

* If bookings do not appear immediately after booking, you can try clearing the cache using the "Clear cache" button.
  If the bookings then reappear, this indicates that the cache is not working correctly.
  In that case, try a different path for the cache or a different cache adapter.

* If your site is very slow, this may also indicate a problem with the cache.
  More about this: [The site is very slow](/en/documentation/faq/site-slow).

## Known problems

In der Vergangenheit gab es bereits Probleme mit anderen Wordpress-Plugins wie z.B. 'REDIS Object Cache'.
Aus diesem Grund raten wir von der Nutzung solcher Plugins ab.

In the past, there have been issues with wordpress caching plugins such as 'REDIS Object Cache'.
For this reason, we advise against using such plugins.

## Discussion

Technically CommonsBooking uses the Symfony-Cache interfaces.

A discussion about the performance of CommonsBooking can be found here: https://github.com/wielebenwir/commonsbooking/discussions/1465 .
We are aware of the performance issues with instances that manage a large number of items and bookings, and we are actively working to improve them.
