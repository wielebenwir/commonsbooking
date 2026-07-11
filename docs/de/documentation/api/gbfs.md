# GBFS

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

## Zu vehicle_types.json

Da über CommonsBooking hauptsächlich Lastenräder verliehen werden, gibt die API standardmäßig den `form_factor` als `cargo_bicycle` zurück. Da `propulsion_type` ein Pflichtfeld ist, wird hier standardmäßig `human` gesetzt. Wenn das nicht der Fall wäre, müssten noch viel mehr zusätzliche Informationen zum Antrieb zur Verfügung gestellt werden.

::: info Behobenes Problem
Seit Juni 2026 (Version 2.11) ist der [Fehler zur Bereitstellung der zeitlichen Verfügbarkeit](https://github.com/wielebenwir/commonsbooking/issues/1388) behoben.
:::
