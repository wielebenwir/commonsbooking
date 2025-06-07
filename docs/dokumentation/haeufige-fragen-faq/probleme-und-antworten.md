#  Probleme und Antworten

__

###  Anzeige Kalender-Widget im Admin-Bereich

Treten Probleme bei der Anzeige des Kalenders im Admin-Bereich der Buchungen
auf (sog. Admin-Backend), siehe das folgende Bild rechts unten, kann eine
mögliche Lösung sein, das [ Plugin "Lightstart" (wp-maintenance-mode)
](https://wordpress.org/plugins/wp-maintenance-mode) zu deaktivieren oder zu
entfernen und neu zu installieren. Das Problem ist eine Inkompatibilität von
Lightstart mit CommonsBooking und kein Fehler im Code von CommonsBooking. Das
Problem tritt nicht mehr auf, wenn eine Neuinstallation von Lightstart
vorgenommen wurde. Mehr dazu auf [ Github im CommonsBooking Quellcode-Repository ](https://github.com/wielebenwir/commonsbooking/issues/1646).

![](/img/backend-booking-list-bug.png)

###  Inkompatibles Theme Gridbulletin

In der letzten Version von [ GridBulletin
](https://wordpress.org/themes/gridbulletin) kommt es zu einer
Inkompatibilität mit CommonsBooking. Probleme tauchen auf, wenn der Footer
aktiviert ist. Konkrete Probleme sind z.B. das Fehlen des Buchungs-Kalenders
auf der Artikelseite. Aus technischer Sicht liegt es daran, dass die nötigen
Javascript-Quellen von CommonsBooking nicht ausgeliefert werden. Der Grund
innerhalb des GridBulletin Themes oder eine Lösung konnte bisher nicht
gefunden werden.

