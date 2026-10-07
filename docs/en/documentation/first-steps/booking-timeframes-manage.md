# Timeframes: Define when an item can be booked

An item becomes bookable through the connection of an item and a location using a timeframe.
The timeframe defines a time window (start and end date) and the booking conditions (e.g. hourly rental) under
which items can be booked. The timeframe type specifies whether we define a period in which the item is available (bookable) or not (holiday/repair).

A bookable timeframe can only be created for one item and one location at a time.
A timeframe that blocks the item (holiday / repair), on the other hand, can be linked to multiple items or locations.

A common approach to timeframe creation is to create a timeframe for when you want the item
to be bookable and another one for the holidays of the location operators.

:::info Getting started
On this page you will learn how to create a timeframe in the backend to make an item bookable.
If you want to know how to display the available bookings (using shortcodes) after you have published your timeframes, [click here](../administration/shortcodes).
:::

**Caution**: Timeframes cannot be directly accessed in the frontend using the backend link "View Post" but must be embedded using the shortcodes mentioned above.

## Creating a timeframe step by step

To create a bookable timeframe, go to "Timeframes" from the CommonsBooking menu
and click on "Add new timeframe". From there, fill out the form:

### **Add title**

* The title serves as an internal label for the timeframe and is displayed in the backend list view. It is not visible to users.
* When you create a bookable timeframe, you can give it a descriptive title (e.g. "Lending from xx-yy").

### **Comment**

* This field is primarily intended for internal use, such as documentation.
  If users are allowed to leave a booking comment for a booking, the comment will be stored in this field.
* This field can be left empty.

### **Type:**

* Select "Bookable" as the type. Other types can be selected for different use cases (e.g. location is on holiday, item is under repair). These types will prevent bookings or usage during the defined times. More information can be found in the [documentation on configuring timeframe types](../basics/timeframes-config). Alternatively, it is possible to enable password protection for an [item](../first-steps/create-item).

### **Location:**

* Select the location for which you want to create the bookable timeframe.

### **Item:**

* Select the item that should be available at the location.

