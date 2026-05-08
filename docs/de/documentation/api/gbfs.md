# GBFS

Diese Schnittstelle stellt Daten der [Standorte](../first-steps/create-location),
[Artikel](../first-steps/create-item) und deren Verfügbarkeit über die
[Zeitrahmen](../first-steps/booking-timeframes-manage) in einem stadardisierten Schema bereit.
Aktuell wird die Version 3.1-RC2 der _General Bikeshare Feed Specification_ ([GBFS](https://www.gbfs.org/documentation/)) unterstützt und die folgenden Endpunkte werden vom Plugin exponiert:

* station_status
* station_information
* system_information
* vehicle_status
* vehicle_availability
* gbfs.json (Discovery)

::: info Behobenes Problem
Seit Juni 2026 (Version 2.11) ist der [Fehler zur Bereitstellung der zeitlichen Verfügbarkeit](https://github.com/wielebenwir/commonsbooking/issues/1388) behoben.
:::
