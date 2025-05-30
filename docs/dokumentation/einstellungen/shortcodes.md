#  Shortcodes für Frontend-Darstellung

__

Um die CommonsBooking-Inhalte (z.B. die automatisch generierten Artikellisten)
auf der Website anzuzeigen werden Shortcodes verwendet. Shortcodes können in
jede WordPress Seite eingefügt werden. [ Offizielle WordPress Dokumentation
](https://en.support.wordpress.com/shortcodes/) .

Die Ergebnis-Liste der Shortcodes kann dabei über einige Parameter beeinflusst
werden. Beispielsweise:

  * ` orderby ` : Bestimmt das Attribut nachdem sortiert werden soll z.B. ` orderby=post_title ` für eine Sortierung nach dem Namen eines Posts.
  * ` order ` : Bestimmt die Sortierreihenfolge. Aufsteigend ` ASC ` und absteigend ` DESC ` .

Das gilt für die folgenden Shortcodes, welche über das CommonsBooking Plugin
verfügbar sind:

##  Artikel-Liste

Zeigt eine Liste aller veröffentlichen Artikel an mit den Stationen, an denen
sie sich befinden.

  * Shortcode: ` [cb_items] `
  * Argumente: ` `
    * ` Kategorie-Filter: category_slug `
    * Nur einzelnen Artikel anzeigen: [cb_items p=PostID]
    * Nur Artikel von einer Station anzeigen: [cb_items location-id=PostIDVonDemStandort]

[ ![](/img/292707e882d420dc3e0c637577caccff.png) ](/wp-content/uploads/2021/01/screenshot-1.png) [cb_items]: Liste von Artikeln

**Nur eine bestimmte Kategorie anzeigen?**

Wenn ihr Artikel Kategorien zugeordnet habt, könnt ihr über einen Parameter
nur Artikel einer bestimmten Kategorie anzeigen. Dazu sucht ihr zunächst die
Titelform / Slug der Kategorie über das Kategorie-Menü aus und setzt diese
dann folgendermaßen ein:

  * ` [cb_items category_slug= _titelform_ ] `

* * *

##  Einzelner Artikel

Zeigt einen einzelnen Artikel in der Listenansicht (s.o.) an.

* Shortcode: ` [cb_items p= _postID_ ] `

* * *

##  Karte mit Filter-Möglichkeit

Zeigt eine Karte aller veröffentlichen Artikel an. Eine Karte muss dafür
zunächst unter "CommonsBooking -> Karten" eingerichtet werden. [ Mehr zur
Einrichtung und Konfiguration von Karten ](/docs/einstellungen/karte-
einbinden/) .

  * Shortcode: ` [cb_map] `
  * Argumente ( **erforderlich!** ): ` id `

[ ![](/img/717949d584aa5b1d4aef255e90bc4d31.png) ](/wp-
content/uploads/2021/05/screenshot-5.png) [cb_map]: Karte mit Filter

* * *

##  Karte mit Artikelliste (BETA)

Erst ab Version 2.9

Bisher war jeder Shortcode nur unabhängig voneinander verwendbar, dh. ein
Filter der auf der Karte angewendet wurde hatte keine Auswirkungen auf die
danebenstehende Artikelliste. Dafür gibt es jetzt den neuen Shortcode

  * Shortcode: ` [cb_search] `
  * Argumente ( **erforderlich!** : ` id `

![](/img/2696a4fc55baa66953d159e08e26f871.png)

[ Weitere Argumente und ausführliche Dokumentation
](/docs/einstellungen/neues-frontend-beta/)

* * *

##  Artikel-Tabelle mit Verfügbarkeit

Zeigt eine Tabelle aller veröffentlichen Artikel an mit Stationen, an denen
sie sich befinden und der aktuellen Verfügbarkeit.

  * Shortcode: ` [cb_items_table] `
  * Die Anzahl der anzuzeigende Tage ist standardmäßig auf 31 gesetzt. Über das Attribut days kann dieser Wert angepasst werden. Beispiel, um nur 10 Tage anzuzeigen: ` [cb_items_table days=10] `
  * Zusätzlich kann oberhalb der Tabelle eine kurze Beschreibung mit dem Attribut desc eingefügt werden. ` [cb_items_table desc=Lastenräder] `
  * Die Liste der Einträge kann mit folgenden Attributen weiter gefiltert werden
    * Filter nach Artikel-Kategorien: itemcat (Beispiel: ` [cb_items_table itemcat=itemcategoryslug] `
    * Filter nach Standort-Kategorien: locationcat (Beispiel: ` [cb_items_table locationcat=locationcategoryslug] `

[ ![](/img/f9d1d3c10d913f60eed3247c9257d48e.png) ](/wp-
content/uploads/2021/05/screenshot-7.png)

* * *

##  Stationen-Liste

Zeigt eine Liste aller veröffentlichen Stationen an mit den Artikeln, die sich
dort befinden

  * Shortcode: ` [cb_locations] `

[ ![](/img/c4609501dfa4cd496f7d0fa1ee36064a.png) ](/wp-
content/uploads/2021/05/screenshot-8.png) [cb_locations]: Liste von Stationen

* * *

##  Liste aller Buchungen

  * Shortcode: ` [cb_bookings] `
  * Liste aller Buchungen (eigene Buchungen des eingeloggten Nutzenden)
  * Administrator*innen sehen hier alle Buchungen
  * [ cb_manager ](/docs/grundlagen/rechte-des-commonsbooking-manager/) sehen hier alle eigenen Buchungen und Buchungen der ihnen zugeordneten Artikel und Stationen.

[ ![](/img/23ebefac587e513e2ff69e5f4d59fc00.png) ](/wp-
content/uploads/2021/05/Bildschirmfoto-2021-12-16-um-13.57.46.png)