### **Configure bookings:**
* **Maximum**: How many days can be booked in a row. (Note: If there are multiple timeframes for the same item and location, the value of the first valid timeframe will be used.)
* **Lead time:** How many days of lead time the location should have between booking and pickup. For example, if 2 days are set, then the item can only be booked for pickup two days from now. Leave empty to allow immediate bookings.
* **Calendar shows as bookable:** How many days in advance the item can be booked. For example, if 7 days are set, then users can only book the item over the next week.
* **Allowed for:** Which [user roles](https://wordpress.org/documentation/article/roles-and-capabilities/) may book the item. If left empty, all registered users can book the item.

### **Configure timeframe:**
* **Full Day:** When enabled, the timeframe applies to the entire day. If this option is disabled, the timeframe must have a start and end time.
* **Grid:** Irrelevant for full-day timeframes. If "Full slot" is selected, the item can only be booked from the start and end time of the timeframe. If "Hourly" is selected, each hour between the start and end time can be booked individually.
* **Start time / End time:** Irrelevant for full-day timeframes. Defines when the booking window starts and ends each day.

### **Timeframe repetition:**

_Select how the bookable timeframe should be repeated within the specified start and end dates._
* **No repetition**
    * This type of timeframe was originally intended to make items bookable for only one day. This can now be achieved using the "Manual repetition" option.
* **Manual repetition**
    * Allows you to select specific dates on which the item should be bookable.
* **Daily**
    * Will have the timeframe settings repeat every day from the start to the end date. If no end date is set, the item will be bookable every day indefinitely.
* **Weekly**
    * Enable this option if you want to select specific days of the week on which the item should be available for booking.
    * Example: The item should only be available for booking from Monday to Friday, as the location is open on these days. No bookings should be possible on weekends.
* **Monthly**
    * Will make the timeframe repeat every month on the same date from start to end date.
    * When a timeframe starts on 2025-02-15 and ends on 2025-05-15, the item will be bookable on the 15th of each month for 3 months consecutively.
    * Keep in mind that for longer time periods you should set the "Calendar shows as bookable" setting to a higher value.
* **Yearly**
    * Will make the timeframe repeat every year on the same date from start to end date.
    * When a timeframe starts on 2025-02-15 and ends on 2028-02-15, the item will be bookable on the 15th of February each year for 3 years.
    * Keep in mind that for longer time periods you should set the "Calendar shows as bookable" setting to a higher value.

### **Configure repetition**
* **Start date / End date:**
    * Defines the start and end date during which the timeframe is valid. Leave empty to have the timeframe be valid indefinitely.
* **Weekdays:**
    * Only available for weekly repetition. Select the days of the week on which the item should be bookable.
    * When configured, this can also define days on which only pickup and return are possible but items can still be booked. For example, a user could book an item for pickup on Friday and return it on Monday. This behaviour is defined in the [location settings](../first-steps/create-location).
* **Selected manual dates:**
    * Only available for manual repetition. Select the specific dates on which the item should be bookable. Enter the dates in the "YYYY-MM-DD" format. Multiple dates need to be comma-separated (e.g. "2023-01-01, 2023-01-02, 2023-01-03"). Clicking on the text field next to "Select dates" will open a calendar. Every day that is selected in the calendar will be appended to the list of dates.

## **Booking Codes**

After creating the timeframes, you can optionally generate booking codes that will be shown to the user on the booking confirmation page and that can be included in the confirmation email.
Booking codes can be used like passwords allowing the location to verify the owner of the booking. The user must provide the correct, daily rotating code to prove that they made the booking.

:::tip
Booking codes are generated in advance for each day and can be downloaded as a text file. This allows you to provide the codes to the location in advance for on-site verification.
:::
Codes can only be generated for timeframes that have the "Full Day" option enabled.

* **Create booking codes:** When this option is enabled and the timeframe is saved, booking codes will be generated.
* **Show booking codes:** When enabled, the code will be displayed to users during the booking process.

### **Send booking codes by email:**
:::warning
The timeframe must be saved before booking codes can be sent.
:::
This feature allows you to send the generated booking codes manually or automatically to the location via email.
The links allow you to quickly send the booking codes for the current or next month to the locations.

**Automatic sending:**
For automatic sending, a start date must be configured. Sending will start from this date and future codes will be sent on the same day of the month.
Defining the number of months determines for how many months in total the codes will be sent. For example, if 6 months are set, the codes for the next 6 months will be sent every half year.

**Download booking codes:**
Will download the previously generated booking codes as a text file so that they can be printed or sent out. For timeframes with an end date, this will include all codes from the start to the end date. For timeframes without an end date, it will include all codes from the start date up until one year in the future (from now).

**List of booking codes:**
This table shows the currently active booking codes for the timeframe. By default, not all codes are displayed in this view. The number of codes displayed can be configured under "Settings" -> "CommonsBooking" -> "Booking Codes".

**Who will receive the codes?**
The codes will be sent to the email addresses stored in the "Location Email" field of the respective location. Multiple email addresses can also be stored there.

### Examples

### **Bookable every day with absence (holidays)**
1. **Timeframe making the item bookable:**
    * Type="Bookable",
    * Full Day= X
    * Timeframe Repetition = ”Daily”
    * Start date: 01.01.2023
    * End date: None (meaning the timeframe is bookable indefinitely)
2. **A multi-day absence due to holidays:**
    * Type=”Holidays or location closed”,
    * Full Day= X
    * Timeframe Repetition=”Daily”
    * Start date: 15.07.2023
    * End date: 22.07.2023

### **Hourly or slot-based booking** (e.g. half-day, every three hours,...)
* Type: Bookable
* "Full Day": unchecked
* Grid: "Hourly" or "Full Slot"
  * When "hourly" is selected, the booking calendar will show time slots of one hour each from the start to the end time for pickup or return.
  * When "Full Slot" is selected, users can only book the entire time slot from start to end time (e.g. only from 09:00 to 12:00). This setting allows you to provide a coarser booking grid if you do not want to offer detailed 1-hour slots.

### **Combining multiple timeframes:**

When using hourly or slot-based timeframes, multiple timeframes of the same grid can be combined
to achieve more complex booking configurations. For example, you can configure the following to account for a lunch break (12:00 to 14:00) at your location:
* **Timeframe A (bookable):** 09:00 to 12:00
* **Timeframe B (bookable):** 14:00 to 18:00
