# GBFS

::: danger Fehler in der API
Mit [Issue 1388](https://github.com/wielebenwir/commonsbooking/issues/1388) ist ein Fehler in der API dokumentiert. Die zeitliche Verfügbarkeit ist u.U. also nicht korrekt.
:::

Seit 2.5

Diese Schnittstelle stellt Daten der [Standorte](/dokumentation/erste-schritte/stationen-anlegen),
[Artikel](/dokumentation/erste-schritte/artikel-anlegen) und deren Verfügbarkeit über die
[Zeitrahmen](/dokumentation/erste-schritte/buchungszeitraeume-verwalten) in einem stadardisierten Schema bereit.
Die folgenden Endpunkte der _General Bikeshare Feed Specification_ ([GBFS](https://www.gbfs.org/documentation/)) Schema werden unterstützt:

* station_status
* station_information
* system_information
* gbfs.json (Discovery)
