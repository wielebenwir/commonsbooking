<script setup>
import Newsletter from '../.vitepress/components/Newsletter.vue'
</script>

#  Merkmale & Funktionen

![](/img/banner-772x250-1.png)

CommonsBooking gibt euch die Möglichkeit, Dinge (etwa: Lastenräder, Werkzeuge)
zur gemeinschaftlichen Verwendung bereit zu stellen.

CommonsBooking ist ein WordPress-Plugin und lässt sich so einfach in
bestehende Webseiten integrieren.

<div>
  <a class="cbdoc-button cb-brand" href="./documentation/setup/install">Installieren</a>
  <a class="cbdoc-button cb-alt" href="./documentation/">Dokumentation</a>
</div>

##  Die wichtigsten Features

###  Flexible Buchung

  * **Neu:** Mehrere Buchungen pro Tag (etwa: Stundenweise Buchung)
  * Vollständige Buchungsstrecke (Checkout) mit Buchungscodes
  * Einstellbare maximale Buchungsdauer
  * Bestätigungs-Mails an Ausleihende und Stationen
  * Kartendarstellung im Frontend mit Filtermöglichkeiten

![](/img/hourly-booking.png)

###  Einfache Administration

  * **Neu:** Benutzer*innen können zugeordnete Artikel, Stationen und Zeiträume selbst verwalten.
  * Automatische Buchungsannahme: Nutzende können Gegenstände buchen, ohne dass Administration nötig ist.
  * Kalender-Integration: Automatischer Import aller relevanter Buchungen in den digitalen Kalender via [ICal-Format](documentation/manage-bookings/icalendar-feed).

![](/img/cb-managers.png)

* * *

##  Screenshots

![](/img/booking-calendar.png) Buchungskalender mit stundenweiser
Ausleihe  ![](/img/booking-confirm.png) Buchungsstrecke in
CommonsBooking  ![](/img/shortcode-cb-map-filtergroups.png) Kartendarstellung
mit Verfügbarkeit  ![](/img/shortcode-cb-items.png) Übersichtliche
Artikelliste

* * *

##  Weiterentwicklung

CommonsBooking wird aktuell ständig weiterentwickelt. Folgende Meilensteine
sind Teil der geplanten Weiterentwicklung:

  * Erweiterte Meta-Daten
  * Implementierung der CommonsAPI

<div>
  <a class="cbdoc-button cb-brand" href="./documentation/setup/install">Installieren</a>
  <a class="cbdoc-button cb-alt" href="./documentation/roadmap/">Roadmap der geplanten Weiterentwicklung</a>
</div>


##  Anwendungsbereiche

Entwickelt wurde das WordPress-Plugin ursprünglich für die Bedürfnisse der [„Freies Lastenrad“-Bewegung](https://freies-lastenrad.org/),
es kann aber für den Verleih beliebiger Dinge verwendet werden.

  * Du/deine Organisation besitzt Werkzeuge, die nicht täglich genutzt werden, und ihr möchtet sie verfügbar machen für lokale Gruppen.
  * Du besitzt ein Lastenrad und willst es mit der Gemeinschaft teilen, es soll über das Jahr hinweg an verschiedenen Standorten stationiert sein.

## Alternativen

Im Folgenden sind einige FOSS (Free and Open Source) Alternativen zu CommonsBooking aufgelistet. Diese Liste soll zur Entscheidungsfindung dienen. Sie ist unvollständig und kann gerne ergänzt werden. Besonders um zu sehen welche Leihläden welches Software benutzen, können wir [diese Übersicht](https://github.com/mojoaxel/awesome-leihladen) empfehlen. Die Unterscheidung ist aus Sicht von CommonsBooking geschrieben, dh es sind alle Features aufgelistet, die CommonsBooking nicht unterstützt. Diese Liste enthält nur Software, die noch aktiv weiterentwickelt wird.

| Name | Anwendungsbereich | Unterscheidung zu CommonsBooking | Existiert seit |
| ---- | ----------------- | -------------------------------- | -------------- |
|[cosum.de](https://cosum.de/) | Leihladen | - Leihanfragen möglich (die Bestätigung benötigen) <br> - Schnelles Erstellen vieler Artikel möglich, da keine Zeitrahmen benötigt werden <br> - Komplettpaket mit integrierter Nutzendenverwaltung | 2019 
|[leihbase](https://leihbase.org/) | Leihladen | - Artikelreservierungen möglich <br> - Komplettpaket (benötigt kein WordPress) <br> - Kann Statistiken erstellen <br> - Kann als docker container ausgeliefert werden | 2024 
|[llka](https://buergerstiftung-karlsruhe.de/ll/unsere-software)|Leihladen | - Installation über einen Befehl (eigenständige Software) <br> - Self-Service Terminal Support <br> - Dashboards für die Teamverwaltung | 2026
|[dingsda2mex](https://commons.machinaex.org/) | Lagerverwaltung für Künstler:innen | - Auf Ein - und Ausbuchen der Lagergegenstände für Smartphones und Laptops optimiert <br> - Fokus auf übersichtlichen Lagerbetrieb <br> - Export für Abrechnungstabellen oder Zollabfertigungstabellen möglich <br> - Eigenständige Software| 2021
|[Biletado](https://www.biletado.info/) | Buchungssoftware für Kommunen | - Optimiert auf Raumbuchung <br> - Mit Abrechnungssystem integrierbar <br> - Tickting und Veranstaltungsmanagement <br> - Eigenständige Software| 2024
|[Koha](https://koha-community.org/) | Bibliothekssoftware | - Erfassen von Büchern in standardisierten Formaten (MARC) <br> - Check out & check in Verfahren <br> - Labelerstellung und Inventarmangement <br> - Eigenständige Software| 2001
|[Biblioteq](https://textbrowser.github.io/biblioteq/) | Bibliothekssoftware | - Lokale Installation (Windows / Mac / Linux) <br> - Primär: Erfassung von Büchern | 2002
* * *

<Newsletter />
