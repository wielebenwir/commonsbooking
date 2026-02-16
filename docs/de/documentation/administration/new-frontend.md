#  Neues Frontend (BETA)


Der Shortcode wird mit `[cb_search id="4" ...]` aufgerufen.

`id` ist die Id der Karte, die als Basis für die Einstellungen genutzt
werden soll.

Der Shortcode kann mehrfach auf der Seite aufgerufen werden. Alle Elemente auf
der selben Seite teilen sich einen Zustand. So können beispielsweise Filter
und Artikelliste an unterschiedlichen Stellen auf der Seite angezeigt werden –
z.B. in einer Sidebar und dem Hauptinhaltsbereich – und haben trotzdem
Einfluss aufeinander. Eine Nutzung mehrere Karten-Ids auf der selben Seite ist
derzeit (noch) nicht möglich.

##  Layouts

Standardmäßig zeigt der Shortcode einen Filter mit Karte. Alternativ können
mit dem `layouts` Parameter ein oder mehrere andere Layouts ausgewählt
werden.
Die folgenden Layouts stehen zur Verfügung:

  * **Filter**:
Ein Filter, um Nutzer:innen die Suche und das Filtern nach Artikeln zu
erlauben.

  * **List**:
Eine Listenansicht der Artikel.

  * **AvailabilityCalendar**:
Der Übersichtstabelle mit der Verfügbarkeit aller Artikel im näheren Zeitraum.

  * **MapWithAutoSidebar**:
Eine Karte mit Artikelliste, die bei Bedarf automatisch geöffnet bzw.
geschlossen wird.

  * **LargeMapWithStaticSidebar**:
Eine große Karte mit linksbündiger statischer Seitenleiste, die Filter und
Artikelliste enthält.

Das folgende Kommando würde einen Filter samt Verfügbarkeitstabelle anzeigen:

`[cb_search id="4" layouts="Filter,AvailabilityCalendar"]`

##  Anzeigeoptionen

###  `filter-expanded`

Mit dieser Option wird der Filter ohne Ausklapp- bzw. Dialogmechanismus
angezeigt.
Es gibt ihn in drei Varianten:

  * `filter-expanded` (immer aktiv)
  * `filter-expanded-desktop` (nur in höheren Auflösungen aktiv)
  * `filter-expanded-mobile` (nur in kleinen Auflösungen aktiv)

Nutzung: `[cb_search id="4" layouts="Filter,List" filter-expanded]`

##  Konfiguration (Erweitert)

Zuletzt kann der Shortcode auch direkt ein Konfigurationsobjekt definieren,
das an die [CB-Frontend Bibliothek](https://github.com/wielebenwir/CB-Frontend) durchgereicht wird. Dieser Schritt ist eher für technikaffine
Menschen interessant, für die JSON eine bekannte Abkürzung ist. Auf diesem Weg
lassen sich z.B. komplexe Logiken für die Anzeige der Marker auf der Karte
umsetzen.

Hierfür muss ein JSON-Objekt an die den Shortcode übergeben werden. Das kann
z.B. so aussehen:

```
[cb_search id="4" layouts="Filter,List" filter-expanded]
{
  "map": {
    "markerIcon": {
      "renderers": [
        {
          "type": "category",
          "match": [
            { "categories": [6, 8], "renderers": [{ "type": "image", "url": "/assets/kasten-elektrisch.png" }] },
            { "categories": [6], "renderers": [{ "type": "image", "url": "/assets/elektrisch.png" }] },
            { "categories": [8], "renderers": [{ "type": "image", "url": "/assets/kasten.png" }] },
            { "categories": [12], "renderers": [{ "type": "image", "url": "/assets/3-raeder.png" }] },
            { "categories": [16], "renderers": [{ "type": "color", "color": "teal" }] }
          ]
        },
        { "type": "thumbnail" },
        { "type": "color", "color": "hsl(20 60% 80%)" }
      ]
    }
  }
}
[/cb_search]
```
Bei dieser Konfiguration würden die Marker z.B. zuerst anhand der Kategorien
zugeordnet werden und als Rückfalloption entweder das Thumbnail oder
schlussendlich eine Farbe genutzt werden. Details der Konfiguration sind in
der [ Dokumentation der CB-Frontend Bibliothek
](https://github.com/wielebenwir/CB-Frontend/blob/main/documentation/configuration.md)
ausgeführt.

