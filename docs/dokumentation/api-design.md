# Schnittstellen und API Design

[[toc]]

## Allgemein

Es gibt verschiedene Wege mit dem CommonsBooking-Plugin zu interagieren.
Hier beschrieben sind die REST-basierten Web-APIs [CommonsAPI]() und [GBFS]().

Andere Schnittstellen wie [Ajax]()-Requests oder die [Wordpress-REST-API]() sind hier nicht beschrieben.

## CommonsAPI

Diese API basiert auf der Wordpress REST-API und ist über eigenes [JSON-Schema]() definiert.
Es soll ermöglichen Artikel von unterschiedlichen Verleih-Organisationen über eine einheitliche Schnittstelle erreichbar
zu machen.

## GBFS

Falls du über das Plugin Fahrräder verleihst, ist dieses Format sinnvoll, da du so Fahrräder die zum Verleih verfübar
sind veröffentlichen kannst.

Dieses Schema ist in [Version 2]() der Spezifikation veröffentlicht.

**Limitierung** bei der Implementierung: Die Attribute [`installed`]() und [`type`]() sind nicht umgesetzt. 
Da es sich bei den "freien Lastenrädern" i.d.R. um eine [Face-2-Face]()
Interaktion handelt und Buchungen ca. 24h im vorraus stattfinden, ist diese API nicht hilfreich zur Exploration von
aktuellen Buchungen (um direkt zu buchen), sondern eher um Statistiken von Stationsstandorten oder von verfügbaren 
Bikes anzufertigen.

