#  Häufige Fragen (FAQ)

__

###  Artikel

  * [ Wie bekomme ich den Buchungskommentar auf die Seite und in die Email? ](/dokumentation/haeufige-fragen-faq/wie-bekomme-ich-den-buchungskommentar-auf-die-webseite-zu-den-buchungsinformationen-etc-sowohl-als-auch-in-die-email)
  * [ Wie kann ich die Artikeldetailseite übersichtlicher gestalten? ](/dokumentation/haeufige-fragen-faq/wie-kann-ich-die-artikeldetailseite-uebersichtlicher-gestalten)
  * [ Die Seite ist sehr langsam ](/dokumentation/haeufige-fragen-faq/die-seite-ist-sehr-langsam)
  * [ Probleme und Antworten ](/dokumentation/haeufige-fragen-faq/probleme-und-antworten)
  * [ Wie füge ich Zahlenschloss-Codes in E-Mails ein? ](/dokumentation/haeufige-fragen-faq/kann-ich-zahlenschloss-codes-in-e-mails-einfuegen)

## Inkompatible Plugins: Autoptimize / Caching

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=9

Optimierungs-Plugins oder weitere Caching-Plugins können dazu führen, dass CommonsBooking nicht alle Seiten anzeigen kann.

Nicht vollständige Liste:
* Autoptimize

::: info Hast du ein Problem festgestellt?
Dann trage die Inkompatibilität hier ein!
:::

Deshalb sollten die von CommonsBooking generierten Seiten von der Optimierung durch Dritt-Plugins ausgenommen werden.
CommonsBooking verwendet ein eigenes Caching.

## Ich möchte einzelne Nutzer vorübergehend sperren

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=7

Ihr möchtet Nutzende vorübergehend für einen bestimmten Zeitraum sperren, weil diese z.B. die Ausleihe übermäßig nutzen oder gegen Ausleihbedingungen verstoßen?

Ich würde euch dazu raten mit einem entsprechenden Plugin zum Blockieren von Nutzenden für einen bestimmten Zeitraum einfach den gesamten Login zu blockieren. Da gibt es auch schon einige Plugins dafür. Wenn eure Nutzenden sowieso nichts anderes auf der Seite machen können als Räder zu buchen erscheint mir das auch sinnvoll es auf diese Art und Weise zu machen.

Ich habe nur kurz bei mir mal das Plugin https://wordpress.org/plugins/user-blocker/ ausprobiert und das hat problemlos funktioniert, auch mit einer Zeitschaltfunktion. Das ist aber nicht das einzige Plugin, was diese Funktion hat.

Das Blockieren bestimmter Benutzergruppen ist kein Feature in CommonsBooking und wird wahrscheinlich auch so bald nicht als Feature kommen, da es schon einige Plugins gibt die genau das erledigen.

## Überbuchbare Tage erlauben / Buchung übers Wochenende

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=6

Wenn ihr euren Nutzenden ermöglichen wollt, den Artikel z.B. über das Wochenende zu buchen, könnt ihr diese Einstellung in den Standort-Einstellungen vornehmen.

Infos dazu unter: https://commonsbooking.org/docs/erste-schritte/stationen-anlegen/

## CommonsBooking mit Ultimate Member Plugin

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=5

Wenn ihr das Plugin Ultimate Member benutzt und die Benutzerrolle "CommonsBooking Manager" nutzen möchtet, müsst ihr in Ultimate Member für die  Rolle cb_manager noch ein Häkchen setzen, um diese für den AP-Admin-Zugang zu aktivieren.

## Fehler bei Nutzung des All-in-one-Event Plugins

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=4

Leider kommt es bei der gleichzeitigen Nutzung des Plugins "All-in-one-Event" zu Fehlern, sodass Seiten, die von CommonsBooking erzeugt werden nicht angezeigt werden.

Die Ursache ist leider auf eine schlechte Programmierung des All-in-one-Event-Plugins zurückzuführen, welches sich nicht an die Wordpress-Standards hält und so tief in Wordpress eingreift, dass es die Programmlogik von CommonsBooking quasi überschreibt.

Wir haben einiges versucht, um eine parallele Nutzung zu ermöglichen, leider aber bisher keine Lösung gefunden.

Wenn ihr das Problem auch habt, schreibt gerne direkt an den Support des Plugins, vielleicht passen sie ihr Plugin doch noch irgendwann an.

Für Experten: Wir haben in unserem github dazu auch ein Ticket: https://github.com/wielebenwir/commonsbooking/issues/675

## Anzahl der Artikel in der cb_items Liste erhöhen

Original Post: https://support.commonsbooking.org/knowledgebase.php?article=3

Die Anzahl der Artikel pro Seite wird über die globalen Einstellungen von Wordpress übernommen.

Diese globale Einstellungen können hier geändert werden:

Als Wordpress Administrator einlogge:

Einstellungen -> Lesen -> Blogseiten zeigen maximal


