# Migration from version 0.9.x

You can migrate from CB 0.9.x to CB 2.x.x with a single click. The migration imports the following data:

  * Items
  * Locations
  * Timeframes (including booking codes)
  * Existing bookings
  * The list of booking codes
  * Sender email and name
    * Note: This may overwrite the sender name and email already stored in the new CommonsBooking.
  * Closed days defined in locations (are carried over as non-bookable days in CB 2.x.x into the settings for bookable timeframes)
  * Existing registration fields for users (phone number, address)
  * Categories
  * From CB 2.2.14 you can set in the timeframe editor whether booking codes are displayed or not. During migration, this value is set to "on" for all imported bookable timeframes so that booking codes are shown as in CB 0.9.x.
  * For using the map, geo coordinates are required for each location. During migration you can select the option "Generate geo coordinates". During import, the geo coordinates are generated from address data for each location and saved to the location.

* * *

##  1\. Prepare the migration

  * Create a **backup of the current site** (we recommend the [ Updraft Plus ](https://de.wordpress.org/plugins/updraftplus) plugin)
  * Update your existing CommonsBooking to the latest version
  * Go to Settings -> CommonsBooking -> "Emails" and copy the template texts into a text editor on your computer (Notepad or similar). During migration, the templates cannot be carried over because the new CommonsBooking uses different [ template tags ](../administration/template-tags). After the migration, new default templates are activated in the new CommonsBooking. You can then adjust them manually to your needs. Do not simply copy the saved templates into the new CB, otherwise the placeholders (template tags) will no longer work.
  * On our [ template tags documentation page ](../administration/template-tags) you will find the names of the new template tags and can use them to adapt the templates accordingly.
  * [ Install CommonsBooking 2 ](./install) and activate the plugin. You can run version 2 in parallel with your existing CommonsBooking installation.
  * We recommend putting your site into maintenance mode during the migration so that no bookings are possible during the migration and testing period that might not be available in the new version. You can use the [ WP Maintenance Mode ](https://de.wordpress.org/plugins/wp-maintenance-mode) plugin for this. In maintenance mode, you can still access the site as administrators and test everything.

## 2\. Run the migration

Before the migration, create **a backup of your site** (we recommend the [ Updraft Plus ](https://de.wordpress.org/plugins/updraftplus) plugin).

  1. In Settings -> CommonsBooking, on the **Migration** tab, click **Start migration** and wait a moment until all data is migrated. The transfer of records happens in steps so that your server is not overloaded. With many records (e.g., many bookings and booking codes) the process can take several minutes. Please be patient.
During the import, the number of imported records updates. Wait until you see the message "Migration finished".

  2. CB 2.x.x has now imported your data and you can deactivate CB 0.9.x.
Note: If something did not work, you can simply activate CB 0.9.x later and you will effectively be back to the previous state.

  3. If problems occur during migration or it does not start, deactivate non-essential plugins. Issues were observed with the plugin "HiFi (Head Injection, Foot Injection)".

**Please note:**

  * **Categories:** If you created categories in CB 0.9.x, they are also migrated for items and locations. Categories in the new CommonsBooking only become active after you deactivate CB0. **Therefore, deactivate CB0 before checking the migration.**
  * **Re-importing:** You can repeat the migration as often as you want. Note that previously imported data is overwritten with the current values from CB1. Items, locations, or timeframes that you created directly in CB2 in the meantime remain unchanged.
  * **Deleted elements:** You can run the migration again, but note the following: If you deleted data (items, locations, timeframes) that had already been created in the new CommonsBooking from a previous migration run (moved to trash), you must empty the trash in the new CommonsBooking for all items / locations / timeframes before running the migration again. Otherwise, faulty data can occur during import.
  * **Items / locations / users that no longer exist:** If timeframes or bookings are imported and the associated items, locations, or users no longer existed in the old CommonsBooking, then these entries are left empty or marked as "null" or "undefined user".

## 4\. Transfer registration fields

Predefined registration fields (e.g., address, terms accepted) are **not active by default** in CommonsBooking 2.x.x. To re-enable them, do the following:

In Settings, click the **Migration** tab.

  * Under "CommonsBooking Version 0.X profile fields" click "Activate"
  * Also verify that the link to the terms is correct.

More information about registration fields can be found on the page [Customize registration and login](../administration/custom-registration-user-fields)

* * *

## 3\. Create an items page

In CommonsBooking 2 you no longer need to set a special "Items" page in the settings. You can insert your items list on an existing page at the desired location via a **shortcode**.

  1. Create a new page and insert the shortcode "[cb_items]".
    1. In WordPress with the classic editor, just enter the shortcode in the text: `[cb_items]`
    2. In WordPress (5+) with the Gutenberg block editor, click the plus icon to add a new Shortcode block and enter: `[cb_items]`
  2. Navigate to the page and create a test booking for an item.
  3. If everything looks good, replace the old items list in your navigation with the new page.
