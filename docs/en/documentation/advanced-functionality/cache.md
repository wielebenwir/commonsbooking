# Cache settings

--

You can find the cache settings for your site in the "Advanced options" tab. CommonsBooking uses caching to optimize frequently requested queries.
If bookings do not appear immediately after booking, you can try clearing the cache using the "Clear cache" button. If the bookings then reappear, this indicates that the cache is not working correctly. In that case, try a different path for the cache or a different cache adapter.
If your site is very slow, this may also indicate a problem with the cache.

More about this: [The site is very slow](/en/documentation/faq/site-slow).

We generally do not recommend disabling the cache, but if you wish to do so, you can select "Cache disabled" as the cache adapter.

# Caching Plugins
In the past, there have been issues with caching plugins such as 'REDIS Object Cache'. For this reason, we advise against using such plugins.
If your site's performance is still insufficient, you can try using a REDIS cache or consider upgrading to a more powerful server.

# Discussion
A discussion about the performance of CommonsBooking can be found here: https://github.com/wielebenwir/commonsbooking/discussions/1465 .
We are aware of the performance issues with instances that manage a large number of items and bookings, and we are actively working to improve them.
