# GBFS

::: danger Fehler in der API
Mit [Issue 1388](https://github.com/wielebenwir/commonsbooking/issues/1388) ist ein Fehler in der API dokumentiert. Die zeitliche Verfügbarkeit ist u.U. also nicht korrekt.
:::

Seit 2.5

Diese Schnittstelle stellt Daten der [Standorte](/documentation/first-steps/create-locations),
[Artikel](/documentation/first-steps/create-item) und deren Verfügbarkeit über die
[Zeitrahmen](/documentation/first-steps/booking-timeframes-manage) in einem stadardisierten Schema bereit.
Die folgenden Endpunkte der _General Bikeshare Feed Specification_ ([GBFS](https://www.gbfs.org/documentation/)) Schema werden unterstützt:

* station_status
* station_information
* system_information
* gbfs.json (Discovery)
