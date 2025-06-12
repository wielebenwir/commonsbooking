#  Configure booking rules (Since 2.9)

__

Using booking rules, you can restrict the use of items by users across the instance.
For instance, if you want to prevent a user from booking multiple items on the same day
or limit excessive use, you can achieve this using booking rules.

Most rules apply instance-wide. So, for example when you use the rule "Maximum booked days per week"
and apply it to all items, users can only book x days per week across the entire instance.
For this reason, it is also possible to apply the rule only to items of certain categories.
Using this feature, you could define that a specific category of items can only be booked for 2 days per week
and another category for 3 days per week.

## Difference to the "Maximum" setting in the timeframe
In the [Timeframe settings](/en/documentation/first-steps/booking-timeframes-manage) it is possible to set
the maximum amount of days an item can be booked in a row (single booking). So users could create multiple bookings which then exceed the maximum value.
Booking rules, on the other hand, define how many days a user can book in total per week, month, or within a specific time period.
They allow you to restrict the use of items by users and prevent them from creating excessive bookings.
Additionally, booking rules can be applied to multiple items. So if you have several items, users can only book them for as many days as defined in the booking rule.

## Setting up booking rules

The settings for booking rules can be found under "Settings" -> "CommonsBooking" ->
in the "Restrictions" tab. Scroll down all the way to the bottom. There you can add or delete rules.

##  Types of rules

###  Forbid simultaneous bookings

Prevents users from booking more than one item on the same day.
When a specific item category is set, this rule only applies to items that share the same category.

###  Prohibit chain-bookings

Prevents users from circumventing the maximum booking limit (default 3 days)
in a way that they create two consecutive bookings for the same item.
When this rule is activated, users must leave at least one day between bookings.

###  Maximum booked days per week

Defines how many days a user can book per week (either for all items or for items of specific categories).
Starting from the day set as the reset day, the new week will start.
So for example, if Monday is set as the reset day and only one day per week
can be booked, the user can book on both Sunday and Monday.

###  Maximum booked days per month

Legt fest, wie viele Tage ein Nutzer maximal pro Monat buchen darf (entweder
für alle Artikel oder für Artikel von bestimmten Kategorien). Ab dem Tag, der
als Resettag gesetzt ist, beginnt dann nach der Definition der neue Monat.
Wenn also z.B. der 15. als Resettag gesetzt ist und nur ein Tag pro Monat
gebucht werden darf, dürfte die Person sowohl am 14. als auch am 15. buchen.

###  Maximal gebuchte Tage in Zeitraum

Legt fest, wie viele Tage ein Nutzer über einen bestimmten Zeitraum von Tagen
hinweg einen Artikel buchen darf. Die Zählung des Zeitraums beginnt immer von
der Mitte aus. Wenn also 30 Tage gesetzt sind, dann werden die 15 Tage vor und
nach der gegebenen Buchung als Zeitraum berücksichtigt.

##  Stornierte Buchungen auf Quote anrechnen

Wenn diese Option aktiviert ist, dann zählen stornierte Buchungen auch mit in
die maximal buchbaren Tage für die Buchungsregeln. Dabei gilt:

  * Buchung vor Beginn des Buchungszeitraums storniert: Zählt **nicht** mit in die Quote
  * Buchung während des Buchungszeitraums storniert: Buchung zählt die Tage von Beginn des Buchungszeitraums bis zu der Stornierung. Wenn also eine Buchung von Montag bis Mittwoch geht, und diese am Dienstag storniert wird dann zählt diese für 2 Tage und nicht für 3.

###  Bestimmte Rollen grundsätzlich von allen Buchungsregeln ausnehmen

Mit einem kleinen Codeschnipsel ( [ mehr dazu ](/dokumentation/einstellungen/hooks-und-
filter) ) kannst du eine Rolle definieren, die grundsätzlich nicht von
Buchungsregeln betroffen ist. Dafür musst du die Rolle nicht manuell bei jeder
Regel hinzufügen.



    add_filter('commonsbooking_privileged_roles', function($privileged_roles) {
        $privileged_roles[] = 'editor';
        return $privileged_roles;
    });

Dieser Schnipsel fügt zum Beispiel die Rolle "Redakteur" mit dem slug editor
als "privilegierte" Rolle hinzu.

Darüber hinaus sind auch alle Administratoren und CB-Manager, denen der
betroffene Artikel / Standort zugewiesen ist immer ausgenommen. [ Mehr zu
manueller Vergabe von Berechtigungen. ](/dokumentation/grundlagen/rechte-des-
commonsbooking-manager)

