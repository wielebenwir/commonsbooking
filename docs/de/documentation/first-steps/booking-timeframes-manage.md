#  Zeitrahmen: Buchungszeiträume verwalten

__

Ein Artikel wird buchbar durch eine Verknüpfung von Artikel und Station mithilfe eines Zeitrahmens.
Der Zeitrahmen definiert für den Buchungszeitraum ein
Zeitfenster (Start- und End-Datum) und die Buchungsbedingungen (z.B.
stundenweise Ausleihe) in denen Artikel gebucht werden können. Der Zeitrahmen-
Typ legt fest, ob wir einen Zeitraum definieren, in dem der Artikel entweder
verfügbar ist (buchbar) oder nicht (Urlaub/Reparatur).

Ein buchbarer Zeitrahmen kann immer nur für eine Kombination von Artikel und Station angelegt werden,
blockierende Zeitrahmen (z.B. für Urlaub oder Reparatur) können aber auch für mehrere Artikel und Stationen gleichzeitig gelten.

Eine übliche Vorgehensweise ist es ein Zeitrahmen für die Zeiten, in denen die
Buchungen möglich sind, anzulegen und einen weiteren für den Urlaub der
Stationsbetreibenden.

::: info Los geht's!
Auf dieser Seite erfährst du, wie du einen Zeitrahmen im Backend anlegst um einen Artikel buchbar zu machen.
Wenn du wissen willst, wie du nach dem Veröffentlichen deiner Zeiträume auf
das Buchungsangebot auf den Frontend-Seiten mithilfe von Shortcodes darstellen kannst,  [klicke hier.](/dokumentation/einstellungen/shortcodes)

**Achtung** : Zeitrahmen können nicht direkt über den Backend-Link “Beitrag anzeigen” im Frontend aufgerufen werden,
sondern müssen über die o.g. Shortcodes eingebunden werden.
:::

## Zeitrahmenerstellung Schritt für Schritt

Um einen Buchungszeitraum anzulegen, gehe im CommonsBooking Menü auf "Zeitrahmen"
-> "Hinzufügen eines neuen Zeitrahmens". Fülle das Formular aus:

### **Titel**

  * Der Titel dient der internen Bezeichnung des Zeitrahmens und wird z.b. in der Listenansicht im Backend angezeigt. Es ist nicht für die Nutzenden sichtbar.
  * Wenn du einen buchbaren Zeitrahmen anlegst, kannst du diesen mit einem aussagekräftigen Titel bezeichnen (z.B. Ausleihe von xx-yy)

### **Kommentar**

  * Diese sind primär für den internen Gebrauch z.B. zur Dokumentation.
Wenn du den Nutzenden erlaubst, einen Buchungskommentar zu einer Buchung
abzugeben, wird der Kommentar in diesem Feld gespeichert und bei einer Buchung
dann dort angezeigt.
  * Du kannst dieses Feld auch leer lassen.

### **Typ:**

  * Wähle hier den Typ “ **Buchbar** ” aus. Du kannst für weitere Fälle (z.B. eintragen eines Urlaubs, Reparatur) auch andere Typen auswählen. Diese verhindern dann z.B. dass zu den definierten Zeiten eine Ausleihe oder Nutzung erfolgen kann. Mehr dazu findest du unter der [ Doku zu den Zeitrahmen-Typen ](/dokumentation/grundlagen/zeitrahmen-konfigurieren) .

### **Standort:**

  * Wähle den Standort aus, für den Du den buchbaren Zeitraum anlegen möchtest.

### **Artikel** :

  * Wähle den Artikel aus, der an dem Standort verfügbar sein soll.

