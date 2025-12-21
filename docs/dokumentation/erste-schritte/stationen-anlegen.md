#  Station anlegen

__

## Stationsbeschreibung

Nutze den großen Texteditor oben auf der Stationsseite nur für die
Detailbeschreibung deiner Station (Art der Station, Projekthintergründe,
Verlinkung zur Webseite usw.). Diese Beschreibung wird auf der
Stationsdetailseite angezeigt.

## Stations-Bild

Auf Wunsch kann bei der Station im Frontend ein Bild angezeigt werden. Das
Beitragsbild kann rechts unter dem Button “publizieren” eingestellt werden.

## Adressdaten

Ergänze die Adressdaten mit Straße, Hausnummer, Postleitzahl und Ort in den
dafür vorgesehen Feldern.

## GPS-Koordinaten abrufen / aktualisieren

Normalerweise werden die GPS-Koordinaten beim Speichern des Beitrags automatisch
aus der Adresse abgerufen. Falls das nicht geklappt hat, kannst du die Koordinaten
nach Speichern des Beitrags mit diesem Knopf abrufen lassen. Du kannst alternativ
auch die Koordinaten von Hand eintragen.

## Positionskarte in der Artikelansicht anzeigen

Wenn du diese Option aktivierst, wird auf der Artikeldetailseite, dort wo sich auch der
Buchungskalender befindet, eine Karte mit der Position der Station angezeigt.

![](/img/item-locationmap.png){width="400"}
_Eine Artikeldetailseite mit aktivierter Positionskarte, Adresse und Stationsbild._

## Allgemeine Standortinformationen

In den Standortinformationen definierst du, welche Informationen zur Abholung
usw. wann angezeigt werden:

  * **Standort-E-Mail**: E-Mail-Adressen, die wichtige E-Mails über Aktivitäten an dem Standort erhalten sollen (Buchungen, Buchungseinschränkungen, Buchungscodes). Mehrere Adressen können mit einem Komma getrennt werden.
  * **Kopie der Buchungen/Stornierungen per E-Mail an den Standort senden**: Wenn diese Option aktiviert ist, erhalten
  die oben genannten E-Mail-Adressen eine Kopie der Buchungs- und Stornierungs-E-Mails, die an die Nutzenden gesendet werden.
  * **Abhol-Hinweise** (Öffnungszeiten, Abholprozess usw.) werden auf der Artikelseite und im gesamtem Buchungsprozess angezeigt. Die hier angegebenen Informationen sind öffentlich und auch ohne Buchung oder Registrierung sichtbar.
  * **Kontaktinformationen** (E-Mail und Telefonnummer) werden erst auf der Bestätigungsseite nach der Buchung angezeigt. Falls Buchende manche Informationen erst nach der Buchung sehen sollen, kannst du sie hier eintragen.
  * **Standort-Administrator(en)**: Wähle einen oder mehrere Benutzer aus, um ihnen die Möglichkeit zu geben, diesen bestimmten Standort zu bearbeiten und zu verwalten. Mehr dazu: [Zugriffsrechte vergeben](/dokumentation/grundlagen/rechte-des-commonsbooking-manager)

## Einstellungen für die Überbuchung {#overbooking}

Wenn du einen Buchungszeitraum anlegst (siehe [Buchungszeiträume anlegen](/dokumentation/erste-schritte/buchungszeitraeume-verwalten)) kannst du auswählen,
ob Buchungen nur an bestimmten Wochentagen möglich sind. So kannst du z.B.
definieren, dass Buchungen nur Montags – Freitags möglich sind. Dies bedeutet
dann, dass eine Abholung und Rückgabe nur an diesen Tagen erfolgt. Um
Nutzenden aber eine Buchung auch über Zeiträume hinweg zu erlauben, an denen
keine Abholung oder Rückgabe möglich ist, kannst du das Überbuchen von
geblockten Tagen erlauben.

**Globale Standorteinstellungen nutzen**

Standardmäßig werden die Einstellungen für die Überbuchung in den CommonsBooking Einstellungen
unter dem Reiter Allgemein definiert. Wenn dieser Haken nicht gesetzt ist, verwendet der Standort
die unten definierten Einstellungen.

**Überbuchen von geblockten Tagen erlauben**

Aktiviert oder deaktiviert die Überbuchung von geblockten Tagen.


**Gesperrte Tage bei Überbuchung zählen**

Unter Umständen kann es mit der Überbuchung sinnvoll sein den Nutzenden zu
ermöglichen über einen längeren Zeitraum, als den Buchungszeitraum zu buchen.
So ist z.B. eine Abholung von Freitag bis Montag bei 3 maximalen Buchungstagen
nur möglich, wenn von diesen überbuchten Tagen nicht alle gezählt werden. Im
folgenden siehst du einige Screenshots mit Einstellungen für die Überbuchung
und deren Auswirkungen auf den Kalender. Der dazugehörige Zeitrahmen hat dabei
als maximale Buchungsdauer immer 3 Tage eingestellt.

![](/img/overbooking-nocount.png){data-zoomable}
_Überbuchung aktiviert ohne Zählung der Tage_

![](/img/overbooking-countall.png){data-zoomable}

_Überbuchung aktiviert mit Zählung jedes einzelnen Tages (das Wochenende ist
nicht überbuchbar, weil das eine Buchung von mehr als drei Tagen bedeuten
würde_ )

![](/img/overbooking-countone.png){data-zoomable}
_Nur der erste Tag wird bei der Überbuchung gezählt: Das Wochenende ist somit
überbuchbar aber es kann nicht bis Dienstag gebucht werden._
