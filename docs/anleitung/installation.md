---
outline: false
---

# Installation

[[toc]]

## ComonsBooking installieren

Installieren kannst du es wie jedes andere Wordpress-Plugin.

1. Gehe zu WordPress->Plugins->Installieren  und Suche nach “CommonsBooking“.
2. Nach Klick auf “Installieren” und “Aktivieren” ist es einsatzbereit.

### WordPress-Einstellungen für Datum und Zeit prüfen

CommonsBooking arbeitet mit den Zeit- und Datumseinstellungen, die du in WordPress unter “Einstellungen -> Allgemein -> Zeitzone” eingestellt hast. Auch die Formatierungen für die Zeitanzeige (24h-Format etc.) und die Datumsformatierung übernimmt CommonsBooking aus den allgemein WordPress-Einstellungen.

Daher prüfe bitte, dass du die richtige Zeitzone in WordPress konfiguriert hast. Wenn du das noch nicht getan hast hole unter “Einstellungen / Allgemein / Zeitzone” nach.

## CommonsBooking konfigurieren

In den WordPress-Einstellungen findest Du nun einen neuen Punkt “CommonsBooking”. Hier kannst Du einige Voreinstellungen vornehmen.  Bitte mindestens den Absender-Namen und E-Mail im Tab “Vorlagen” eintragen Weitere Informationen zur Konfiguration.

## Standorte, Artikel und Zeitrahmen anlegen

* Artikel unter “CommonsBooking -> Artikel” anlegen
* Standorte unter “CommonsBooking -> Standorte” anlegen
* Anschließend kannst du im Menüpunkt “Zeitrahmen” festlegen, wann ein Artikel an einem bestimmten Standort für die Ausleihe verfügbar sein soll.

Detailliierte Informationen dazu findest du unter “Erste Schritte“.

## Inhalte auf der Website einbinden

Lege zuerst eine Seite an, auf der deine Artikel erscheinen sollen.
Füge dann einen Textbaustein mit (Shortocde) `[cb_items]` in diese Seite ein.
Mit dem klassischen WordPress-Editor fügst du `[cb_items]` inklusive der Klammern einfach in das Textfeld ein.
Mit dem neuen Editor klickst du auf das schwarze + Plus im Kasten, wählst “Shortcode” und fügst `[cb_items]` inklusive der Klammern ein.
Weitere Shortcodes für Karten, Tabellen, etc. Fertig!

Im Frontend stehen nun die Artikel als Liste zu Buchung zur Verfügung.

Siehe [Shortcodes](/dokumentation/shortcodes) für eine Liste aller verfügbaren Shortcodes.


::: info Hinweis
Wenn auf der Artikel- oder Standortliste (die Seiten mit dem cb_items oder cb_locations-Shortcode) nach Klick auf “Jetzt Buchen” keine gültige Seite angezeigt wird, müsst ihr einmal in den WordPress Einstellungen auf die Seite “Permalinks” gehen und dort auf speichern klicken.
:::
