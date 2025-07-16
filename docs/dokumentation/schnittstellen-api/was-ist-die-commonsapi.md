#  Was ist die CommonsAPI?

__

Über die CommonsAPI ist es möglich, dass die Dateien einzelner lokaler
CommonsBooking-Plugins über eine Schnittstelle mit zentralen Plattformen (wie
z.B. deutschlandweite Verzeichnisse von freien Lastenrädern oder ggf.
übergreifende Portale) verbunden werden. Die [Aktivierung](/dokumentation/schnittstellen-api/commonsbooking-api) und Freigaben können von euch
natürlich individuell eingestellt werden.

[Technische Details findet ihr hier](/dokumentation/schnittstellen-api/commonsbooking-api).

##  So funktionieren die CommonsAPI und das CommonsHub

![](/img/logo-api-items.png) Initiativen verleihen über
CommonsBooking (oder andere Software) Gemeingüter.

![](/img/logo-api-connects.png) Das CommonsBooking-Plugin veröffentlicht
(pushed) Daten im CommonsAPI Format.  ![](/img/logo-api-commonshub.png)
Externe Portale, wir nennen Sie CommonsHub, stellen die Gemeingüter
plattformübergreifend da, z.B. auf einer Karte.

## Aktueller Stand (Juli 2025)

Die CommonsAPI ist in allen CommonsBooking Installationen enthalten, jedoch nicht automatisch aktiviert. Ein CommonsHub ist technisch möglich und wir haben im letzten Jahr mit dem [@commonsbooking/frontend](https://www.npmjs.com/package/@commonsbooking/frontend) die nötige Vorarbeit geleistet die Datenstruktur so zu abstrahieren, dass ein Einbinden der CommonsAPI theoretisch möglich sein sollte. Jedoch hat die Entwicklung eines CommonsHub von unserer Seite gerade keine Priorität, da wir mit der Instandhaltung des Core Plugins beschäftigt sind. Aber kontaktiert uns gerne, wenn ihr ein CommonsHub entwickeln möchtet und wir stehen euch mit Rat und Tat beiseite.

