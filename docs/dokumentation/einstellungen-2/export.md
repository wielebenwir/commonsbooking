#  Export von Zeitrahmen und Buchungen

__

Über die Einstellungen -> Export könnt ihr Zeitrahmen und Buchungen im CSV Format exportieren um diese zB. zu archivieren oder mithilfe von statistischen Programmen auszuwerten. 

# Exportieren

Dabei wählt ihr zunächst, welche Daten ihr exportieren möchtet (Zeitrahmen, Buchungen etc.)

Standardmäßig werden die grundlegenden Felder eines Zeitrahmens exportiert (siehe unten).

Zusätzlich könnt ihr zu Standorten, Artikeln und Benutzenden beliebige weitere Felder exportieren. Da wir nicht wissen, ob wir ggf. weitere
benutzerdefinierte Felder wie Telefonnummern oder andere Felder erfasst, haben wir den Export im Standard hier derzeit keine weiteren Felder vorbelegt. Dazu muss nur der Bezeichner des entsprechenden Meta Feldes angegeben werden.

# Auswertungstools

* [cb-statistics von inSPEYERed](https://inspeyered.github.io/cb-statistics/)
* [R-Script für die jährliche Auslastung](https://gist.github.com/hansmorb/b4de840ed98f5b26d46ee51a1907b8b7)

# Inhalt des Exports

Die resultierende CSV Datei ist durch Semikolons getrennt.
Standardmäßig sind in einem Datenexport enthalten:

  * **Zeitrahmen bzw. Buchungsdetails**
    * ID des Zeitrahmens / der Buchung: `ID`
    * Ersteller der Buchung / des Zeitrahmens: `post_author`
    * Erstelldatum: `post_date`
    * Erstelldatum (GMT): `post_date_gmt`
    * Post-Inhalt (normalerweise leer): `post_content`
    * Post-Auszug (normalerweise leer): `post_excerpt`
    * Titel des Zeitrahmens / der Buchung: `post_title`
    * Status des Zeitrahmens / der Buchung: `post_status`
    * Eindeutig identifizierbarer Name (Slug): `post_name`
    * Typ (z.B. Buchung, Zeitrahmen, Einschränkung): `type`
    * * Achtung: dieses Feld ist lokalisiert, d.h. in einer englischen Installation steht dort "booking", in einer deutschen "Buchung"
    * Wiederholung des Zeitrahmens: `timeframe-repetition`
    * Stündliche Buchung oder Buchung des gesamten Slots: `grid`
    * Maximal am Stück buchbare Tage für diesen Zeitrahmen: `timeframe-max-days`
    * Ganzer Tag gebucht / buchbar: `full-day`
    * Beginn der Buchung / des Zeitrahmens im ISO 8601 Format: `repetition-start`
    * Ende der Buchung / des Zeitrahmens im ISO 8601 Format: `repetition-end`
    * Startzeit der Buchung / des Zeitrahmens: `start-time`
    * Endzeit der Buchung / des Zeitrahmens: `end-time`
    * Abholzeitpunkt wie er den Benutzenden angezeigt wird: `pickup`
    * Rückgabezeitpunkt wie er den Benutzenden angezeigt wird: `return`
    * Buchungscode: `booking-code`
    * Buchungskommentar: `comment`
  * **Standortdetails**
    * Name des Standorts: `location-post_title`
  * **Artikel**
    * Name des Artikels: `item-post_title`
  * **Benutzende**
    * Vorname: `user-firstname`
    * Nachname: `user-lastname`
    * Login: `user-login`

