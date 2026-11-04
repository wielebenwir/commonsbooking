# GBFS

::: tip Aktivierung der API
Die API ist standardmäßig deaktiviert und es gibt keine Verbindungen zu externen Services oder Plattformen.
Die API aktivierst du über die Einstellungen (s.u.).
:::


Diese Schnittstelle stellt Daten der [Standorte](../first-steps/create-location),
[Artikel](../first-steps/create-item) und deren Verfügbarkeit über die
[Zeitrahmen](../first-steps/booking-timeframes-manage) in einem stadardisierten Schema bereit.
Aktuell wird die Version 3.1-RC3 der _General Bikeshare Feed Specification_ ([GBFS](https://www.gbfs.org/documentation/)) unterstützt und die folgenden Endpunkte werden vom Plugin exponiert:

* station_status.json
* station_information.json
* system_information.json
* vehicle_status.json
* vehicle_availability.json
* vehicle_types.json
* gbfs.json (Discovery)

##  Einstellungen

Die API erreichst du über _Einstellungen_ -> _CommonsBooking_ -> Tab: _API / Export_.

* API aktivieren: Aktiviert generell den API-Zugriff (auch für die [Commons API](commonsbooking-api)).

### Artikel von der API ausschliessen

Wenn Artikel nicht in der API erscheinen sollen, müssen diese explizit ausgeschlossen werden.
Diese Einstellungen kann auf Artikelebene vorgenommen werden, wenn die API aktiviert ist.
Wenn in der Artikel-Ansicht der Haken im entsprechenden Meta-Feld gesetzt ist, tauchen diese Artikel nicht in den API Routen auf.

## Zu vehicle_types.json

Da über CommonsBooking hauptsächlich Lastenräder verliehen werden, gibt die API standardmäßig den `form_factor` als `cargo_bicycle` zurück. Da `propulsion_type` ein Pflichtfeld ist, wird hier standardmäßig `human` gesetzt. Wenn das nicht der Fall wäre, müssten noch viel mehr zusätzliche Informationen zum Antrieb zur Verfügung gestellt werden.

::: info Behobenes Problem
Seit Juni 2026 (Version 2.11) ist der [Fehler zur Bereitstellung der zeitlichen Verfügbarkeit](https://github.com/wielebenwir/commonsbooking/issues/1388) behoben.
:::
