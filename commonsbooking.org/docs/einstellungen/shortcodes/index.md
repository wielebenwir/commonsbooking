  1. __
  2. [ Home  ](https://commonsbooking.org/)
  3. __
  4. [ Dokumente  ](https://commonsbooking.org/dokumentation/)
  5. __
  6. [ Administration  ](https://commonsbooking.org/docs/einstellungen/)
  7. __
  8. Shortcodes für Frontend-Darstellung 

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

[ ![](https://commonsbooking.org/wp-
content/uploads/2021/01/screenshot-1-237x300.png)
](https://commonsbooking.org/wp-content/uploads/2021/01/screenshot-1.png)
[cb_items]: Liste von Artikeln

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
Einrichtung und Konfiguration von Karten
](https://commonsbooking.org/docs/einstellungen/karte-einbinden/) .

  * Shortcode: ` [cb_map] `
  * Argumente ( **erforderlich!** ): ` id `

[ ![](https://commonsbooking.org/wp-
content/uploads/2021/05/screenshot-5-e1639659329563-300x257.png)
](https://commonsbooking.org/wp-content/uploads/2021/05/screenshot-5.png)
[cb_map]: Karte mit Filter

* * *

##  Karte mit Artikelliste (BETA)

Erst ab Version 2.9

Bisher war jeder Shortcode nur unabhängig voneinander verwendbar, dh. ein
Filter der auf der Karte angewendet wurde hatte keine Auswirkungen auf die
danebenstehende Artikelliste. Dafür gibt es jetzt den neuen Shortcode

  * Shortcode: ` [cb_search] `
  * Argumente ( **erforderlich!** : ` id `

![](https://commonsbooking.org/wp-content/uploads/2024/01/image.png)

[ Weitere Argumente und ausführliche Dokumentation
](https://commonsbooking.org/docs/einstellungen/neues-frontend-beta/)

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

[ ![](https://commonsbooking.org/wp-
content/uploads/2021/05/screenshot-7-237x300.png)
](https://commonsbooking.org/wp-content/uploads/2021/05/screenshot-7.png)

* * *

##  Stationen-Liste

Zeigt eine Liste aller veröffentlichen Stationen an mit den Artikeln, die sich
dort befinden

  * Shortcode: ` [cb_locations] `

[ ![](https://commonsbooking.org/wp-
content/uploads/2021/05/screenshot-8-237x300.png)
](https://commonsbooking.org/wp-content/uploads/2021/05/screenshot-8.png)
[cb_locations]: Liste von Stationen

* * *

##  Liste aller Buchungen

  * Shortcode: ` [cb_bookings] `
  * Liste aller Buchungen (eigene Buchungen des eingeloggten Nutzenden) 
  * Administrator*innen sehen hier alle Buchungen 
  * [ cb_manager ](https://commonsbooking.org/docs/grundlagen/rechte-des-commonsbooking-manager/) sehen hier alle eigenen Buchungen und Buchungen der ihnen zugeordneten Artikel und Stationen. 

[ ![](https://commonsbooking.org/wp-
content/uploads/2021/05/Bildschirmfoto-2021-12-16-um-13.57.46-265x300.png)
](https://commonsbooking.org/wp-
content/uploads/2021/05/Bildschirmfoto-2021-12-16-um-13.57.46.png)

###  Navigation

[ ← Leihangebote im Frontend anzeigen
](https://commonsbooking.org/docs/einstellungen/leihangebote-im-frontend-
anzeigen/) [ User-Widget einbinden →
](https://commonsbooking.org/docs/einstellungen/widget/)

