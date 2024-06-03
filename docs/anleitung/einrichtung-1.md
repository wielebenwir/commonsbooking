# Einrichtung

Du hast CommonsBooking installiert und möchtest es jetzt einrichten, um später eine Buchung eines Artikels 
vornehmen zu können.

[[toc]]

## Artikel, Zeitrahmen und Standort anlegen

Also legen wir über den Wordpress-Adminbereich zuerst einen Artikel an, wähle dazu im linken Menü "Artikel -> Artikel erstellen" aus.
Trage danach die Felder für den Namen

Danach folgt der Standort, wähle dazu im linken Menü "Standorte -> Standort erstellen".
Trage in die Felder den Namen des Standorts und seine Adresse ein, dann kannst du noch die Koordinaten ermitteln und ggf. anpassen.
Alle anderen Felder kannst du erstmal vernachlässigen, deren Bedeutung ist in der [Dokumentation zum Standort](/dokumentation/standorte) beschrieben.

Am Schluss erstellen wir den Zeitrahmen, wähle dazu auch hier im linken Menü "Zeitrahmen -> Zeitrahmen erstellen" aus.
Ordne den Zeitrahmen zuerst dem Standort und dem Artikel von oben zu.
Trage auch hier die Felder **Typ**, **Wiederholungstyp** und **Start-** und **Ende-Datum** entsprechend deiner Vorstellung ein.
Die restlichen Felder sind optional und du kannst sie hier auch erstmal vernachlässigen, deren Bedeutung ist in der [Dokumentation zum Zeitrahmen](/dokumentation/zeitrahmen) beschrieben.

## Einbinden von Zeitrahmen im Frontend

::: info
* Was ist ein Shortcode? Kurz, eine Möglichkeit Inhalte aus der Datenbank auf der Website anzuzeigen.
  Nachzulesen in der [Wordpress-Dokumentation zu Shortcodes](https://en.support.wordpress.com/shortcodes/).
* [Hier findest du eine Liste aller Shortcodes die CommonsBooking](/dokumentation/shortcodes) zur Anzeige von Artikeln und Standorten anbietet.
:::

Füge dazu den Shortcode `[cb_items]` auf einer beliebigen Wordpress Seite ein.
Alle erstellen buchbaren Artikel werden jetzt mit einem kleinen Bild und ihrem Standort an dieser Stelle angezeigt.