# Cache einstellen

--

::: warning Technisch!
Um zu verstehen ob der Cache in deiner Installation korrekt funktioniert benötigst du technisches Verständnis.
Wir versuchen trotzdem, dir den Einsatz des Caches möglichst einfach zu gestalten.
:::


Unter **"CommonsBooking > Einstellungen > Erweiterte Optionen"** befinden sich die Einstellungen für den Cache.
Der Cache hält Ausleih-Daten zu häufig gestellten Anfragen vor und soll so die Antwortzeiten optimieren.

* Standardmäßig ist der dateibasierte Cache aktiviert.
* Alternativ kann ein Cache basierend auf [REDIS](http://redis.io) konfiguriert werden. Du benötigst dazu die DSN. Frage dazu z.B. deinen Webhoster nach Support.
* Wir empfehlen es grundsätzlich nicht den Cache zu deaktivieren, sollte das dennoch gewünscht sein, kannst du als Cache Adapter "Cache deaktiviert" auswählen.

Falls CommonsBooking aufgrund eines Problems mit dem Cache nicht aktiviert werden kann, kannst du den Cache standardmäßig
mit diesem Codeschnipsel deaktivieren. [ Mehr dazu, wie du Codeschnipsel nutzen kannst ](/dokumentation/einstellungen/hooks-und-filter).
Dies wird nur empfohlen, wenn andere Methoden den Cache zu deaktivieren nicht funktionieren.

```php
add_filter('commonsbooking_disableCache', function() {
    return true;
} );
```

## Troubleshooting

::: danger Experimentell
Ein falsch konfigurierter Cache kann deine Seite verlangsamen.
:::

* Wenn Buchungen direkt nach der Buchung nicht erscheinen, kannst du probieren über **"Cache leeren"** den Cache zu leeren.
  Wenn die Buchungen dann wieder erscheinen deutet das darauf hin, dass der Cache nicht richtig funktioniert.
  Probiere dann einen anderen Pfad für den Cache oder einen anderen Cache Adapter.

* Falls deine Seite sehr langsam ist, kann das auch auf Problem mit dem Cache hindeuten.
  Mehr dazu: [Die Seite ist sehr langsam](/dokumentation/haeufige-fragen-faq/die-seite-ist-sehr-langsam).

* Siehe auch die Seite [Häufige Fragen](/dokumentation/haeufige-fragen-faq) für bekannte Inkompatibilitäten.
* **Regelmäßiges Aufwärmen des Caches durch Cronjob**:
  :::warning ACHTUNG
  Diese Einstellung ist nur für ganz besondere Randfälle gedacht und betrifft dich vermutlich nicht.Außerdem ist die Funktion experimentell und kann unerwünschte Nebeneffekte haben. Wir konnten zum Beispiel nicht feststellen, ob der Cache nach einer Buchung rechtzeitig geleert wird. Der Cronjob sollte wahrscheinlich relativ häufig ausgeführt werden, wenn die Funktion genutzt werden soll.
  :::
  Wenn deine Seite selten aufgerufen wird aber viele Artikel / Buchungen enthält kann es sein, dass beim ersten Aufrufen
  die Seite sehr langsam reagiert. Falls sich das zum Problem entwickelt, kannst du den Cache regelmäßig aufwärmen lassen.
  Das geht mit der Option "Regelmäßiges Aufwärmen des Caches durch Cronjob". Wenn diese Checkbox aktiviert ist, wird der Cache regelmäßig
  durch einen Cronjob aufgewärmt. Anschließend kannst du einstellen, wie oft der Cache automatisch aufgewärmt werden soll. Dies kann zu höherer Serverlast führen, wenn der Cache sehr regelmäßig aufgewärmt wird.
  Damit das gelingt MUSS WP-Cron in den System Task Scheduler eingebunden sein. Siehe hier: [Hooking WP-Cron Into the System Task Scheduler](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/)

## Bekannte Probleme

* Siehe auch die Seite [Häufige Fragen](/dokumentation/haeufige-fragen-faq) für bekannte Inkompatibilitäten.

## Diskussion

Technisch nutzt CommonsBooking aktuell die Symfony-Cache Interfaces.

Eine Diskussion über die Performance von CommonsBooking findest du hier: https://github.com/wielebenwir/commonsbooking/discussions/1465.
Wir sind uns der Performance Probleme bei Instanzen mit sehr vielen Artikeln und Buchungen bewusst und arbeiten daran diese zu verbessern.
