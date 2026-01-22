#  Häufige Fragen (FAQ)

__

###  Artikel

  * [ Wie bekomme ich den Buchungskommentar auf die Seite und in die Email? ](/dokumentation/haeufige-fragen-faq/wie-bekomme-ich-den-buchungskommentar-auf-die-webseite-zu-den-buchungsinformationen-etc-sowohl-als-auch-in-die-email)
  * [ Wie kann ich die Artikeldetailseite übersichtlicher gestalten? ](/dokumentation/haeufige-fragen-faq/wie-kann-ich-die-artikeldetailseite-uebersichtlicher-gestalten)
  * [ Die Seite ist sehr langsam ](/dokumentation/haeufige-fragen-faq/die-seite-ist-sehr-langsam)
  * [ Wie füge ich Zahlenschloss-Codes in E-Mails ein? ](/dokumentation/haeufige-fragen-faq/kann-ich-zahlenschloss-codes-in-e-mails-einfuegen)


## Ich möchte einzelne Nutzer vorübergehend sperren

Ihr möchtet Nutzende vorübergehend für einen bestimmten Zeitraum sperren, weil diese z.B. die Ausleihe übermäßig nutzen oder gegen Ausleihbedingungen verstoßen?

Ich würde euch dazu raten mit einem entsprechenden Plugin zum Blockieren von Nutzenden für einen bestimmten Zeitraum einfach den gesamten Login zu blockieren. Da gibt es auch schon einige Plugins dafür. Wenn eure Nutzenden sowieso nichts anderes auf der Seite machen können als Räder zu buchen erscheint mir das auch sinnvoll es auf diese Art und Weise zu machen.

Ich habe nur kurz bei mir mal das Plugin https://wordpress.org/plugins/user-blocker/ ausprobiert und das hat problemlos funktioniert, auch mit einer Zeitschaltfunktion. Das ist aber nicht das einzige Plugin, was diese Funktion hat.

Das Blockieren bestimmter Benutzergruppen ist kein Feature in CommonsBooking und wird wahrscheinlich auch so bald nicht als Feature kommen, da es schon einige Plugins gibt die genau das erledigen.

## Überbuchbare Tage erlauben / Buchung übers Wochenende

Wenn ihr euren Nutzenden ermöglichen wollt, den Artikel z.B. über das Wochenende zu buchen, könnt ihr diese Einstellung in den Standort-Einstellungen vornehmen.

Infos dazu unter: https://commonsbooking.org/docs/erste-schritte/stationen-anlegen/

## Anzahl der Artikel in der cb_items Liste erhöhen

Die Anzahl der Artikel pro Seite wird über die globalen Einstellungen von Wordpress übernommen.

Diese globale Einstellungen können hier geändert werden:

Als Wordpress Administrator einlogge:

Einstellungen -> Lesen -> Blogseiten zeigen maximal

##  Fehlerhafte Anzeige des Kalender-Widget im Admin-Bereich

Treten Probleme bei der Anzeige des Kalenders im Admin-Bereich der Buchungen
auf (sog. Admin-Backend), siehe das folgende Bild rechts unten, kann eine
mögliche Lösung sein, das [ Plugin "Lightstart" (wp-maintenance-mode) ](https://wordpress.org/plugins/wp-maintenance-mode) zu deaktivieren oder zu
entfernen und neu zu installieren. Das Problem ist eine Inkompatibilität von
Lightstart mit CommonsBooking und kein Fehler im Code von CommonsBooking. Das
Problem tritt nicht mehr auf, wenn eine Neuinstallation von Lightstart
vorgenommen wurde. Mehr dazu auf [ Github im CommonsBooking Quellcode-Repository ](https://github.com/wielebenwir/commonsbooking/issues/1646).

![](/img/backend-booking-list-bug.png)

## Inkompatibles Plugin All-in-one-Event Plugins

Leider kommt es bei der gleichzeitigen Nutzung des Plugins "All-in-one-Event" zu Fehlern, sodass Seiten, die von CommonsBooking erzeugt werden nicht angezeigt werden.

Die Ursache ist leider auf eine schlechte Programmierung des All-in-one-Event-Plugins zurückzuführen, welches sich nicht an die Wordpress-Standards hält und so tief in Wordpress eingreift, dass es die Programmlogik von CommonsBooking quasi überschreibt.

Wir haben einiges versucht, um eine parallele Nutzung zu ermöglichen, leider aber bisher keine Lösung gefunden.

Wenn ihr das Problem auch habt, schreibt gerne direkt an den Support des Plugins, vielleicht passen sie ihr Plugin doch noch irgendwann an.

Für Experten: Wir haben in unserem github dazu auch ein Ticket: https://github.com/wielebenwir/commonsbooking/issues/675

## Inkompatibles Plugin REDIS Object Cache

Im Zusammenhang mit dem [Cache](/dokumentation/erweiterte-funktionalitaet/) gab
es in der Vergangenheit bereits Probleme mit anderen Wordpress-Plugins wie
z.B. 'REDIS Object Cache'. Aus diesem Grund raten wir von der Nutzung solcher
Plugins ab.

Deshalb sollten die von CommonsBooking generierten Seiten von der Optimierung durch Dritt-Plugins ausgenommen werden.
CommonsBooking verwendet ein eigenes Caching.


## Inkompatibles Plugin Ultimate Member

Wenn ihr das Plugin Ultimate Member benutzt und die Benutzerrolle "CommonsBooking Manager" nutzen möchtet, müsst ihr in Ultimate Member für die  Rolle cb_manager noch ein Häkchen setzen, um diese für den AP-Admin-Zugang zu aktivieren.

## Inkompatible Plugins: Autoptimize / Caching

Optimierungs-Plugins oder weitere Caching-Plugins können dazu führen, dass CommonsBooking nicht alle Seiten anzeigen kann.

Nicht vollständige Liste:
* Autoptimize

Deshalb sollten die von CommonsBooking generierten Seiten von der Optimierung durch Dritt-Plugins ausgenommen werden.
CommonsBooking verwendet ein eigenes Caching.

::: info Hast du ein Problem festgestellt?
Dann trage die Inkompatibilität hier ein!
:::


##  Inkompatibles Theme Gridbulletin

In der letzten Version von [ GridBulletin ](https://wordpress.org/themes/gridbulletin) kommt es zu einer
Inkompatibilität mit CommonsBooking. Probleme tauchen auf, wenn der Footer
aktiviert ist. Konkrete Probleme sind z.B. das Fehlen des Buchungs-Kalenders
auf der Artikelseite. Aus technischer Sicht liegt es daran, dass die nötigen
Javascript-Quellen von CommonsBooking nicht ausgeliefert werden. Der Grund
innerhalb des GridBulletin Themes oder eine Lösung konnte bisher nicht
gefunden werden.



