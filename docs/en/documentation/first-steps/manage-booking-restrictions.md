#  Manage restrictions

__

**What are restrictions?**

Restrictions can be useful when the item you are lending is defective or completely out of order.
With booking restrictions, you can manage such cases without having to adjust the timeframe.
Your users will be notified about the defects or the total breakdown and any affected bookings in the timeframe can be automatically cancelled and new bookings prevented.

**How it works:**

  * Select "Restrictions" in the Commonsbooking menu.
  * Klick on "Add new restriction"

The following settings are now available:

  * **Type**
    * **Total breakdown:** In the case of a total breakdown, the item is no longer bookable. All bookings in this period are canceled, unless this behavior has been explicitly disabled in the [booking restriction settings](./documentation/settings/restrictions).
    * **Notice:** A notice is only displayed on the item page and users can be notified when desired.
  * **Location**
    * Select the location for the restriction.
    * When selecting "All", this restriction applies to all locations. (Currently disabled)
  * **Item**
    * Select the affected item.
    * When selecting "All", the restriction automatically applies to all items at the location set above. This is useful, for example, if the location needs to be temporarily and urgently closed due to illness or if the opening hours change. All bookings of all items linked to this location will then be canceled or users will be notified. (Currently disabled)
  * **Start date and end date**
    * Select the prospective start and end date of the restriction.
  * **Status**
    * **Not active:** The restriction is not yet active. This is useful if you want to create and save the restrictions first, but do not want to inform users yet.
    * **Active:** = The restriction is shown on the booking pages (in the booking calendar) and in the case of a total breakdown, the days between the start and end date are blocked.
    * **Problem solved:** If you select this status and then click "Update", the restriction will be lifted. Cancelled bookings will not be restored.

  * **Send Notification emails to users**
    * When you click this button, an email will be sent to all users informing them about the issue. Depending on the type (total breakdown or notice), a different message will be sent.
    * When the status of the restriction is set to "Problem solved" and you click the send button, an info email with a corresponding note will be sent.
      :::warning
        When a total breakdown restriction is set to "Problem solved", it **does not** notify the users that had their bookings cancelled about the resolution of the issue. The rationale behind this is that users likely have already sought alternative bookings by that time. If cancellations are disabled, users will notified about the resolution of the total breakdown.
        :::
    * The templates for these emails can be configured under [Settings -> Commonsbooking -> Restrictions](./documentation/settings/restrictions).
