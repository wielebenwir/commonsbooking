# Zeitrahmen

::: info
Auf dieser Seite erfährst du, wie du einen Zeitrahmen im Admin-Bereich anlegst. 

* Wenn du wissen willst, wie du nach dem Veröffentlichen deine Zeiträume auf den Frontend-Seiten mit Hilfe
  von Shortcodes darstellen kannst, [klicke hier](/anleitung/einrichtung-1). 
  
  **Achtung**: Zeitrahmen können nicht direkt über die 
  Backend-Seite “Beitrag anzeigen” aufgerufen werden.
* Falls du nach Anleitungen zum [Einrichten von Artikel, Standort und Zeitrahmen]() oder [Einschränkungen]() suchst, klicke hier.
:::

Dieses Objekt definiert wann ein [Artikel](/dokumentation/artikel) ausgeliehen werden kann.
Außerdem können Zeitrahmen angelegt werden, welche die Nicht-Verfügbarkeit wie z.B. Urlaub oder Arbeitszeiten festlegt.

Ein Buchungszeitraum entsteht durch die Verknüpfung von Artikel und Stationen zu einem Zeitrahmen. 
Ein Buchungszeitraum definiert, zu welchen Zeiten und unter welchen Buchungsbedingungen (z.B. stundenweise
Ausleihe) Artikel gebucht werden können. Buchungszeiträume können aus mehreren Verknüpfungen von Artikel 
und Stationen zu mehr als einem Zeitrahmen bestehen. Beispielsweise ein Zeitrahmen für die Zeiten, in 
denen die Buchungen möglich sind und ein weiterer für den Urlaub der Stationsbetreibenden.

## Eingabefelder Schritt für Schritt

**Zeitrahmen-Typen**: Der Typ eines Zeitrahmens unterscheidet zwischen Zeiträumen in denen ein Artikel buchbar ist und 
  in denen er das nicht ist z.B. Urlaub.
  - **Buchbar**: erlaubt Abholung, Rückgabe und Nutzung. Zugeordnete Artikel sind buchbar im Kalender.
  - **Urlaub**:  verhindert, diesen Zeitraum zur Abholung, Rückgabe oder Nutzung auszuwählen. Dieser Typ kann auch über einen bereits definierten “buchbaren” Zeitraum gelegt werden.
  - **Reparatur** verhindert, diesen Zeitraum zur Abholung, Rückgabe oder Nutzung auszuwählen. Dieser Typ kann auch über einen bereits definierten “buchbaren” Zeitraum gelegt werden.

**Wiederholungstyp**: Bestimmt das Intervall in welchem sich die Zeitrahmen-Konfiguration wiederholt.
  - **täglich**: Der Zeitrahmen ist für jeden Tag gleich, keine Unterscheidung von Wochentagen.
  - **wöchentlich**: Wenn du den Artikel nur an bestimmten Wochentagen anbieten möchtest, wähle bitte diese Option. Dies dürfte der häufigste Fall sein.
    Beispiel: Du möchtest den Gegenstand von Montag – Freitag ausleihbar machen, da an diesen Tagen der Standort geöffnet hat. Am Wochenende soll keine Ausleihe stattfinden.
  - **monatlich**: Bei dieser Option wird ausgehend vom Start-Datum die gewählte Einstellung jeden Monat am gleichen Datum bis zum End-Datum wiederholt
    Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “maximale Buchungstage im Voraus” entsprechend hoch setzt.
  - **jährlich**: Bei dieser Option wird ausgehend vom Start-Datum die gewählte Einstellung jedes Jahr am gleichen Datum bis zum End-Datum wiederholt.
    Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “maximale Buchungstage im Voraus” entsprechend hoch setzt
  - **keine Wiederholung**: Keine Wiederholung des Zeitrahmens, bei Zeitrahmen vom Typ “buchbar” diese 
    Option nur verwenden, wenn du wirklich nur einen Tag buchbar machen möchtest. Möchtest du mehrere Tage 
    als buchbare Tage anbieten (was der häufigste Fall sein wird), dann bitte einen der anderen Wiederholungs-Arten 
    auswählen.

* **Ganzer Tag**: Wähle diese Option, wenn du keinen bestimmten Abholzeitraum (z.B. 09:00 – 18:00 Uhr)
  angeben möchtest, sondern die Abstimmung über die konkrete Abhol- und Rückgabezeit dem Nutzenden im 
  Austausch mit dem Standort überlassen möchtest. Du kannst hierfür dann bei den Standorten Informationen
  zur Abholung- und Rückgabe hinterlegen.

* **Startzeit / Endzeit**: Hier definierst du die Start- und Endzeiten des Buchungszeitrahmens.
  
* **Stundenweise oder Slotweise (z.B. halbtags, 3-stundenweise o.ä.) Buchung**:

  Deaktiviere die Checkbox “Ganzer Tag”
  Wähle im Raster: “stundenweise” oder “ganzer Slot”
  - Bei Auswahl stundenweise werden im Buchungskalender von der Start- bis zur Endzeit jeweils Zeiträume 
    von einer Stunde für Abholung oder Rückgabe angezeigt. Eine feinere Aufteilung unterhalb von Stunden 
    ist derzeit nicht möglich. Bei entsprechender Nachfrage könnten dies evtl. noch Implementiert werden, 
    sodass ggf. auch die Start- und Endzeiten freier gewählt werden könnnen.
  - Slotweise bedeutet, dass Nutzende den Zeitraum von Start- bis Endzeit jeweils nur als kompletten Block
    buchen können (z.B. nur von 09:00 – 12:00 Uhr). Durch diese Einstellung könnt ihr ein gröberes 
    Ausleihraster ermöglichen, falls ihr keine kleinteiligen 1-Stunden-Raster anbieten möchtet.

Bei der stundenweise (oder slotweise Buchung) kann du gerne auch mehrere Zeiträume mit dem gleichen Raster
kombinieren. So kannst du z.B. Folgendes konfigurieren um bei der stundenweisen Buchung die Mittagspause 
(12 Uhr bis 14 Uhr) deiner Station zu berücksichtigen:

  * Zeitraum A (buchbar): 9 Uhr bis 12 Uhr
  * Zeitraum B (buchbar): 14 Uhr bis 18 Uhr

### Buchungs-Codes

Beim Erstellen der Zeiträume von tageweisen Buchungen werden auf Wunsch auch Buchungs-Codes generiert, 
die dann auf die Buchungsbestätigungsseite und in der Bestätigungs-Mail integriert werden.

::: info
Hinweis: Die Buchungscodes werden für jeden Tag vorab generiert und können als Textdatei heruntergeladen werden. So 
könnt ihr die Codes vorab dem Standort zur Verfügung stellen, damit ein Abgleich vor Ort erfolgen kann.
:::

Codes können nur für Zeitrahmen erzeugt werden die das Intervall "ganztägig" konfiguriert haben und 
für welche ein End-Datum definiert wurde.

In einer späteren Version von CommonsBooking werden auch Codes möglich sein, die automatisch während der 
Buchung generiert werden und einen Zufallscode enthalten. Diese werden dann nicht vorab generiert und 
können für alle Arten von Zeitrahmen (stundenweise etc.) genutzt werden.
