#  Buchungsregeln einrichten (Ab 2.9)

__

Mithilfe von Buchungsregeln kannst du die Nutzung von Artikeln von Nutzenden
projektweit einschränken. Wenn du also zum Beispiel verhindern willst, dass
ein Nutzender für einen Tag mehrere Artikel gleichzeitig bucht oder übermäßige
Nutzung einschränken willst, kannst du das mit dieser Funktion erreichen.

Die meisten Regeln gelten projektweit. Wenn du also z.B. die Regel "Maximal
gebuchte Tage pro Woche" verwendest und die Regel auf Alle Artikel anwendest,
dann können Nutzende projektweit nur x Tage pro Woche buchen. Aus diesem Grund
kannst du die Regel auch nur für Artikel bestimmter Kategorien verwenden.
Damit könntest du zum Beispiel definieren, dass eine bestimmte Kategorie
Artikel 2 Tage pro Woche und eine andere 3 Tage pro Woche gebucht werden darf.

## Unterschied zur Einstellung "Maximale Buchungsdauer"

In den [Einstellungen für die Zeitrahmen](/dokumentation/erste-schritte/buchungszeitraeume-verwalten) ist es
möglich die maximale Buchungsdauer für eine Buchung eines Artikels festzulegen. Das definiert nur
das Maximum für eine einzelne Buchung. Nutzende könnten also mehrere Buchungen anlegen, die dann über den Maximalwert hinausgehen.
Die Buchungsregeln hingegen definieren, wie viele Tage ein Nutzer insgesamt pro Woche, Monat oder in einem bestimmten Zeitraum buchen darf.
Damit kannst du die Nutzung von Artikeln durch Nutzende einschränken und verhindern, dass sie übermäßig viele Buchungen anlegen.
Darüber hinaus lassen sich Buchungsregeln auch auf mehrere Artikel anwenden. Falls du also mehrere Artikel hast, können Nutzende insgesamt
nur so viele Tage die Artikel buchen, wie du es in der Buchungsregel definiert hast.

##  Buchungsregeln definieren

Die Einstellungen für die Buchungsregeln findest du unter
"Einstellungen"->"CommonsBooking" unter dem Reiter "Einschränkungen" ganz
unten. Dort kannst du Regeln hinzufügen oder löschen.

##  Regelübersicht

###  Gleichzeitige Buchungen verbieten

Verhindert, dass Nutzende am selben Tag mehr als einen Artikel buchen können.
Wenn eine Artikelkategorie festgelegt ist, gilt diese Regel nur für Artikel,
die sich die gleiche Kategorie teilen.

###  Kettenbuchungen verhindern

Verhindert, dass Nutzende das maximale Buchungslimit (Standard 3 Tage)
umgehen, indem sie einfach zwei Buchungen für den gleichen Artikel
hintereinander tätigen. Wenn diese Regel aktiviert ist, müssen Nutzende
mindestens einen Tag zwischen den Buchungen lassen.

###  Maximal gebuchte Tage pro Woche

Legt fest, wie viele Tage ein Nutzer maximal pro Woche buchen darf (entweder
für alle Artikel oder für Artikel von bestimmten Kategorien). Ab dem Tag, der
als Resettag gesetzt ist, beginnt dann nach der Definition die neue Woche.
Wenn also z.B. der Montag als Resettag gesetzt ist und nur ein Tag pro Woche
gebucht werden darf, dürfte die Person sowohl am Sonntag als auch am Montag
buchen.

###  Maximal gebuchte Tage pro Monat

Legt fest, wie viele Tage ein Nutzer maximal pro Monat buchen darf (entweder
für alle Artikel oder für Artikel von bestimmten Kategorien). Ab dem Tag, der
als Resettag gesetzt ist, beginnt dann nach der Definition der neue Monat.
Wenn also z.B. der 15. als Resettag gesetzt ist und nur ein Tag pro Monat
gebucht werden darf, dürfte die Person sowohl am 14. als auch am 15. buchen.

###  Maximal gebuchte Tage in Zeitraum

Legt fest, wie viele Tage ein Nutzer über einen bestimmten Zeitraum von Tagen
hinweg einen Artikel buchen darf. Die Zählung des Zeitraums beginnt immer von
der Mitte aus. Wenn also 30 Tage gesetzt sind, dann werden die 15 Tage vor und
nach der gegebenen Buchung als Zeitraum berücksichtigt.

##  Stornierte Buchungen auf Quote anrechnen

Wenn diese Option aktiviert ist, dann zählen stornierte Buchungen auch mit in
die maximal buchbaren Tage für die Buchungsregeln. Dabei gilt:

  * Buchung vor Beginn des Buchungszeitraums storniert: Zählt **nicht** mit in die Quote
  * Buchung während des Buchungszeitraums storniert: Buchung zählt die Tage von Beginn des Buchungszeitraums bis zu der Stornierung. Wenn also eine Buchung von Montag bis Mittwoch geht, und diese am Dienstag storniert wird dann zählt diese für 2 Tage und nicht für 3.

###  Bestimmte Rollen grundsätzlich von allen Buchungsregeln ausnehmen

Mit einem kleinen Codeschnipsel ([mehr dazu hier](/dokumentation/einstellungen/hooks-und-filter)) kannst du eine Rolle
definieren, die grundsätzlich nicht von Buchungsregeln betroffen ist. Dafür musst du die Rolle nicht manuell bei jeder
Regel hinzufügen.


```php
add_filter('commonsbooking_privileged_roles', function($privileged_roles) {
    $privileged_roles[] = 'editor';
    return $privileged_roles;
});
```

Dieser Schnipsel fügt zum Beispiel die Rolle "Redakteur" mit dem slug editor
als "privilegierte" Rolle hinzu.

Darüber hinaus sind auch alle Administratoren und CB-Manager, denen der
betroffene Artikel / Standort zugewiesen ist immer ausgenommen. [Mehr zu manueller Vergabe von Berechtigungen.](/dokumentation/grundlagen/rechte-des-commonsbooking-manager)

