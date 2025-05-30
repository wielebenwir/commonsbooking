#  Hooks und Filter

__

##  Action Hooks

Ab Version 2.7

Mit Hooks ( [ [ https://developer.wordpress.org/plugins/hooks/
](https://developer.wordpress.org/plugins/hooks)
](https://developer.wordpress.org/plugins/hooks) ) kannst du deine eigenen
Code-Schnipsel an bestimmten Stellen in den CommonsBooking Vorlagen einbinden.
So kannst du deinen eigenen Code in die Templates einfügen, ohne die
entsprechenden Template Dateien ersetzen zu müssen.

Code Schnipsel sind meist sehr kurzer Code in PHP und kann über ein [ Child
Theme ](https://developer.wordpress.org/themes/advanced-topics/child-themes)
eingebunden werden oder über spezielle Plugins für Code Schnipsel (z.B. Code
Snippets). Dafür musst du nicht sonderlich viel PHP können, es ist aber auch
möglich mit diesen Snippets etwas fundamentales an der Funktion der Webseite
zu ändern oder auch Fehler zu erzeugen, die das Buchungssystem nicht mehr
nutzbar machen. Wenn du in der Dokumentation Beispiele siehst, dann sind diese
einigermaßen sicher und getestet. Ein gewisses Restrisiko bleibt aber. Falls
du Probleme haben solltest, dann kannst du dich gerne an uns wenden. Bitte gib
aber auch sämtliche Codeschnipsel mit an, die ihr verwendet. Dadurch können
wir das Problem besser nachvollziehen.

Die Action Hooks sind nach dem Prinzip

    
    
    commonsbooking_(before/after)_(template-file)

strukturiert. Mit der Funktion _add_action_ kannst du deine eigene Callback
Funktion integrieren. Beispiel:

    
    
    function itemsingle_callback() {
        // dein code hier
    }
    add_action( 'commonsbooking_before_item-single', 'itemsingle_callback' );

###  Alle Action Hooks im Überblick:

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

##  Filter Hooks

Filter Hooks ( [ [ [ [ [ [ [ [ [ [ [ [ [ [
https://developer.wordpress.org/plugins/hooks/filters/
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters)
](https://developer.wordpress.org/plugins/hooks/filters) ) funktionieren
ähnlich wie Action Hooks jedoch mit dem Unterschied, dass die Callback
Funktion einen Wert übergeben bekommt, diesen modifiziert und ihn dann wieder
zurückgibt.

###  Alle Filter Hooks im Überblick:

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

Es gibt auch Filter Hooks, mit denen du zusätzliche Benutzerrollen, die
zusätzlich zum CB Manager Artikel und Standorte administrieren können,
hinzufügen kannst.  
Mehr dazu: [ Zugriffsrechte vergeben (CB-Manager) ](/dokumentation/grundlagen/rechte-
des-commonsbooking-manager/#andere-rollen-einem-artikel-standort-zuweisen-
ab-2-8-2)

Darüber hinaus gibt es Filter Hooks, mit denen du die voreingestellten
Standardwerte bei der Zeitrahmenerstellung ändern kannst, mehr dazu hier:  
[ [ [ [ [ https://commonsbooking.org/dokumentation/erweiterte-
funktionalitaet/standardwerte-fuer-zeitrahmenerstellung-aendern/
](/dokumentation/erweiterte-funktionalitaet/standardwerte-fuer-zeitrahmenerstellung-
aendern) ](/dokumentation/erweiterte-funktionalitaet/standardwerte-fuer-
zeitrahmenerstellung-aendern) ](/dokumentation/erweiterte-
funktionalitaet/standardwerte-fuer-zeitrahmenerstellung-aendern)
](/dokumentation/erweiterte-funktionalitaet/standardwerte-fuer-zeitrahmenerstellung-
aendern) ](/dokumentation/erweiterte-funktionalitaet/standardwerte-fuer-
zeitrahmenerstellung-aendern)

###  Filter Hook: commonsbooking_tag_$key_$property

####  Beispiel: Stations-Betreibende als E-Mail Empfänger der Buchungs-Mails
überschreiben

Ein Anwendungsfall für diesen Hook, stellt z.B. die Verwendung innerhalb einer
Staging-Umgebung dar. Du möchtest dort Buchungs-Vorgänge einer neuen Version
von Commonsbooking mit verschiedenen Zeitrahmen-Stations-Artikel-Kombinationen
testen, aber gleichzeitig nicht Mails an alle möglichen Stationsbetreibende
verschicken. Dann kannst das mit folgendem Filter-Hook via eingebundem Code-
Snippet (gleichnamiges Plugin) oder Theme-/Plugin-Datei-Editor erreichen:

    
    
    /**
     * This adds a filter to send all booking confirmations to one email adress.
     */
    function mywebsite_cb_return_location_mail( $value ){
        return 'yourname@example.com';
    }
    add_filter('commonsbooking_tag_cb_location__cb_location_email', 'mywebsite_cb_return_location_mail' );

