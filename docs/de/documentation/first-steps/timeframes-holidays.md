#  Zeitrahmen: Feiertage definieren

__


Zeitrahmen können nicht nur Artikel an Standorten verfügbar machen, sondern
auch bestimmte Schließtage der Standorte bestimmen. CommonsBooking hat seit
Version 2.9 die Möglichkeit automatisch gesetzliche Feiertage auf eine oder
mehrere Standorte anzuwenden.

Erstelle dazu einen Zeitrahmen mit dem Typ "Feiertage oder Station
geschlossen". Anschließend kannst du bei Standort auswählen, für welche Standorte diese
Regel gelten soll. Entweder für eine "Manuelle Auswahl", für alle Standorte
die einer "Standortkategorie" angehören oder für alle Standorte.

Das Gleiche kannst du jetzt bei den Artikeln machen. Bitte beachte, dass wenn
du "Alle" auswählst, damit Alle Artikel an den oben definierten Standorten
gemeint sind und nicht alle Artikel in der gesamten Installation. Um alle
Artikel in der gesamten Installation auszuwählen musst du sowohl Standort als
auch Artikel auf "Alle" stellen.

# Regelmäßiges Blockieren eines Artikels

Um einen Artikel regelmäßig aus dem Verleih zu nehmen, kann der Zeitrahmentyp `Geblockt (nicht überbuchbar)` genutzt werden.
Wenn ein Artikel z.B. eine Woche lang jeden Tag von 08:00 - 10:00 Uhr blockiert werden soll, müsste dann der Anfang und das Ende der Woche
als Start- und Enddatum eingetragen werden und die Wiederholung auf `Täglich` gestellt werden. Wenn jedoch der Artikel für eine ganze Woche durchgehend blockiert
werden soll, muss die Wiederholung auf `Keine Wiederholung` gesetzt werden.

:::tip Tipp
Für Wartungszeiträume lohnt es sich eher eine [Buchungseinschränkung](/documentation/first-steps/manage-booking-restrictions) zu erstellen, da diese auch eine Benachrichtigung an die Betroffenen Nutzenden mit Buchung in dem entsprechenden Zeitraum ermöglicht.
:::

#  Feiertage automatisch importieren

Um einzelne, nicht zusammenhängende Tage für einen Zeitrahmen zu definieren
musst du die Zeitrahmenwiederholung auf "Manuelle Wiederholung" stellen. Dann
kannst du über das oben erscheinende Feld die Feiertage für dein Bundesland
und dein Jahr importieren. Du kannst auch manuell über die Datumsauswahl
weitere Tage einfügen.

# Überbuchbarkeit

Ein Zeitrahmen vom Typ "Urlaub" ist überbuchbar, wenn die Überbuchbarkeit in
den Standorteinstellungen konfiguriert ist ( [mehr dazu](/documentation/first-steps/create-locations#overbooking) ).
Das bedeutet, dass ein Artikel zwar über den Zeitraum ausgeliehen werden kann,
aber währenddessen weder ausgeliehen noch zurückgegeben werden kann.

