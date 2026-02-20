# The site is very slow

If your CommonsBooking site is very slow, there may be several reasons.
We use a technology called caching, which keeps frequently requested data
in temporary storage to save server resources.  
Caching may not work under certain conditions, for example when:

  * [WP_DEBUG](https://wordpress.org/documentation/article/debugging-in-wordpress) is enabled; in that case you need to edit your wp-config.php
  * The /tmp/ folder on your server is not writable. If that is the case, contact your web host and ask them to make the folder writable.
    * If that is not possible, you can set the path for the filesystem cache in the CommonsBooking settings under "Advanced Options". Ask your web host which folders on the server are available for temporary files.
    * If that is also not possible: Go to your Site Health screen at (http://YOUR-URL/wp-admin/site-health.php?tab=debug). There you will find the path to your WordPress directory under **Directories**. Alternatively, choose a folder in the format `YOUR_DIRECTORY/symfony` as the cache target. **Warning:** This can cause your WordPress directory to grow very large.

Alternatively, you can install [Redis](https://redis.io) on your server and let Redis manage the cache. Since Redis stores the cache in RAM instead of the filesystem, it is usually a bit faster.
