#  Migration von Version 0.9.x

__

Die Migration von Version CB 0.9.x zu CB 2.x.x könnt ihr per Knopfdruck
erledigen. Die Migration importiert folgende Daten:

  * Artikel 
  * Standorte 
  * Zeiträume (inkl. Buchungscodes) 
  * vorhandene Buchungen 
  * Die Liste der Buchungscodes 
  * Absender E-Mail und Name 
    * Hinweis: Dabei werden evtl. bereits im neuen CommonsBooking gespeicherte Absendername und E-Mail überschrieben. 
  * Die in Standorten definierten geschlossenen Tage (werden als nicht buchbare Tage in CB 2.x.x. in die Einstellungen der buchbaren Zeitrahmen übernommen) 
  * Die bisherigen Registrierungsfelder für Nutzer (Telefonnummer, Adresse) 
  * Kategorien 
  * Ab CB 2.2.14 kannst du im Zeitrahmen-Editor einstellen, ob Buchungscodes angezeigt werden oder nicht. Während der Migration wird dieser Wert für alle importierten buchbaren Zeiträume auf “an” gesetzt, um direkt wie aus CB 0.9.x gewohnt, die Buchungscodes den Nutzenden anzuzeigen. 
  * Für die Nutzung der Karte werden Geo-Koordinaten für jeden Standort benötigt. Du kannst bei der Migration die Option “Geo-Koordinaten erzeugen” anklicken. Beim Import werden dann für jeden Standort anhand der Adressdaten die Geo-Koordinaten generiert und zu dem Standort gespeichert. 

* * *

