#  iCalendar Feed

__

## Beschreibung

In den Einstellungen unter "Erweiterte Optionen" kannst du den iCalendar Feed
aktivieren. iCalendar ist ein sehr geläufiges Format für digitale Kalender.
Die meisten dieser digitalen Kalender erlauben es eine URL hinzuzufügen, mit
der automatisch Termine aus dem Buchungssystem in den digitalen Kalender
importiert werden können. Diese Kalendereinträge sind nicht beschreibbar, dh.
Änderungen am digitalen Kalender können sich nicht auf das Buchungssystem
auswirken.

Nachdem diese Funktion aktiviert ist, findest du die individuelle Kalender URL
in dem Menü in der Übersicht "Meine Buchungen".

![](/img/iCalendar-feed.png)

**ACHTUNG** : Dieser digitale Kalender listet nicht nur deine eigenen
Buchungen auf sondern alle Buchungen, die du einsehen darfst. Dies hat
besondere Auswirkungen auf den Administrator und die CommonsBooking Manager,
die Zugriffsrechte auf Stationen oder Artikel haben. Mehr dazu: [
Zugriffsrechte vergeben ](/dokumentation/grundlagen/rechte-des-commonsbooking-manager)

**ACHTUNG:** Vergangene Termine verschwinden aktuell noch aus dem Kalender.
Das kann sich unter Umständen ändern.

## Vorlagen

Der iCalendar Feed unterscheidet zwischen zwei Arten von Terminen: 
Termine des Nutzers selbst und Termine von anderen Nutzern. Wenn Nutzende
den Feed abonniert haben, dann werden sie hauptsächlich ihre eigenen Termine sehen,
Stationen werden, wenn sie die entsprechenden Berechtigungen haben, auch Termine von anderen Nutzenden sehen. Für die Nutzendensicht sind andere Dinge wichtig als für die Station. Z.B. wollen Nutzende eher wissen, wo ihre Station ist und wie sie den Artikel abholen können. Stationen dagegen wollen eher wissen wie die Nutzenden heißen, die den Artikel abholen und wie diese kontaktiert werden können. Aus diesem Grund existieren zwei Vorlagen für die Darstellung der Termine:

- Eigene Termine: "Einstellungen"->"CommonsBooking"->"Vorlagen"->"iCalendar Feed"
- Fremdtermine: "Einstellungen"->"CommonsBooking"->"Erweiterte Optionen"->"iCalendar Feed"

##  Anwendungsszenario Station

Dieses Szenario soll kurz verdeutlichen, wie diese Funktion sinnvoll genutzt
werden kann. Gehen wir davon aus, dass wir als Station für den Verleih von
Fahrrädern automatisch in unserem digitalen Kalender sehen wollen ob das Rad
gebucht wurde. Zu diesem Zweck erstellen wir einen neuen Nutzeraccount für die
Station mit der Rolle CommonsBooking Manager*in. Anschließend gehen wir in die
Einstellungen von dem zu verwaltenden Standort und tragen dort das Nutzerkonto
in die Liste der Stationsmanager ein.

Jetzt kann das Stationskonto über "Meine Buchungen" sämtliche Buchungen für
diesen Standort einsehen. Wenn nun wie oben beschrieben die URL für den
digitalen Kalender in den Kalender der Standortbetreibenden eingefügt wird,
dann sehen sie sämtliche Buchungen direkt in ihrem digitalen Kalender.

