# iCalendar Feed

## Description

In the settings under "Advanced Options" you can activate the iCalendar feed. iCalendar is a very common format for digital calendars.
Most digital calendars allow you to add a URL from which calendar entries are automatically imported from the booking system into your digital calendar.
These calendar entries are read-only, i.e. changes to the digital calendar cannot affect the booking system.

Once this feature is activated, you will find the individual calendar URL in the menu in the "My Bookings" overview.

![](/img/iCalendar-feed.png)

**ATTENTION** : This digital calendar not only lists your own bookings but all bookings you have access to.
This has special implications for administrators and CommonsBooking managers who have access rights to locations or items. For more information: [Assign access rights](../basics/permission-management)

**ATTENTION:** Past appointments currently still disappear from the calendar.
This may change under certain circumstances.

## Vorlagen

Der iCalendar Feed unterscheidet zwischen zwei Arten von Terminen: 
Termine des Nutzers selbst und Termine von anderen Nutzern. Wenn Nutzende
den Feed abonniert haben, dann werden sie hauptsächlich ihre eigenen Termine sehen,
Stationen werden, wenn sie die entsprechenden Berechtigungen haben, auch Termine von anderen Nutzenden sehen. Für die Nutzendensicht sind andere Dinge wichtig als für die Station. Z.B. wollen Nutzende eher wissen, wo ihre Station ist und wie sie den Artikel abholen können. Stationen dagegen wollen eher wissen wie die Nutzenden heißen, die den Artikel abholen und wie diese kontaktiert werden können. Aus diesem Grund existieren zwei Vorlagen für die Darstellung der Termine:

- Eigene Termine: "Einstellungen"->"CommonsBooking"->"Vorlagen"->"iCalendar Feed"
- Fremdtermine: "Einstellungen"->"CommonsBooking"->"Erweiterte Optionen"->"iCalendar Feed"

###  Use case scenario: Location

This scenario is intended to briefly illustrate how this feature can be used meaningfully.
Let's assume that as a location for bicycle rentals we want to automatically see in our digital calendar whether the bike
was booked. For this purpose, we create a new user account for the
location with the role CommonsBooking Manager. Then select the
settings of the location to be managed and enter the user account
in the list of CommonsBooking Managers.

Now the location account can view all bookings for
this location via "My Bookings". If, as described above, the URL for the
digital calendar is inserted into the calendar of the location operators,
then they will see all bookings directly in their digital calendar.

