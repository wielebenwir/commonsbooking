#  Shortcodes für Frontend-Darstellung

__

Die CommonsBooking-Inhalte (z.B. Artikellisten oder Verfügbarkeiten) kannst du auf der Website anzuzeigen, indem du sogenannte Shortcodes verwendest.
Shortcodes können in jede WordPress Seite eingefügt werden. [Offizielle WordPress Dokumentation](https://en.support.wordpress.com/shortcodes).

Die Anzeige eines Shortcodes kann dabei über bestimmte Argumente beeinflusst werden.

Beispiel-Argumente:

  * ` orderby ` : Bestimmt das Attribut nachdem sortiert werden soll z.B. ` orderby=post_title ` für eine Sortierung nach dem Namen eines Posts.
  * ` order ` : Bestimmt die Sortierreihenfolge. Aufsteigend ` ASC ` und absteigend ` DESC ` .

Diese Paramter sind für die folgenden Shortcodes gültig, welche über das CommonsBooking Plugin verfügbar sind:

##  Artikel-Liste

Zeigt eine Liste aller veröffentlichen Artikel an mit den Stationen, an denen
sie sich befinden.

  * Shortcode: `[cb_items]`
  * Argumente:
    * `category_slug`: Kategorie-Filter
    * `p`: Nur einzelnen Artikel anzeigen, wobei 1234 die numerische ID von dem Artikel ist.
      ```
      [cb_items p=1234]
      ```
    * `location-id`: Nur Artikel von einer Station anzeigen, wobei 1234 die numerische ID von dem Standort Post ist.
      ```
      [cb_items location-id=1234]
      ```

![](/img/shortcode-cb-items.png)

**Nur eine bestimmte Kategorie anzeigen?**

Wenn ihr Artikel Kategorien zugeordnet habt, könnt ihr über einen Parameter
nur Artikel einer bestimmten Kategorie anzeigen. Dazu sucht ihr zunächst die
Titelform / Slug der Kategorie über das Kategorie-Menü aus und setzt diese
dann folgendermaßen ein.

Beispiel:
```
[cb_items category_slug=titelform]
```


##  Einzelner Artikel

Zeigt einen einzelnen Artikel in der Listenansicht (s.o.) an.

* Shortcode: `[cb_items]`
* Argumente: `p` die Post-ID von deinem Artikel

Beispiel:
```
[cb_items p=1234]
```

##  Karte mit Filter-Möglichkeit

Zeigt eine Karte aller veröffentlichen Artikel an.
Dafür muss zuerst eine Karte unter "CommonsBooking -> Karten" eingerichtet werden.

[Mehr zur Einrichtung und Konfiguration von Karten](/dokumentation/einstellungen/karte-einbinden).

  * Shortcode: ` [cb_map] `
  * Argumente ( **erforderlich!** ): ` id `

![](/img/shortcode-cb-map.png)

##  Karte mit Artikelliste

::: tip Ab Version 2.9
:::

Bisher war jeder Shortcode nur unabhängig voneinander verwendbar, dh. ein
Filter der auf der Karte angewendet wurde hatte keine Auswirkungen auf die
danebenstehende Artikelliste. Dafür gibt es jetzt den neuen Shortcode

  * Shortcode: ` [cb_search] `
  * Argumente (**erforderlich!**): ` id `

![](/img/shortcode-cb-search-map.png)

[ Weitere Argumente und ausführliche Dokumentation
](/dokumentation/einstellungen/neues-frontend-beta)

##  Artikel-Tabelle mit Verfügbarkeit

Zeigt eine Tabelle aller veröffentlichen Artikel an mit Stationen, an denen
sie sich befinden und der aktuellen Verfügbarkeit.

  * Shortcode: ` [cb_items_table] `
  * Argumente
    * `days`: Die Anzahl der anzuzeigende Tage ist standardmäßig auf 31 gesetzt. Über das Attribut days kann dieser Wert angepasst werden. Beispiel, um nur 10 Tage anzuzeigen.

      Beispiel:
      ```
      [cb_items_table days=10]
      ```
    * `desc`: Zusätzlich kann oberhalb der Tabelle eine kurze Beschreibung mit dem Attribut desc eingefügt werden.

      Beispiel:
      ```
      [cb_items_table desc=Lastenräder]
      ```
    * `itemcat`: Filter nach Artikel-Kategorien

      Beispiel:
      ```
      ` [cb_items_table itemcat=itemcategoryslug] `
      ```
    * `locationcat`: Filter nach Standort-Kategorien

      Beispiel:
      ```
      [cb_items_table locationcat=locationcategoryslug]
      ```

![](/img/shortcode-cb-items-table.png)

##  Stationen-Liste

Zeigt eine Liste aller veröffentlichen Stationen an mit den Artikeln, die sich
dort befinden

  * Shortcode: `[cb_locations]`

![](/img/shortcode-cb-locations.png)

##  Liste aller Buchungen

  * Shortcode: `[cb_bookings]`
  * Liste aller Buchungen (eigene Buchungen des eingeloggten Nutzenden)
  * Administrator*innen sehen hier alle Buchungen
  * [User mit der Rolle cb_manager](/dokumentation/grundlagen/rechte-des-commonsbooking-manager) sehen hier alle eigenen Buchungen und Buchungen der ihnen zugeordneten Artikel und Stationen.

![](/img/shortcode-cb-bookings.png)

