#  Installieren

__

Installieren der aktuellen Version des neuen CommonsBooking (Version 2.x.x).

::: warning Migration?
**Du hast bereits CommonsBooking 0.9.x installiert?**
Hier gelangst du zur [Migration](dokumentation/installation/migration-von-cb1)
:::

###  ComonsBooking installieren

  * Gehe zu **WordPress** -> **Plugins** -> **Installieren** und Suche nach “ **CommonsBooking** “.
  * Nach Klick auf “ **Installieren** ” und “ **Aktivieren** ” ist es einsatzbereit.

###  WordPress-Einstellungen für Datum und Zeit prüfen

CommonsBooking arbeitet mit den Zeit- und Datumseinstellungen, die du in
WordPress unter “Einstellungen -> Allgemein -> Zeitzone” eingestellt hast.
Auch die Formatierungen für die Zeitanzeige (24h-Format etc.) und die
Datumsformatierung übernimmt CommonsBooking aus den allgemein WordPress-
Einstellungen.

Daher prüfe bitte, dass du die richtige Zeitzone in WordPress konfiguriert
hast. Wenn du das noch nicht getan hast hole unter “Einstellungen / Allgemein
/ Zeitzone” nach.

###  CommonsBooking konfigurieren

in den WordPress-Einstellungen findest Du nun einen neuen Punkt
“CommonsBooking”. Hier kannst Du einige Voreinstellungen vornehmen.  Bitte
mindestens den Absender-Namen und E-Mail im Tab “Vorlagen” eintragen  Weitere
Informationen zur [ Konfiguration ](/dokumentation/einstellungen-2/) .

###  Standorte, Artikel und Zeitrahmen anlegen

  * **Artikel** unter “CommonsBooking -> Artikel” anlegen
  * **Standorte** unter “CommonsBooking -> Standorte” anlegen
  * Anschließend kannst du im Menüpunkt “ **Zeitrahmen** ” festlegen, wann ein Artikel an einem bestimmten Standort für die Ausleihe verfügbar sein soll.

Detailliierte Informationen dazu findest du unter [Erste Schritte](/dokumentation/erste-schritte/).

###  Inhalte auf der Website einbinden

  * Eine Seite anlegen, auf der deine Artikel erscheinen sollen Den Textbaustein (Shortocde) ` [cb_items] ` in die Seite einbinden.
  * Mit dem klassischen WordPress-Editor fügst du [cb_items] inklusive der Klammern einfach in das Textfeld ein.
  * Mit dem neuen Editor klickst du auf das schwarze **\+ Plus im Kasten** , wählst “Shortcode” und fügst [cb_items] inklusive der Klammern ein.
  * Weitere [ Shortcodes für Karten, Tabellen, etc ](/dokumentation/einstellungen/shortcodes) . Fertig!
  * im Frontend stehen die Artikel nun zur Buchung zur Verfügung.

###  **Hinweis** :

Wenn auf der Artikel- oder Standortliste (die Seiten mit dem cb_items oder
cb_locations-Shortcode) nach Klick auf “Jetzt Buchen” keine gültige Seite
angezeigt wird, müsst ihr einmal in den WordPress Einstellungen auf die Seite
“Permalinks” gehen und dort auf speichern klicken.