##  1\. Vorbereitung der Migration

  * Erstelle ein **Backup der aktuellen Seite** (wir empfehlen das Plugin „ [ Updraft Plus ](https://de.wordpress.org/plugins/updraftplus/) “) 
  * Aktualisiere dein bestehendes Commons Booking auf die neueste Version 
  * Gehe unter Einstellungen -> Commons Booking zum Tab “E-Mails” und kopiere dir dort die Vorlagen-Texte in einen Texteditor auf deinem Rechner (Notepad oder ähnliches). Bei der Migration können die Templates nicht übernommen werden, da das neue CommonsBooking mit anderen [ Template-Tags ](/docs/einstellungen/template-tags/) arbeitet. Nach der Migration sind im neuen CommonsBooking dann neue Standardvorlagen aktiviert. Diese kannst du dann manuell an deine Bedürfnisse anpassen. Bitte nicht die gespeicherten Vorlagen einfach in das neue CB kopieren, da sonst die Platzhalter (Template-Tags) nicht mehr funktionieren. 
  * Auf unserer [ Doku-Seite der Template-Tags ](/docs/einstellungen/template-tags/) findest du die Namen der neuen Template-Tags und kannst mit diesen dann die Templates entsprechend anpassen. 
  * [ Installiere dir CommonsBooking 2 ](/docs/installation/neuinstallation/) und aktiviere das Plugin. Du kannst die Version 2 parallel zu deiner bestehenden CommonsBooking-Installation betreiben. 
  * Wir empfehlen, währen der Migration deine Seite in einen Wartungsmodus zu versetzen, damit während der Migrations- und Testzeit keine Buchungen möglich sind, die dann evtl. in der neuen Version nicht zur Verfügung sind. Ihr könnt dazu z.B. das Plugin [ WP Maintenance Mode ](https://de.wordpress.org/plugins/wp-maintenance-mode/) nutzen. Im Wartungsmodus könnt ihr selbst als Administratoren natürlich auf die Seite zugreifen und so alles testen. 

##  2\. Migration durchführen Daten migrieren

Erstelle vor der Migration **ein Backup eurer Seite** (wir empfehlen das
Plugin „ [ Updraft Plus ](https://de.wordpress.org/plugins/updraftplus/) “)

  1. Klicke in den Einstellungen -> CommonsBooking im Reiter “ **Migration** ” auf “ **Migration starten** ” und warte einen Moment, bis alle Daten migriert sind. Die Übernahme der Datensätze erfolgt einzelnen Schritten, um nicht eure Server zu überlasten. Bei vielen Datensätzen (z.B. vielen Buchungen und Buchungscodes) kann der Vorgang mehrere Minuten dauern. Bitte habe Geduld 🙂   
Während des Imports aktualisiert sich die Anzahl der importierten Datensätze.
Warte, bis du die Meldung “Migration beendet” siehst.

  2. CB 2.x.x hat nun deine Daten importiert, du kannst CB 0.9.x deaktivieren.   
Hinweis: Wenn etwas nicht funktioniert hat, kannst du CB 0.9.x. später einfach
wieder aktivieren und bist sozusagen direkt im vorherigen Stand.

  3. Falls bei der Migration Probleme auftauchen sollten oder diese nicht startet, deaktiviere nicht erforderliche Plugins. Probleme wurden etwa mit den Plugin “HiFi (Head Injection, Foot Injection)” festgestellt. 

**Bitte beachte:**

  * **Kategorien** : Wenn du in CB 0.9.x Kategorien angelegt hast, werden diese ebenfalls für Artikel und Standorte migriert. Die Kategorien werden im neuen CommonsBooking jedoch erst aktiv, wenn du CB0 deaktiviert hast. **Bitte deaktiviere deshalb CB0, bevor du die Migration prüfst** . 
  * **Erneutes Importieren:** Du kannst die Migration beliebig oft wiederholen. Bitte beachte, dass dabei die bereits importierten Daten mit den jeweils aktuellen Werten aus CB1 überschrieben werden. Artikel, Standorte oder Zeitrahmen, die du in der Zwischenzeit direkt in CB2 angelegt hast, bleiben unverändert. 
  * **Gelöschte Elemente** : Wenn Du die Migration noch einmal erneut durchführen möchtest, ist das grundsätzlich möglich. Beachte aber dabei folgendes: Wenn du die einem vorigen Migrationsdurchlauf bereits im neuen CommonsBooking angelegten Daten (Artikel. Standorte, Zeitrahmen) gelöscht hast (also in den Papierkorb legst), musst du vor einem erneuten Migrationsdurchlauf den Papierkorb im neuen CommonsBooking für alle Artikel / Standorte / Zeitrahmen leeren, ansonsten kann es zu Fehlerhaften Daten beim Import kommen. 
  * **Nicht mehr existierende Artikel / Standorte / Nutzer:** Wenn Zeitrahmen oder Buchungen importiert werden und die damit verknüpften Artikel, Standorte oder Nutzende nicht mehr im alten CommonsBooking vorhanden waren, dann werden diese Angaben leer gelassen bzw. mit einen “null” oder “undefined user” gekennzeichnet. 

##  4\. Registrierungsfelder übernehmen

Vordefinierte Registrierungsfelder (z.B. Adresse, AGBs akzeptiert) sind in
CommonsBooking 2.x.x **nicht mehr standardmässig aktiv** . Um diese zu re-
aktivieren, gehst du so vor:

Klicke in den Einstellungen im Reiter “ **Migration** ”

  * Klicke unter „CommonsBooking Version 0.X profile fields“ auf „Aktivieren“ 
  * Zusätzlich überprüfe ob der Link zu den AGBs korrekt ist. 

Mehr Infos zum Thema Registrierungsfelder findest du auf der Seite [
Registrierungs-Seiten und -Felder anpassen
](/docs/einstellungen/registrierungs-seiten-und-benutzerfelder-anpassen/)

* * *

##  3\. Artikel-Seite anlegen

In CommonsBooking 2 musst du nicht mehr in den Einstellungen eine spezielle
„Artikel“-Seite einstellen, du kannst deine Artikel-Liste ganz einfach auf
einer bestehenden Seite an gewünschter Stelle per **Shortcode** einfügen.

  1. Erstelle eine neue Seite und füge den Shortcode „[cb_items]“ ein. 
     1. In WordPress mit dem „Classic-Editor“ schreibst du den Shortcode einfach in den text: ` [cb_items] `
     2. In WordPress (ab 5) mit dem Gutenberg-Block-Editor fügst du per Plus einen neuen Shortcode-Block hinzu, und gibst dort den Shortcode: ` [cb_items] `
  2. Navigiere nun auf die Seite und mache eine Test-Buchung eines Artikels. 
  3. Wenn alles gut aussieht: Ersetze die die alte Artikel-Liste in deiner Navigation mit der neuen Seite. 

* * *

* * *

