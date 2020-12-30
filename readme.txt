=== CommonsBooking ===

Contributors: wielebenwirteam, m0rb, flegfleg
Donate link: https://www.wielebenwir.de/verein/unterstutzen
Tags: booking, commons, sharing, calendar, 
Requires at least: 5.2
Tested up to: 5.6
Stable Tag: 2.2.16
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Commons Booking is a plugin for management and booking of common goods. 

== Description ==

This plugin gives associations, groups and individuals the ability to share items (e.g. cargo bikes, tools) with users. It is based on the idea of Commons and sharing resources for the benefit of the community. 

CommonsBooking was developed for the ["Commons Cargobike" movement](http://commons-cargobikes.org/), but it can be used for any kind items.

This is a new version of CommonsBooking, [Commons Booking V.09](https://de.wordpress.org/plugins/commons-booking/) will be retired some time in the future.

**Unique features:**

* Items can be assigned to different locations for the duration of a timeframe, each with their own contact information.  
* Simple booking process:  bookable timeframes can be configured with hourly slots oder daily slots.
* Auto-accept bookings: A registered user can book items without the need for administration. 
* Codes: The plugin automatically generates booking codes, which are used at the station to validate the booking. 
* Managers can set holidays or repair slots to prevent items from beeing booked.


**Use cases:**

* Your association owns special tools that are not in use every day, and you want to make them available to a local group.
* You own a cargo bike that you want to share with the community, and it will be placed at different locations throughout the year.

**Plugin websites**

* [Official Website](https://commonsbooking.org)
* [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues) 


== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'commonsbooking'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `commonsbooking.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `commonsbooking.zip`
2. Extract the `commonsbooking` directory to your computer
3. Upload the `commonsbooking` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==

= Where can i find help/report bugs? =

* [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues)
* [Support](https://commonsbooking.org/kontakt/)


== Screenshots ==

1. Booking calendar
2. Items list
3. Booking confirmation
4. User bookings list

== Changelog ==

= 2.2.16 () =
* Added customizable avilablity messages for location and item pages (can be set in options -> templates)
* added automatic reset to default values for some options if they are empty but needed for the plugin to work properly
* reworked save options process so that permalink page refresh is not longer needed after updating url slugs
* Optimized timframe validation so that not overlapping weekdays on overlapping timeframes doesn't result in an validation error
* Enhanced API route
* Removed default limitation of 2 months for maxium advance booking time. Now users can book as long as the timeframe is defined in advance. In a future release we will add the option to set the maximum advance booking time in admin options.

= 2.2.15 (25.12.2020) =
* optmizized migration process
* fixed issue when default options fields are missing after migration
* added: set show booking-codes default=on to all imported timeframes from cb1

= 2.2.14 =
* fixed: error when using individual table prefix other than wp_
* fixed: refresh permalink on save individual slug (no need to call permalinks settings page after saving slug)
* fixed: categories not shown in gutenberg editor
* added: You can set if booking codes should be shown to user or not on fullday booking slots in timeframe settings (timeframe editor)

= 2.2.13 =
* Added notice to refresh permalinks due to unsolved issue

= 2.2.11 =
* Fixed bug default options not set on update

= 2.2.10 =
* Fixed template issues (usernmame not shown, formatting issues in mail an booking template)

= 2.2.9 =
* Fixed template issup pickup instructions not shown on booking page

= 2.2.8 =
* Updated translation and minor text edits
* Set default values on activation and upates
* Fix: 404-page after installation because of missing permalink refresh

= 2.2.7 =

* add: Updated translation

= 2.2.6 =

* Enhanced import wizard for automatic migration from previous Commons Booking version (version < 1.0). Migration of time frames, articles, locations, bookings, booking codes, settings for blocked days. During migration, parallel operation of the old and new version is possible. No data from the previous installation is deleted or changed.
* Unconfirmed bookings are automatically deleted (after approx. 10 minutes)
* Several usability improvements and bug fixes
* Improvements of the CommonsBooking API

= 2.2.0 =

* inital stable release

