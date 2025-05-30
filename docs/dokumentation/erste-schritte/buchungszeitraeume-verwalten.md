#  Zeitrahmen: Buchungszeiträume verwalten

__

Ein Buchungszeitraum entsteht durch die Verknüpfung von Artikel und Station zu
einem Zeitrahmen. Der Zeitrahmen definiert für den Buchungszeitraum ein
Zeitfenster (Start- und End-Datum) und die Buchungsbedingungen (z.B.
stundenweise Ausleihe) in denen Artikel gebucht werden können. Der Zeitrahmen-
Typ legt fest ob wir einen Zeitraum definieren in dem der Artikel entweder
verfügbar ist (buchbar) oder nicht (Urlaub/Reparatur). Ein Zeitrahmen kann
dabei auch mit mehreren Stationen oder mehreren Artikeln verknüpft sein und so
für alle diese Artikel den Buchungszeitraum festlegen.

Eine übliche Vorgehensweise ist es ein Zeitrahmen für die Zeiten, in denen die
Buchungen möglich sind anzulegen und einen weiterer für den Urlaub der
Stationsbetreibenden.

**Beispiel:**

  * **Eine mehrtägige Abwesehenheit durch Urlaub:** Typ=”Feiertage oder Station geschlossen”, Ganzer Tag= [ ✔️ ](https://emojiterra.com/de/kraftiges-hakchen/ "✔️ kräftiges Häkchen") , Zeitrahmenwiederholung=”Keine Wiederholung” 

> Auf dieser Seite erfährst du, wie du einen Zeitrahmen im Backend anlegst.
> Wenn du wissen willst, wie du nach dem Veröffentlichen deine Zeiträume auf
> den Frontend-Seiten mit Hilfe von Shortcodes darstellen kannst,  [ klicke
> hier. ](/docs/erste-schritte/shortcodes/) **Achtung** : Zeitrahmen können
> nicht direkt über die Backend-Seite “Beitrag anzeigen” aufgerufen werden,
> sondern müssen über die o.g. Shortcodes eingebunden werden.

##  Eingabfelder Schritt für Schritt

Um einen Buchungszeitraum anzulegen, gehe im linken Bereich auf "Zeitrahmen"
-> "Zeitrahmen neu erstellen". Fülle das Formular aus:

**Titel**

  * Der Titel dient der internen Bezeichnung des Zeitrahmens und wird z.b. in der Listenansicht angezeigt. 
  * Wenn du einen buchbaren Zeitrahmen anlegst, kannst du diesen mit einem aussagekräftigen Titel bezeichnen (z.B. Ausleihe von xx-yy) 

**Kommentar**

  * Diese sind primär für den internen Gebrauch z.B. zur Dokumentation und für einen besseren Überblick gedacht.   
Wenn du den Nutzenden erlaubst, einen Buchungskommentar zu einer Buchung
abzugeben, wird der Kommentar in diesem Feld gespeichert und bei einer Buchung
dann dort angezeigt.

  * Du kannst die Felder auch gerne leer lassen. 

**Typ:**

  * Wähle hier den Typ “ **Buchbar** ” aus. Du kannst für weitere Fälle (z.B. eintragen eines Urlaubs, Reparatur) auch andere Typen auswählen. Diese verhindern dann z.B. dass zu den definierten Zeiten eine Ausleihe oder Nutzung erfolgen kann. Mehr dazu findest du unter der [ Doku zu den Zeitrahmen-Typen ](/docs/grundlagen/zeitrahmen-konfigurieren/) . 

**Standort:**

  * Wähle den Standort aus, für den Du den buchbaren Zeitraum anlegen möchtest. 

**Artikel** :

  * Wähle den Artikel aus, der an dem Standort verfügbar sein soll. 

**Maximale Buchungsdauer**

  * Trage die Anzahl an maximal buchbaren Tagen ein. 
  * Wichtig: Wenn du mehrere Zeitrahmen für die gleiche Kombination von Artikel und Standort verwendest, dann wird für die Berechnung der maximalen Tage im Buchungskalender der Wert des ersten gültigen buchbaren Zeitrahmens verwendet. 

**Maximale Buchungstage im Voraus**

  * Trage die Anzahl an Tagen, die maximal im Voraus gebucht werden können, z.B. 31 Tage für einen Monat. 
  * Dies hat den Vorteil, dass du nicht immer wieder neue Zeitrahmen anlegen musst, sondern die buchbaren Tage eines Zeitrahmens ohne Enddatum täglich neu freigeschaltet werden. 
  * Standard-Wert sind 365 Tage. 

**Zeitrahmenwiederholung:  
** Wähle aus, welche Arten von Wiederholungen du gerne möchtest.

  * **keine Wiederholung**
    * Bei Zeitrahmen vom Typ “buchbar” diese Option nur verwenden, du wirklich nur einen Tag buchbar machen möchtest. Möchtest du mehrere Tage als buchbare Tage anbieten (was der häufigste Fall sein wird), dann bitte einen der anderen Wiederholungs-Arten auswählen. 
    * keine Wiederholung eignet sich vor allem, wenn du Reparaturen oder Urlaube / Feiertage eintragen willst, die einen bestimmten Zeitraum blockieren. 
  * **täglich**
    * Bitte wählen, wenn sich die Einstellungen jeden Tag wiederholen sollen. 
  * **wöchentlich**
    * Wenn du den Artikel nur an bestimmten Wochentagen anbieten möchtest, wähle bitte diese Option. Dies dürfte der häufigste Fall sein. 
    * Beispiel: Du möchtest den Gegenstand von Montag – Freitag ausleihbar machen, da an diesen Tagen der Standort geöffnet hat. Am Wochenende soll keine Ausleihe stattfinden. 
  * **monatlich**
    * Bei dieser Option wird ausgehend vom Start-Datum die gewählte Einstellung jeden Monat am gleichen Datum bis zum End-Datum wiederholt 
    * Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “maximale Buchungstage im Voraus” entsprechend hoch setzt. 
  * **jährlich**
    *       * Bei dieser Option wird ausgehend vom Start-Datum die gewählte Einstellung jedes Jahr am gleichen Datum bis zum End-Datum wiederholt. 
      * Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “maximale Buchungstage im Voraus” entsprechend hoch setzt 

**Ganzer Tag:**

  * Aktiviere die Checkbox “Ganzer Tag”, wenn du keinen bestimmten Abholzeitraum (z.B. 09:00 – 18:00 Uhr) angeben möchtest, sondern die Abstimmung über die konkrete Abhol- und Rückgabezeit dem Nutzenden im Austausch mit dem Standort überlassen möchtest. Du kannst hierfür dann bei den Standorten Informationen zur Abholung- und Rückgabe hinterlegen. 

**Startzeit / Endzeit:**

  * Hier definierst du die Start- und Endzeiten des Buchungszeitrahmens. 

**Stundenweise oder** **Slotweise** (z.B. halbtags, 3-stundenweise o.ä.)
**Buchung** :

  * Deaktiviere die Checkbox “Ganzer Tag” 
  * Wähle im Raster: “stundenweise” oder “ganzer Slot” 
    * Bei Auswahl stundenweise werden im Buchungskalender von der Start- bis zur Endzeit jeweils Zeiträume von einer Stunde für Abholung oder Rückgabe angezeigt. Eine feinere Aufteilung unterhalb von Stunden ist derzeit nicht möglich. Bei entsprechender Nachfrage könnten dies evtl. noch Implementiert werden, sodass ggf. auch die Start- und Endzeiten freier gewählt werden könnnen. 
    * Slotweise bedeutet, dass Nutzende den Zeitraum von Start- bis Endzeit jeweils nur als kompletten Block buchen können (z.B. nur von 09:00 – 12:00 Uhr). Durch diese Einstellung könnt ihr ein gröberes Ausleihraster ermöglichen, falls ihr keine kleinteiligen 1-Stunden-Raster anbieten möchtet. 

Bei der stundenweise (oder slotweise Buchung) kann du gerne auch mehrere
Zeiträume mit dem gleichen Raster kombinieren. So kannst du z.B. Folgendes
konfigurieren um bei der stundenweisen Buchung die Mittagspause (12 Uhr bis 14
Uhr) deiner Station zu berücksichtigen:

  * Zeitraum A (buchbar): 9 Uhr bis 12 Uhr 
  * Zeitraum B (buchbar): 14 Uhr bis 18 Uhr 

**Buchungs-Codes**

Beim Erstellen der Zeiträume von tageweisen Buchungen werden auf Wunsch auch
Buchungs-Codes generiert, die dann auf die Buchungsbestätigungsseite und in
der Bestätigungs-Mail integriert werden.

Hinweis: Die Buchungscodes werden für jeden Tag vorab generiert und können als
Textdatei heruntergeladen werden. So könnt ihr die Codes vorab dem Standort
zur Verfügung stellen, damit ein Abgleich vor Ort erfolgen kann.

Die Codes können nur für Zeitrahmen die das Intervall "ganztägig" haben und
für die ein End-Datum definiert wurde, erzeugt werden.

In einer späteren Version von CommonsBooking werden auch Codes möglich sein,
die automatisch während der Buchung generiert werden und einen Zufallscode
enthalten. Diese werden dann nicht vorab generiert und können für alle Arten
von Zeitrahmen (stundenweise etc.) genutzt werden.

