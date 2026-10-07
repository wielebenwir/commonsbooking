<script setup>
import Newsletter from '/.vitepress/components/Newsletter_EN.vue'
</script>

#  Functions and features

![](/img/banner-772x250-1.png)

CommonsBooking gives you the opportunity to offer items (e.g. cargo bikes, tools) for communal use.

CommonsBooking is a WordPress plugin and can therefore be easily integrated into existing websites.

<div>
  <a class="cbdoc-button cb-brand" href="./documentation/setup/install">Install</a>
  <a class="cbdoc-button cb-alt" href="./documentation/">Documentation</a>
</div>


* * *

##  Key features

###  Book flexibly

  * **New:** Multiple bookings per day (for example: booking by the hour)
  * Complete booking process (checkout) with booking codes
  * Configurable booking limits
  * Confirmation emails to clients and stations
  * Front-end map with filter options

![](/img/hourly-booking.png)

###  Easy administration

  * **New** : Designated managers can manage items assigned by admins
  * Automatic booking confirmation: Users can book items without the need for administration.
  * Calendar integration: Automatic import of all relevant bookings into your digital calendar via [iCal format](./documentation/manage-bookings/icalendar-feed.md).

![](/img/cb-managers.png)

* * *

##  Screenshots

![](/img/booking-calendar.png) The booking calendar with hourly booking  ![](/img/booking-confirm.png) The booking process  ![](/img/shortcode-cb-map-filtergroups.png) The map with displayed item availability  ![](/img/shortcode-cb-items.png) Well organized item list

* * *

##  Development

CommonsBooking is continuously under development. The following milestones are part of the planned further development:

  * Improved Metadata
  * Implementing the Commons API

<div>
  <a class="cbdoc-button cb-brand" href="./documentation/setup/install">Install</a>
  <a class="cbdoc-button cb-alt" href="./documentation/roadmap/">Roadmap of upcoming development milestone</a>
</div>

* * *

##  Application areas

The WordPress plugin was originally developed for the needs of the [„Free cargo bike movement"](https://freies-lastenrad.org/),
 but it can be used for the rental of any item.

  * You or your organization have tools that are not used on a daily basis and you want to make them available to local groups.
  * You own a cargo bike and want to share it with the community, it should be stationed at different locations throughout the year.

## Alternatives

We will list some FOSS (Free and Open Source) alternatives to CommonsBooking here to help you make a decision on which software to use. This list is incomplete and additions are welcome. We can also recommend [the list of German libraries of things](https://github.com/mojoaxel/awesome-leihladen) to see live instances. We are comparing the different software solutions from CommonsBooking's perspective, therefore we have listed all the features that CommonsBooking does not support. This list only contains software that is still actively developed. All of the solutions do not work with WordPress, but in turn also do not require WordPress.

| Name | Scope of application | Comparison to CommonsBooking | Since |
| ---- | ----------------- | -------------------------------- | -------------- |
|[cosum.de](https://cosum.de/) | Library of things | - Rental requests that have to be confirmed possible <br> - Does not require creation of timeframes, quick creation of many bookable items possible <br> - Complete package with integrated user management | 2019 
|[leihbase](https://leihbase.org/) | Library of things | - Reservation of items possible <br> - Can create statistics <br> - Can be deployed as a docker container | 2024 
|[llka](https://buergerstiftung-karlsruhe.de/ll/unsere-software)|Library of things | - Installation through a single command <br> - Self-Service Terminal Support <br> - Dashboards for team management | 2026
|[dingsda2mex](https://commons.machinaex.org/) | Inventory management for artists | - Optimized for checking items in and out of inventory using a smartphones or laptop <br> - Focus on streamlined inventory management <br> - Export options for billing or customs clearance forms <br>| 2021
|[Biletado](https://www.biletado.info/) | Booking software for municipalities | - Optimized for room reservations <br> - Can be integrated with billing systems <br> - Ticketing and event management | 2024
|[Koha](https://koha-community.org/) | Library software | - Cataloging books in standardized formats (MARC) <br> - Check-out and check-in procedures <br> - Label creation and inventory management <br>| 2001
|[Biblioteq](https://textbrowser.github.io/biblioteq/) | Library software | - Local installation (Windows / Mac / Linux) <br> - Primary function: Cataloging books | 2002


* * *

##  Subscribe to our newsletter

We will keep you posted. Subscribe to our newsletter.

<Newsletter />