### **Buchungen konfigurieren:**
  * **Maximal**: Wie viele Tage in am Stück buchbar sind.  (Achtung: Bei mehreren Zeitrahmen für die gleiche Kombination von Artikel und Standort wird der Wert des ersten gültigen Zeitrahmens verwendet.)
  * **Vorlauf:**: Wie viele Tage Vorlauf der Station zwischen Buchung und Abholung liegen soll. Wenn z.B. 2 Tage eingestellt sind, dann kann der Artikel immer erst für in 2 Tagen gebucht werden. Leer lassen für keine Vorlaufzeit.
  * **Kalender zeigt als buchbar:**: Wie viele Tage im Voraus der Kalender mögliche Buchungen anzeigt. Wenn z.B. 7 Tage eingestellt sind, dann können die Nutzenden im Kalender nur Buchungen für die nächste Woche vornehmen.
  * **Erlaubt für:**: Für welche [Nutzendenrollen](https://wordpress.org/documentation/article/roles-and-capabilities/) die Buchung erlaubt ist. Wenn das Feld leer ist, dürfen alle registrierten Nutzenden den Artikel buchen. Alternativ ist es möglich in den Einstellungen des [Artikels](/dokumentation/erste-schritte/artikel-anlegen) einen Passwortschutz für die Buchung zu konfigurieren.

### **Konfigurieren des Zeitrahmens:**
  * **Ganzer Tag:** Wenn aktiviert, belegt der Zeitrahmen den ganzen Tag. Wenn diese Option deaktiviert ist, muss der Zeitraum eine Start- und Endzeit haben.
  * **Raster:** Bei ganztägigen Zeitrahmen irrelevant. Wenn "Gesamter Slot" ausgewählt ist, ist der Artikel immer von der angegebenen Startzeit bis zur Endzeit buchbar. Wenn "Stundenweise" ausgewählt ist, ist jede Stunde zwischen Start- und Endzeit einzeln buchbar.
  * **Startzeit / Endzeit:** Bei ganztägigen Zeitrahmen irrelevant. Wann jeden Tag das Buchungsfenster beginnt und endet.

### **Zeitrahmenwiederholung:**

_Wähle aus, wie sich der buchbaren Zeitrahmen innerhalb des angegebenen Start-und Enddatums wiederholen soll._
* **Keine Wiederholung**
  * Dieser Zeitrahmentyp war ursprünglich dafür gedacht Artikel nur für einen Tag buchbar zu machen. Bitte nutze dafür jetzt die Option "Manuelle Wiederholung".
* **Manuelle Wiederholung**
  * Erlaubt es, einzelne Tage auszuwählen für die der Zeitrahmen gelten soll.
* **Täglich**
  * Bitte wählen, wenn sich die Einstellungen jeden Tag wiederholen sollen. Dann ist der Zeitrahmen vom Start- bis zum End-Datum für jeden Tag buchbar.
* **Wöchentlich**
  * Bei dieser Option können bestimmte Wochentage ausgewählt werden, an denen der Artikel verfügbar ist.
  * Beispiel: Du möchtest den Gegenstand von Montag – Freitag ausleihbar machen, da an diesen Tagen der Standort geöffnet hat. Am Wochenende soll keine Ausleihe stattfinden.
* **Monatlich**
  * Bei dieser Option wird, ausgehend vom Start-Datum, die gewählte Einstellung jeden Monat am gleichen Datum bis zum End-Datum wiederholt.
  * Wenn also ein Zeitrahmen vom 15.02.2025 bis 15.05.2025 angelegt wird, dann ist der Artikel für 3 Monate an jedem 15. des Monats buchbar.
  * Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “Kalender zeigt als buchbar” entsprechend hoch setzt.
* **Jährlich**
  * Bei dieser Option wird ausgehend vom Start-Datum die gewählte Einstellung jedes Jahr am gleichen Datum bis zum End-Datum wiederholt.
  * Wenn also ein Zeitrahmen vom 15.02.2025 bis 15.02.2028 angelegt wird, dann ist der Artikel für 3 Jahre an jedem 15.02. des Jahres buchbar.
  * Beachte, dass du bei längeren Zeitdauern die Tage in der Einstellung “Kalender zeigt als buchbar” entsprechend hoch setzt

### **Konfigurieren der Wiederholung:**
* **Startdatum / Enddatum:**
  * Hier legst du den Start- und Endzeitpunkt des Zeitrahmens fest. Der Endzeitpunkt ist optional, wenn du einen unbefristeten Zeitraum anlegen möchtest.
* **Wochentage:**
  * Nur bei wöchentlicher Wiederholung: Wähle die Wochentage aus, an denen der Artikel buchbar sein soll.
  * Wenn gewünscht kann das auch die Tage definieren, an denen der Artikel nur abgeholt / zurückgegeben werden kann.
    Ob ein Wochentag "überbuchbar" ist wird durch die [Standorteinstellungen](/dokumentation/erste-schritte/stationen-anlegen) bestimmt.
* **Manuell ausgewählte Daten:**
  * Nur bei manueller Wiederholung: Trage einzelne Tage im Format JJJJ-MM-TT ein, um den Artikel an diesem Tag buchbar zu machen. Mehrere Tage werden durch Kommas getrennt. Du kannst auch auf das Textfeld hinter "Ausgewählte Daten" klicken, dort öffnet sich ein Kalender. Jeder Tag, der in dem Kalender angeklickt wird, wird dann der Liste hinzugefügt.

## **Buchungs-Codes**

Beim Erstellen der Zeiträume können auf Wunsch auch Buchungs-Codes generiert werden, die dann auf die Buchungsbestätigungsseite und in
der Bestätigungs-Mail integriert werden. Buchungscodes können wie Passwörter genutzt werden,
damit die Station eine Verifizierung der Buchung vornehmen kann. Dann muss vom Ausleihenden der richtige, tagesaktuelle Code genannt werden um
nachzuweisen, dass sie die Buchung auch wirklich vorgenommen haben.

:::tip Tipp
Die Buchungscodes werden für jeden Tag vorab generiert und können als
Textdatei heruntergeladen werden. So könnt ihr die Codes vorab dem Standort
zur Verfügung stellen, damit ein Abgleich vor Ort erfolgen kann.
:::
Die Codes können nur für Zeitrahmen erstellt werden, die die Option "Ganzer Tag" aktiviert haben.

* **Buchungscodes generieren:** Wenn diese Option aktiviert ist, werden nach dem Speichern des Zeitrahmens die Buchungscodes generiert.
* **Zeige Buchungs-Codes:** Wenn aktiviert, wird der Code den Nutzenden auch während des Buchungsprozesses angezeigt.

### **Buchungscodes per E-Mail versenden:**

:::warning ACHTUNG!
Der Zeitrahmen muss erst gespeichert werden, bevor Buchungscodes versendet werden können.
:::
Diese Funktion erlaubt es die generierten Buchungscodes entweder manuell oder automatisiert per E-Mail an die Stationen zu versenden.
Mit den Links können schnell die Buchungscodes für den aktuellen oder nächsten Monat an die Stationen gesendet werden.

**Automatischer Versand:**
Für den automatischen Versand muss ein Startdatum konfiguriert werden. Ab diesem Datum werden die zukünftigen Codes immer am gleichen Tag des Monats versendet.
Die Anzahl an Monaten legt fest, für wie viele Monate im Voraus die Codes gebündelt versendet werden. Wenn z.B. 6 Monate eingestellt sind, werden jedes halbe Jahr die Codes für die nächsten 6 Monate versendet.

**Buchungscodes herunterladen:**
Lädt die bisher generierten Buchungscodes als Textdatei herunter damit diese ausgedruckt oder versendet werden können. Bei Zeitrahmen mit Enddatum sind das alle Codes vom Start- bis zum Enddatum. Bei Zeitrahmen ohne Enddatum sind das alle Codes ab dem Startdatum bis zu einem Jahr in der Zukunft.

**Liste der Buchungscodes:**
Diese Tabelle zeigt die aktuellen Buchungscodes für den Zeitrahmen an. Es werden standardmäßig nicht alle Codes in dieser Ansicht angezeigt. Wie viele Codes hier angezeigt werden kann unter "Einstellungen" -> "CommonsBooking" -> "Buchungscodes" konfiguriert werden.

**An wen werden die Codes gesendet?**
Die Codes werden an die E-Mail-Adressen versendet, die in dem Feld "Standort E-Mail" des jeweiligen Standorts hinterlegt sind. Dort können auch mehrere E-Mail Adressen hinterlegt werden.

## Beispielszenarien

### **Ganztägige Buchung mit Abwesenheit (Urlaub)**
1. **Ein Zeitraum, in dem ein Artikel buchbar ist:**
    * Typ=”Buchbar”,
    * Ganzer Tag= X
    * Zeitrahmenwiederholung = ”Täglich”
    * Startdatum: 01.01.2023
    * Enddatum: Kein Enddatum (d.h. der Zeitraum ist unbefristet buchbar)
2. **Eine mehrtägige Abwesenheit durch Urlaub: (weiterer Zeitrahmen)**
    * Typ=”Feiertage oder Station geschlossen”
    * Ganzer Tag= X
    * Zeitrahmenwiederholung=”Täglich”
    * Startdatum: 15.07.2023
    * Enddatum: 22.07.2023

### **Stundenweise oder** **Slotweise** (z.B. halbtags, 3-stundenweise o.ä.)
  **Buchung** :
  * Deaktiviere die Checkbox “Ganzer Tag”
  * Wähle im Raster: “stundenweise” oder “ganzer Slot”
    * Bei Auswahl stundenweise werden im Buchungskalender von der Start- bis zur Endzeit jeweils Zeiträume von einer Stunde für Abholung oder Rückgabe angezeigt.
    * Slotweise bedeutet, dass Nutzende den Zeitraum von Start- bis Endzeit jeweils nur als kompletten Block buchen können (z.B. nur von 09:00 – 12:00 Uhr). Durch diese Einstellung könnt ihr ein gröberes Ausleihraster ermöglichen, falls ihr keine kleinteiligen 1-Stunden-Raster anbieten möchtet.

### **Kombination mehrerer Zeitrahmen:**

Bei der stundenweise (oder slotweise Buchung) kannst du gerne auch mehrere
Zeiträume mit dem gleichen Raster kombinieren. So kannst du z.B. Folgendes
konfigurieren um bei der stundenweisen Buchung die Mittagspause (12 Uhr bis 14
Uhr) deiner Station zu berücksichtigen:

  * Zeitraum A (buchbar): 9 Uhr bis 12 Uhr
  * Zeitraum B (buchbar): 14 Uhr bis 18 Uhr
