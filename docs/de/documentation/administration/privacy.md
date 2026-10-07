# Datenschutz

::: tip Seit 2026-01-15
Status: In Arbeit
:::

Diese Seite soll eine Übersicht über durch CommonsBooking genutzte Drittanbieterdienste geben, damit diese in Datenschutzerklärungen für die Nutzenden berücksichtigt werden können und für Seitenbetreibende nachvollziehbar sind.

## Drittanbieterdienste

### Nominatim

Das Plugin verwendet [Nominatim](https://nominatim.org) um aus Straßen- bzw. Haus-Adressen die Geo-Koordinaten zu berechnen, auch geokodieren genannt.
Es wird aktuell keine Möglichkeit geboten einen alternativen Geo-Coding-Service zu nutzen.
Wir haben uns für Nominatim entschieden, da der Service quelloffen ist und, durch die OSM-Community unterstützt, betrieben wird.

#### Was wird gesendet

Bei der Anfrage des entfernten Nominatim-Servers wird ein Plugin- und Seiten-spezifischer `UserAgent` Header und ein `Referer` Header gesetzt und somit werden keine Daten des Anfrage-Headers weitergeleitet, um z.B. Device-Fingerprinting zu vermeiden. Beide Werten helfen dem Nominatim-Server die Anfragen der Installation zurückverfolgen um z.B. Missbrauch zu erkennen und sind Teil der Nutzungsbedingungen von Nominatim.
