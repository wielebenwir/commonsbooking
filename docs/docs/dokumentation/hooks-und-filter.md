# Hooks und Filter

Damit User des Plugins zentrale Funktionalitäten auf sich anpassen können,
werden Wordpress [Action-Hooks und Filter-Hooks](https://developer.wordpress.org/plugins/hooks/) implementiert.
Diese erlauben es deine eigenen Code-Schnipsel an bestimmten Stellen im Plugin einzubinden und das Verhalten auf deine
Bedürfnisse anzupassen.

::: info
Code Schnipsel sind meist sehr kurzer Code in PHP und kann über ein Child Theme eingebunden werden oder über spezielle Plugins für Code Schnipsel (z.B. Code Snippets). Dafür musst du nicht sonderlich viel PHP können, es ist aber auch möglich mit diesen Snippets etwas fundamentales an der Funktion der Webseite zu ändern oder auch Fehler zu erzeugen, die das Buchungssystem nicht mehr nutzbar machen. Wenn du in der Dokumentation Beispiele siehst, dann sind diese einigermaßen sicher und getestet. Ein gewisses Restrisiko bleibt aber. Falls du Probleme haben solltest, dann kannst du dich gerne an uns wenden. Bitte gib aber auch sämtliche Codeschnipsel mit an, die ihr verwendet. Dadurch können wir das Problem besser nachvollziehen.
:::

[[toc]]

## Action Hooks
Ab Version `2.7.0`

Mit Action-Hooks (https://developer.wordpress.org/plugins/hooks/actions/) kannst du deine eigenen Code-Schnipsel an bestimmten Stellen in den CommonsBooking Vorlagen einbinden. 
So kannst du deinen eigenen Code in die Templates einfügen, ohne die entsprechenden Template Dateien ersetzen zu müssen.


Die vom Plugin veröffentlichten Action-Hooks sind nach dem Prinzip
```
commonsbooking_(before/after)_(template-file)
```
strukturiert. Mit der Funktion add_action kannst du deine eigene Callback Funktion integrieren. Beispiel:

```php
function itemsingle_callback() {
    // dein code hier
}
add_action( 'commonsbooking_before_item-single', 'itemsingle_callback' );
```

**Alle Action Hooks im Überblick:**

* commonsbooking_before_booking-single
* commonsbooking_after_booking-single
* commonsbooking_before_location-calendar-header
* commonsbooking_after_location-calendar-header
* commonsbooking_before_item-calendar-header
* commonsbooking_after_item-calendar-header
* commonsbooking_before_location-single
* commonsbooking_after_location-single
* commonsbooking_before_timeframe-calendar
* commonsbooking_after_timeframe-calendar
* commonsbooking_before_item-single
* commonsbooking_after_item-single
* commonsbooking_mail_sent

### `commonsbooking_before_booking-single`

```php
/*
 * Hiermit fügst du auf der Buchungs-Bestätigungs-Seite vor dem Inhalt einen
 * Link ein, der die User zum Mailprogramm mit vorbefüllter Nachricht führt.
 */
add_action( 'commonsbooking_before_booking-single', 'my_booking_reparatur_mail' );
function my_booking_reparatur_mail() {
        global $post;

        $mailTo = "helpme@example.com";

        if (get_current_user_id() == $post->post_author) {

                $booking = new \CommonsBooking\Model\Booking( $post->ID );
                $bookingUrl = site_url() . "/cb_booking/" . $post->post_name . "/";

                echo "<div class=\"cb-notice\"> <p>Melde technische Probleme während der Fahrt via <a href=\"mailto:" . $mailTo . "?subject=Hilfe%20mit%20" . $booking->getItem()->post_title . "&body=Hallo Team, ich brauche Hilfe:%20" . $bookingUrl . "%0D%0A%0D%0ADanke und Grüße\">Mail über unserer Vorlage.</a></p></div>";

        }
}

```

## Filter Hooks

Filter Hooks (https://developer.wordpress.org/plugins/hooks/filters/) funktionieren ähnlich wie Action Hooks jedoch mit dem Unterschied, dass die Callback Funktion einen Wert übergeben bekommt, diesen modifiziert und ihn dann wieder zurückgibt.

**Alle Filter Hooks im Überblick:**

* commonsbooking_isCurrentUserAdmin
* commonsbooking_isCurrentUserSubscriber
* commonsbooking_get_template_part
* commonsbooking_template_tag
* commonsbooking_tag_$key_$property
* commonsbooking_booking_filter
* commonsbooking_mail_to
* commonsbooking_mail_subject
* commonsbooking_mail_body
* commonsbooking_mail_attachment

Es gibt auch Filter Hooks, mit denen du zusätzliche Benutzerrollen, die zusätzlich zum CB Manager Artikel und Standorte administrieren können, hinzufügen kannst.
Mehr dazu: Zugriffsrechte vergeben (CB-Manager)

Darüber hinaus gibt es Filter Hooks, mit denen du die voreingestellten Standardwerte bei der Zeitrahmenerstellung ändern kannst, mehr dazu hier:
https://commonsbooking.org/docs/erweiterte-funktionalitaet/standardwerte-fuer-zeitrahmenerstellung-aendern/

### `commonsbooking_tag_$key_$property`

Das ist ein sehr technischer Hook. Die generischen Werte für `$key` und `$property` ermittelst du eigentständig aus 
dem Code.

**Beispiel:** Stations-Betreibende als E-Mail Empfänger der Buchungs-Mails überschreiben.
Ein Anwendungsfall für diesen Hook, stellt z.B. die Verwendung innerhalb einer Staging-Umgebung dar. Du möchtest dort 
Buchungs-Vorgänge einer neuen Version von Commonsbooking mit verschiedenen Zeitrahmen-Stations-Artikel-Kombinationen 
testen, aber gleichzeitig nicht Mails an alle möglichen Stationsbetreibende verschicken. Dann kannst das mit folgendem 
Filter-Hook via eingebundem Code-Snippet (gleichnamiges Plugin) oder Theme-/Plugin-Datei-Editor erreichen.
Der `$key` ist `cb_location` und die `$property` ist `_cb_location_email`.

```php
/**
 * This adds a filter to send all booking confirmations to one 
 * email adress.
 */
function mywebsite_cb_return_location_mail( $value ){
    return 'yourname@example.com';
}
add_filter( 'commonsbooking_tag_cb_location__cb_location_email', 'mywebsite_cb_return_location_mail' );
```

### `commonsbooking_mail_attachment`

Ab Version `2.8.0`

Dieser Hook ermöglicht es Anhänge an einer Buchungsmail zu verändern.

```php

```
