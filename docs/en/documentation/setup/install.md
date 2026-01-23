# Install

__

Installing the current version of the new CommonsBooking (Version 2.x.x)

### Install CommonsBooking

  * Go to **WordPress** -> **Plugins** -> **Install** and search for ‘ **CommonsBooking** ’.
  * After clicking on ‘ **Install** “ and ” **Activate** ’ it is ready to use.

### Check WordPress settings for date and time

CommonsBooking works with the time and date settings set in
WordPress under ‘Settings -> General -> Time zone’.
The formatting for the time display (24h format etc.) and the date formatting settings set in the general WordPress settings are also valid for CommonsBooking.

Therefore, please check that you have configured the correct time zone in WordPress. If you have not yet done so, go to ‘Settings / General/ Timezone’.

### Configure CommonsBooking

In the WordPress settings you will now find a new list item ‘CommonsBooking’. Here you can do perform the initial configuration.  Please
enter at least the sender name and e-mail in the ‘Templates’ tab to get started. More information can be found under [Configuration](/en/documentation/settings/index)

### Create locations, articles and time frames

  * [Create **items**](/en/documentation/first-steps/create-item) under ‘CommonsBooking -> Items’
  * [**Create locations**](/en/documentation/first-steps/create-location) under ‘CommonsBooking -> Locations’
  * You can then specify when an item should be available for lending at a specific location under the menu item ‘[**Timeframe**](/en/documentation/first-steps/booking-timeframes-manage)’.

You can find detailed information on this under [first steps](/en/documentation/first-steps/).

### Show content on the website

  * Create a page on which your articles should appear: Include the text module (shortcode) `[cb_items]` in the page.
  * With the classic WordPress editor, simply insert `[cb_items]` including the brackets into the text field.
  * With the new editor, click on the black **\+ plus in the box**, select ‘Shortcode’ and insert `[cb_items]` including the brackets.
  * More \[shortcodes for maps, tables, etc](/en/documentation/administration/shortcodes) (Not translated yet).
  * The items are now available to book in the frontend.

### **Note** :

If the item or location list (the pages with the cb_items or
cb_locations shortcode) show an invalid page after clicking on ‘Book now’, you have to go to the WordPress settings on the ‘Permalinks’ page and click on "Save".

