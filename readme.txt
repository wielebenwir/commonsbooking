=== CommonsBooking ===
Contributors: wielebenwirteam, m0rb, flegfleg, chriwen, hansmorb, datengraben
Donate link: https://www.wielebenwir.de/verein/unterstutzen  
Tags: booking, calendar, sharing, commoning, open-source
Requires at least: 5.9  
Tested up to: 6.6
Stable Tag: 2.9.4
Requires PHP: 7.4
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

CommonsBooking is a plugin for the management and booking of common goods.

## Description

This plugin gives associations, groups and individuals the ability to share items (e.g. cargo bikes, tools) with users. It is based on the idea of the commons and sharing resources for the benefit of the community.

CommonsBooking was developed for the ["Commons Cargobike" movement](http://commons-cargobikes.org/), but it can be used for any kind items.

**Unique features:**

* Items can be assigned to different locations for the duration of a timeframe, each with their own contact information. You can display all locations via shortcode as an interactive map.
* Simple booking process:  bookable timeframes can be configured with hourly slots oder daily slots.
* Auto-accept bookings: A registered user can book items without the need for administration. 
* Codes: The plugin automatically generates booking codes, which are used at the station to validate the booking. 
* Managers can set holidays or repair slots to prevent items from beeing booked.


**Use cases:**

* Your association owns special tools that are not in use every day, and you want to make them available to a local group.
* You own a cargo bike that you want to share with the community, and it will be placed at different locations throughout the year.

**Plugin websites**

* [Official Website](https://commonsbooking.org)
* [Official Documentation](https://commonsbooking.org/dokumentation)
* [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues) 


## Installation

### Using The WordPress Dashboard 

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'commonsbooking'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Uploading in WordPress Dashboard 

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `commonsbooking.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

### Using FTP

1. Download `commonsbooking.zip`
2. Extract the `commonsbooking` directory to your computer
3. Upload the `commonsbooking` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


## Frequently Asked Questions

### Where can i find help/report bugs?

* [Bug-Tracker](https://github.com/wielebenwir/commonsbooking/issues)
* [Support](https://commonsbooking.org/kontakt/)


## Screenshots

1. Booking calendar
2. Items list
3. Booking confirmation
4. User bookings list

## Changelog

### 2.9.4 (17.07.2024)
FIXED: Plugin not usable in multisite mode

### 2.9.3 (31.05.2024)
ADDED: Download ics file directly from booking details page
ENHANCED: Taxonomies will now be shown in item / location overview in the backend
ENHANCE: If iCalendar attachments are enabled: The cancellation email will now contain a calendar event that will cancel the booking in the user's calendar
FIXED: Restriction emails button not working in some instances
FIXED: Unexpected behavior when booking expires before confirmation
FIXED: German translation and typos

### 2.9.2 (26.04.2024)
ADDED: You can now configure reminder emails that are sent to the location before the start and before the end of a booking. (thanks @poilu)
ENHANCED: You can now disable sending a copy of the booking confirmation email to the location.
ENHANCED: New filter hooks for metaboxes
FIXED: Excerpt of item now shown in map popup
FIXED: Issue with special characters in booking email (Thanks @nelarsen)
FIXED: Admin Booking was not sending emails for CB_Manager
FIXED: API will even return response if the schema is not met and WP_DEBUG is enabled
FIXED: Incompatibility with "Futurio Extra" plugin

### 2.9.1 (17.03.2024)
FIXED: Timeframe export was not working
FIXED: GBFS Schema was inaccesible
FIXED: API Routes not working when WP_DEBUG is enabled
FIXED: cb_search map not working on some servers
ENHANCED: Minor string changes

### 2.9 (23.02.2024)
NEW: You can now schedule automated emails with booking codes to be sent to stations in custom intervals. (Thanks @printpagestopdf)
NEW: You can now apply custom rules to restrict bookings to a certain limit (e.g. max. 3 bookings per user per month).
NEW: An experimental new frontend shortcode as a drop-in replacement for the [cb_map] shortcode called [cb_search]. Read the documentation for more information. (Thanks @kmohrf)
NEW: You can now create holiday timeframes with manually defined dates and import holidays for German states.
NEW: You can now make items bookable for pre-defined dates (e.g. events) without just one timeframe.
ENHANCED: Added button to clear cache from the advanced options tab.
FIXED: Sender and subject of emails can now contain special characters. (Thanks @nelarsen)
FIXED: Fixed issues with booking code generation (Thanks @nelarsen)
FIXED: Commons API crashing when WP_DEBUG is enabled.
FIXED: Deprecation warnings for PHP 8.X
FIXED: Updated some packages

### 2.8.6 (02.12.2023)
FIXED: Holidays sometimes bookable when they should not be bookable

### 2.8.5 (03.11.2023)
ADDED: Support for WordPress Personal Data Exporter & Personal Data Eraser
ENHANCED: The amount of days that will be counted when creating a booking over closed days / holidays is now configurable.
FIXED: Sorting the table of bookings / locations / timeframes in backend
FIXED: Wrong error messages when creating a new timeframe
FIXED: Availabilities of items in GBFS API
FIXED: Rendering error in item table
FIXED: Holidays not working when they are more than 30 days in the future
FIXED: Map sometimes not loading

### 2.8.4 (20.09.2023)
FIXED: Incorrect time in booking confirmation
FIXED: Can now trash bookings again (thanks @danielappelt)
FIXED: URL prefix for GBFS root (thanks @futuretap)
FIXED: Shortcode Parameter causing fatal error
ENHANCED: Made items, locations & taxonomies searchable (thanks @flegfleg)

### 2.8.3 (25.08.2023)
ENHANCED: Booking codes are now available for timeframes without a configured end-date
ENHANCED: Added option to change directory of filesystem cache
ENHANCED: Fixed permission system to allow for creation of custom roles ( see  https://commonsbooking.org/docs/grundlagen/rechte-des-commonsbooking-manager/ )
ENHANCED: Added filter to allow selecting other roles to be assigned to items / locations
FIXED: Location map view not working since 2.8.1
FIXED: Booking offset should work again

### 2.8.2 (09.08.2023)
FIXED: fatal error om admin backend after upgrading to wordpress 6.3
FIXED: Wrong field value for iCal events (the field value is reset, you have to re-set your values after updating)
FIXED: Bug sending cancellation mail to admin on admin cancellation
FIXED: Availability text for items in item shortcode (Merged display of overlapping timeframes)
FIXED: Location without addresses not displayed correctly
FIXED: Incorrect error messages displayed to user during booking process
FIXED: Not all routes of GBFS API were initialized (thanks @futuretap)
FIXED: User data loading in backend caused timeout on large instances
ENHANCED: Improved booking validation
ENHANCED: Default value for days that are bookable in advance set to 31

### 2.8 (27.04.2023)
NEW: Added option to set a minimum offset for bookings. This allows to set a minimum time between booking and pickup.
NEW: Added ability to create bookings from the backend.
NEW: Can now add a signature that will be added to each email sent from the instance.
NEW: Additional user fields can be shown on booking details page.
NEW: iCalendar files can be attached to booking confirmation emails.
NEW: Experimental support of iCalendar feed to subscribe to all bookings that can be seen by the user.
ENHANCED: Added ability for item admins to also receive copy of restriction emails.
ENHANCED: Added ability to use REDIS as cache backend.
ENHANCED: Export of booking data now conforms to ISO 8601 standard. Thanks to @splines
ENHANCED: Can now filter items / locations by category in admin.
ENHANCED: ORDER and ORDER BY can be used in shortcodes to define the order of items / locations.
ENHANCED: Added filter hooks for timeframe defaults. Advanced users can now define their own default settings for timeframes through a filter hook.
ENHANCED: Added filter hooks for mail attachment. Advanced users can now define their own mail attachments through a filter hook. ( Frontend settings are not yet available )
ENHANCED: Added a key for the item availability table.
FIXED: Cronjobs were not re-scheduled when the execution time was changed. All cronjobs are re-scheduled on plugin update.
FIXED: Removed deprecated cronjobs.
FIXED: Dark and light text color options now work properly.
FIXED: No clustering with max_cluster_radius set to 0. You should now be able to disable map clustering.
FIXED: Missing translations.
FIXED: Vulnerability in dependency.
FIXED: Wrong month shown in calendar due to timezone issues.

### 2.7.3 (20.10.2022)
FIXED: Fatal error when trying to export timeframes with deleted items
FIXED: Fatal error when trying to access invalid data
FIXED: Restriction e-mails now contain correct booking links again
FIXED: Issue with map category presets

### 2.7.2 (30.06.2022)
FIXED: Plugin incompatibility with WPBakery
FIXED: Plugin incompatibility with Events Manager
FIXED: Plugin incompatibility with All-in One Events Calendar
FIXED: Shortcodes sometimes not showing all items
FIXED: Overbooking was possible when combining hourly and daily slots
ENHANCED: Optimized caching to avoid caching conflicts on multiple instances on same server
FIXED: Location map sometimes not properly rendered on location edit screen

### 2.7.1 (05.05.2022)
FIXED: Fixed Fatal error when PHP Version is < 7.4 / we recommend updating you PHP version to 7.4. because 7.3 is no longer maintained. Please ask you hosting provider for support.
FIXED: Migration did not work properly
ADDED: You can now add html text-snippets before and after an email template tag. This allows to add e.g. a label that is only shown when the template variable has content. Syntax: Add optional text in square brackets [xxx] directly before and after the template tag. Example: {{[optional text before ]item:post_title[optional text after]}} 
ENHANCED: Unified filter hooks. New hook prefix is commonsbooking_xxx . Please check your custom filters.

### 2.7 (26.04.2022)
NEW: You can now choose your individual colors to customize Commonsbooking to your liking. Try it via Options -> CommonsBooking -> Templates (scroll down to color section)
NEW: Added action hooks to templates
ENHANCED: Optimized the commonsbooking internal caching so booking lists and maps are rendered faster.
ENHANCED: Modified CSS styles for calendar.
ENHANCED: Item lists and availability tables will now output a warning when no items have been found.
ENHANCED: Added links to location pages in maps, booking lists, availability tables and item overview page.
ENHANCED: Items, which are restricted to a certain user group are now hidden for non-eligible users.
ENHANCED: Map: Pre-Filtering of items by item-categorys and location categorys is now possible
FIXED: Set default advance booking days for existing timeframes to 365 days.
FIXED: Some rendering issues with the calendar have been fixed.
FIXED: Issues with already past bookings where cancellation was still possible
FIXED: Wrong time displayed in cancellation messages

### 2.6.12 (27.02.2022)
FIXED: Fixes issue that prevents user meta data (.e.g phone number etc.) to be shown in booking emails 

### 2.6.11 (23.02.2022)
FIXED: Some users reported that bookings were no longer possible. After clicking on "continue to booking check" the expected booking page was not loaded. Since this only occurred on some systems and sporadically, it was not possible to determine the cause in the individual cases. However, our analysis showed that it was likely related to a Wordpress function for validating user input. We have adjusted this in the current version. 

### 2.6.10 (20.02.2022)
FIXED: With certain time frame settings it could happen that the calendar was only displayed starting with the next month. This is now fixed. 
FIXED: The map on the location page always showed a default location. It now shows the correct location.
FIXED: In some systems, bookings could not be executed because the booking confirmation page did not load.   

### 2.6.9 (18.02.2022)
FIXED: When an a href link was included in the site pickup instructions, it caused the booking calendar to not load correctly. 

### 2.6.8 (14.02.2022)
FIXED: fixed sanitzing issues
FIXED: reminder mails have been sent to users even if not activated in options
FIXED: error on location detail pages in some cases

### 2.6.7 (13.02.2022)
FIXED: fixed minor technical issue that leads to hidden gps refresh button in some environments

### 2.6.5 (13.02.2022)
FIXED: fixed issue of missing user data in booking and restriction related emails
MODIFIED: Internal refactoring of codebase

### 2.6.4 (10.02.2022)
FIXED: fixed issue that produces an error when sending restriction mails in some environments and cases 

### 2.6.3 (10.02.2022)
FIXED: fixed issue with classic editor and gps button on location editor

### 2.6.2 (10.02.2022)
FIXED: fixed minor technical issue

### 2.6.1 (10.02.2022)
FIXED: Map geo-coordinates are not updated after saving location without page reload with gutenberg editor. Added button to manually update / set geo coordinates and added some minor map improvements.

### 2.6 (03.02.2022)
Notice: Version 2.5 was only a release candidate is skipped as a stable release to to technial reasons

#### New
* Bookings as a separate menu item, better overview in the backend. The bookings are no longer listed under menu item "time frame" They moved to  a new menu item "Bookings". 
* Dashboard: Revision of the dashboard. Now shows today's pickups and returns.
* Reminder emails: Users will receive reminder and feedback emails before and after a booking.* Manage Usage Restrictions: Restrictions can now be managed. These can be notifications of broken or missing parts or the declaration of a total breakdown (e.g. due to a repair). Bookings that are within the affected time frame are automatically cancelled in case of a total breakdown and an info email is sent to users and CB managers. Notices are displayed in the booking calendar and users can be notified about changes.
* A map view can now be set for the location page. The setting can be activated via the location editor.
* Customizable booking confirmation text on booking page ("Your booking has been confirmed"). Can now be customized in Settings -> Templates.
* Maximum advance booking period is now customizable.The period is set to 365 days by default.  This setting also applies to all existing time frames.  The setting is done via the time frames. The time frame can thus be created for a longer or infinite period. Users can then always only book a maximum of x days in advance, calculated from today.
* Thumbnail size in article and location lists now adjustable (Settings -> Templates -> Image formatting).
* GBFS API integrated to enable standardized data exchange with other mobility platforms.
* Calendar Legend:The booking calendar has now received a legend to explain the colors and settings of the calendar.
* For experts: metadata sets (individual attributes / fields can be added to items or categories here).API extended (individual API releases possible).

#### Enhanced or changed
* The map view no longer shows pickup notes and contact details in the small preview popup, as we want to output these only in the booking process. Also, these options have been removed from the map settings.
* In the export function, the custom fields created by CommonsBooking are displayed to be able to add them to the export.
* The booking list has been revised. The design has been adjusted accordingly and the booking status has been integrated.
* Export function extended with more standard fields (name of the borrower etc.).
* In the booking calendar, the time selection in the calendar can now be reset.
* Booking codes are now also displayed in the booking list (My bookings).
* Pickup notes are now displayed differently in the booking calendar and in the booking confirmation. Attention: Template change. If you change the template manually, please check the adjustments and correct them if necessary.
* For cancellations, the cancellation time is saved and displayed in the booking details view at the top of the status message.

#### Fixed bugs
* Locations in time frame editing are now sorted alphabetically.
* On misconfigured servers, there could be an error related to geo-coding that prevented locations from being saved. Switching to a different software library should fix this bug.
* Minor adjustments to guarantee compatibility with Wordpress 5.9 and PHP 8.

### 2.4.5 (10.05.2021)
* NEW: Restrict bookings to user groups. It is now possible to restrict bookable timeframes to one or more user groups to restrict bookings based on these timeframes.
* FIXED: In case of consecutive time frames, it could happen that not all time frames were displayed in the calendar. This is now fixed. (#612)
* FIXED: In a some combination of time frames it could happen that an already existing booking could be overwritten (in case of slotwise booking). (#610)
* FIXED: Some parts in the calendar were not translated to English when the website language was set to English. (#545)
* FIXED: API was available by default - this is standard behaviour of the wordpress integrated API too. Now the CommonsBooking API is deactived by default an can be activated in CommonsBooking options. We also removed the Owner information from items that has been available via the API (first and last name)
* FIXED: In the email template tags, the tag following the pattern {{xxx:yyy}} could not be used within an a href link as it is not allowed by Wordpress security methods. We have now added the alternative divider #. This now also works in a href links. Example a href="{{xxxx#yyyy}}"
* FIXED: New booking codes could not be generated in some cases.


### 2.4.4 (26.04.2021) 
* NEW: Added category filter in items and locations shortcode. You can use [cb_items category_slug=category_slug] to show items by a single category.
* NEW: Added the p attribute to cb_items shortcode, so you can display a single item by using [cb_items p=POSTID]
* CHANGED: Item and location list in select dropdown in timeframe editor is not restricted to published elements anymore. 
* ENHANCED: template improvements: not available notice now in separate line in item/location lists
* ENHANCED: pickupinstructions now inclueded in the location section on the booking page (changed template: booking-single.php)
* ENHANCED: inlcuded pickupinstructions in the following templates: location-calendar-header.php / location-single-meta.php
* ENHANCED: Changed the standard image thumbnail size in listings
* FIXED: If multiple timeframes are set the calendar only showed the last timeframe in booking calendar. 
* FIXED: Fixed some issues with map category filter
* FIXED: fixed interaction issues with calender when using timeslots. pickup field resets when selecting pickup time (fixed issues #629 and #619)


### 2.4.3 (09.04.2021)
* NEW: Eport-Tool for exporting timeframes (Bookings etc.) with flexible data fields. Useful for external analytics or to create connections to external systems like automatic lockers etc.
* NEW: Booking comment: Users can add an internal comment to a booking that can be viewed by location administrators and can be used in email template via template tags (see template tags in documentation)
* NEW: Maximum bookable days are now without limitation. You can choose the maximum days in the timeframe editor.
* NEW: We added 2 new menu items in the CommonsBooking section so that you can now the edit Commonsbooking categories for locations and items (rename, remove etc.)
* NEW: Hide Contact Details: It is now possible to configure whether contact details of the station are only displayed after the booking has been confirmed by the user. This prevents users from already receiving booking details for an unconfirmed booking and thus possibly already contacting the location without having completed the booking.
* ENHANCED: Added migration of elementor special fields
* ENHANCED: Added map link to dashboard
* ENHANCED: Validation of bookings optimized
* FIXED: Bookable timeframe without enddate caused some issues in frontend calendar. Now it is possible to leave end date empty to allow infinite booking timeframe
* FIXED: performance issue on some systems in backend view (issue #546)
* FIXED: cancelation of an unconfirmed booking triggered a cancelation mail to user and location. Now the cancelation mail will not be send anymore.  (issue #532)
* FIXED: fixed a timeframe validation error (isse #548)
* FIXED: calendar not shown in edge / explorer in some versions. Thanks to @danielappelt for fixing it
* FIXED: Added tooltips in map configuration
* FIXED: Multiple categories are not imported during migration.
* TEMPLATES: modification in templates: booking-single-form.php and booking-single.php 
* ENHANCED: Make CommonsBooking Menu entry fit better in WP Admin für Wordpress 5.7 #593

### 2.4.2 (15.02.2021)
* FIXED: Fixed permission issue on booking lists

### 2.4.1 (14.02.2021)
* FIXED: Avoid Uncaught Exception during Geo Coding on Update

### 2.4.0 (12.02.2021)
* NEW: Booking list for frontend users now available (my bookings)
* NEW: Booking Widget now available (Widget display links to my bookings, login, logout) 
* MODIFIED: Permissions changed so that only administrators can assign CBManagers to locations and items. #478
* ENHANCED: Implementent message if backend users try to open preview of timeframes other than bookings
* ENHANCED: Interface and layout map filter optimized
* FIXED: generated duplicate booking codes if location was changed in existing timeframe. Now booking codes are deleted if location is not assigned to a timeframe #466
* FIXED: Export booking codes as CSV caused formatting issues when opening in Excel for some users due to incorrect character encoding. UTF-8 encoding added to avoid this error. #467
* FIXED: Small Commons API compatibility issues #281
* ENHANCED: Added internal Class for better admin message management
* FIXED: issue with filtered item list with role CB Manager (pagination based on inital filter)
* FIXED: minor issue: Headers already sent error on restore default options
* ADDED: function to remove deprecated user roles from former commonsbooking versions. affected users will get the role 'subscriber'
* FIXED: migration issues when using elementor are solved. all postmeta fields are imported

### 2.3.2 (18.01.2021
* FIXED: map error due to missing option value

### 2.3.1 (16.01.2021)
* FIXED: minor translation issue

### 2.3 (15.01.2021)
* NEW: Map Feature now included in CommonsBooking. Map Feature was originally based on the Map Plugin made by fLotte Berlin. Many many thanks to fLotte for their great work and support.
* NEW: added automatic reset to default values for some options if they are empty but needed for the plugin to work properly
* NEW: Added customizable avilablity messages for location and item pages (can be set in options -> templates)
* ENHANCED: reworked save options process so that permalink page refresh is not longer needed after updating url slugs
* ENHANCED: Optimized timframe validation so that not overlapping weekdays on overlapping timeframes doesn't result in an validation error
* ENHANCED: API route
* ENHANCED: Removed default limitation of 2 months for maxium advance booking time. Now users can book as long as the timeframe is defined in advance. In a future release we will add the option to set the maximum advance booking time in admin options.
* FIXED: booking caelndar not shown on some iphone models in portrait mode

### 2.2.15 (25.12.2020)
* optmizized migration process
* fixed issue when default options fields are missing after migration
* added: set show booking-codes default=on to all imported timeframes from cb1

### 2.2.14
* fixed: error when using individual table prefix other than wp_
* fixed: refresh permalink on save individual slug (no need to call permalinks settings page after saving slug)
* fixed: categories not shown in gutenberg editor
* added: You can set if booking codes should be shown to user or not on fullday booking slots in timeframe settings (timeframe editor)

### 2.2.13
* Added notice to refresh permalinks due to unsolved issue

### 2.2.11
* Fixed bug default options not set on update

### 2.2.10
* Fixed template issues (usernmame not shown, formatting issues in mail an booking template)

### 2.2.9
* Fixed template issup pickup instructions not shown on booking page

### 2.2.8
* Updated translation and minor text edits
* Set default values on activation and upates
* Fix: 404-page after installation because of missing permalink refresh

### 2.2.7
* add: Updated translation

### 2.2.6
* Enhanced import wizard for automatic migration from previous Commons Booking version (version < 1.0). Migration of time frames, articles, locations, bookings, booking codes, settings for blocked days. During migration, parallel operation of the old and new version is possible. No data from the previous installation is deleted or changed.
* Unconfirmed bookings are automatically deleted (after approx. 10 minutes)
* Several usability improvements and bug fixes
* Improvements of the CommonsBooking API

### 2.2.0
* inital stable release

