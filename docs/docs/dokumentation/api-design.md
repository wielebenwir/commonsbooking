---
status: wip
---

# Schnittstellen und API Design

[[toc]]

## Allgemein

Es gibt verschiedene Wege mit dem CommonsBooking-Plugin zu interagieren.
Hier beschrieben sind die REST-basierten Web-APIs [CommonsAPI]() und [GBFS]().

Andere Schnittstellen wie [Ajax]()-Requests oder die [Wordpress-REST-API]() sind hier nicht beschrieben.

## CommonsAPI

`Status der Umsetzung: Wartend`

Diese API basiert auf der Wordpress REST-API und ist über eigenes [JSON-Schema]() definiert.
Es soll ermöglichen Artikel von unterschiedlichen Verleih-Organisationen über eine einheitliche 
Schnittstelle erreichbar zu machen.

Bisherige Arbeiten zu einem Anwendungsfall wurden in [commons-api-frontend]() und [commons-api-backend]() 
beschrieben. Seit 2021 wurde hier nicht aktiv weitergearbeitet.

## GBFS

`Status der Umsetzung: Fertig`

Die General Bikesharing Feed Specification hilft dir Transportmöglichkeiten zum Ausleihen für 
internetfähige Geräte konsumierbar zu machen.

Falls du über das Plugin Fahrräder oder sonstige Transportmöglichkeiten verleihst, ist eine Schnittstelle in 
diesem Format sinnvoll, da du so Fahrräder die zum Verleih verfübar sind in einem etablierten Format 
veröffentlichst.

Dieses Schema ist in [Version 2]() der Spezifikation veröffentlicht.

**Limitierung** bei der Implementierung: Die Attribute [`installed`]() und [`type`]() sind nicht umgesetzt. 
Da es sich bei den "freien Lastenrädern" i.d.R. um eine [Face-2-Face]()
Interaktion handelt und Buchungen ca. 24h im vorraus stattfinden, ist diese API nicht hilfreich zur Exploration von
aktuellen Buchungen (um direkt zu buchen), sondern eher um Statistiken von Stationsstandorten oder von verfügbaren 
Bikes anzufertigen.

