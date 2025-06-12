# Cache einstellen

--

::: warning Technisch!
Um zu verstehen ob der Cache in deiner Installation korrekt funktioniert benötigst du technisches Verständnis.
Wir versuchen trotzdem dass der Einsatz des Cache möglichst einfach funkioniert.
:::


Unter **"Erweiterte Optionen"** befinden sich die Einstellungen für den Cache.
Der Cache hält Ausleih-Daten zu häufig gestellten Anfragen vor und soll so die Antwortzeiten optimieren.

* Standardmäßig ist der dateibasierte Cache aktiviert.
* Alternativ kann ein Cache basierend auf [REDIS](http://redis.io) konfiguriert werden. Du benötigst dazu die DSN. Frage dazu z.B. deinen Webhoster nach Support.
* Wir empfehlen es grundsätzlich nicht den Cache zu deaktivieren, sollte das dennoch gewünscht sein, kannst du als Cache Adapter "Cache deaktiviert" auswählen.

## Troubleshooting

::: danger Experimentell
Ein falsch konfigurierter kann deine Seite verlangsamen.
:::

* Wenn Buchungen direkt nach der Buchung nicht erscheinen, kannst du probieren über **"Cache leeren"** den Cache zu leeren.
  Wenn die Buchungen dann wieder erscheinen deutet das darauf hin, dass der Cache nicht richtig funktioniert.
  Probiere dann einen anderen Pfad für den Cache oder einen anderen Cache Adapter.

* Falls deine Seite sehr langsam ist, kann das auch auf Problem mit dem Cache hindeuten.
  Mehr dazu: [Die Seite ist sehr langsam](/dokumentation/haeufige-fragen-faq/die-seite-ist-sehr-langsam).

## Bekannte Probleme

In der Vergangenheit gab es bereits Probleme mit anderen Wordpress-Plugins wie z.B. 'REDIS Object Cache'.
Aus diesem Grund raten wir von der Nutzung solcher Plugins ab.

## Diskussion

Technisch nutzt CommonsBooking aktuell die Symfony-Cache Interfaces.

Eine Diskussion über die Performance von CommonsBooking findest du hier: https://github.com/wielebenwir/commonsbooking/discussions/1465.
Wir sind uns der Performance Probleme bei Instanzen mit sehr vielen Artikeln und Buchungen bewusst und arbeiten daran diese zu verbessern.
