# Cache einstellen

--

Unter **"Erweiterte Optionen"** befinden sich die Einstellungen für den Cache. CommonsBooking nutzt Caching, um häufig gestellte Anfragen zu optimieren.

Wenn Buchungen direkt nach der Buchung nicht erscheinen, kannst du probieren über **"Cache leeren"** den Cache zu leeren. Wenn die Buchungen dann wieder erscheinen deutet das darauf hin, dass der Cache nicht richtig funktioniert. Probiere dann einen anderen Pfad für den Cache oder einen anderen Cache Adapter.

Falls deine Seite sehr langsam ist, kann das auch auf Problem mit dem Cache hindeuten.
Mehr dazu: [Die Seite ist sehr langsam](/dokumentation/haeufige-fragen-faq/die-seite-ist-sehr-langsam).

Wir empfehlen es grundsätzlich nicht den Cache zu deaktivieren, sollte das dennoch gewünscht sein, kannst du als Cache Adapter "Cache deaktiviert" auswählen.

# Caching Plugins
In der Vergangenheit gab es bereits Probleme mit Caching Plugins wie z.B. 'REDIS Object Cache'. Aus diesem Grund raten wir von der Nutzung solcher Plugins ab.

Falls die Performance deiner Seite nicht ausreichend ist kannst du probieren einen [REDIS Cache](http://redis.io) zu nutzen oder bei deinem Webhoster einen leistungsfähigeren Server zu buchen.

# Diskussion
Eine Diskussion über die Performance von CommonsBooking findest du hier: https://github.com/wielebenwir/commonsbooking/discussions/1465 . Wir sind uns der Performance Probleme bei Instanzen mit sehr vielen Artikeln und Buchungen bewusst und arbeiten daran diese zu verbessern.
