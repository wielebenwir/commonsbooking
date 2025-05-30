#  Standardwerte für Zeitrahmenerstellung ändern

__

Es ist möglich mithilfe von kleinen Codeschnipseln die Standardwerte, die bei
jeder Erstellung eines Zeitrahmens schon ausgefüllt sind, zu ändern.

Code Schnipsel sind meist sehr kurzer Code in PHP und können über ein [ Child
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

###  Buchungskommentar

    
    
    function change_defaults_comment( $comment ) {
        $comment = "Hier dein Buchungskommentar"; // string
        return $comment;
    
    }
    add_filter( 'commonsbooking_defaults_comment', 'change_defaults_comment' );

###  Buchungstyp

    
    
    function change_defaults_type( $type ) {
        $type = "2"; //Bookable
        //$type = "3"; //Holiday
        //$type = "5"; //blocked (not overbookable)
        return $type;
    
    }
    add_filter( 'commonsbooking_defaults_type', 'change_defaults_type' );

###  Standort

    
    
    function change_defaults_location_id( $location_id ) {
        $location_id = ""; //String with valid post id of location
        return $location_id;
    }
    add_filter( 'commonsbooking_defaults_location-id', 'change_defaults_location_id' );

###  Artikel

    
    
    function change_defaults_item_id( $item_id ) {
        $item_id = ""; //String with valid post id of item
        return $item_id;
    
    }
    add_filter( 'commonsbooking_defaults_item-id', 'change_defaults_item_id' );

###  Maximale Buchungstage

    
    
    function change_defaults_timeframe_max_days( $max_days ) {
        $max_days = 3; //just a number
        return $max_days;
    }
    add_filter( 'commonsbooking_defaults_timeframe-max-days', 'change_defaults_timeframe_max_days' );

###  Buchungsvorlauf

    
    
    function change_defaults_booking_startday_offset( $offset ) {
        $offset = 0; //just a number
        return $offset;
    }
    add_filter( 'commonsbooking_defaults_booking-startday-offset', 'change_defaults_booking_startday_offset' );
    

###  Buchungs bis (Kalender)

    
    
    function change_defaults_timeframe_advance_booking_days( $advance_booking_days ) {
        $advance_booking_days = 365; //just a number
        return $advance_booking_days;
    }
    add_filter( 'commonsbooking_defaults_timeframe-advance-booking-days', 'change_defaults_timeframe_advance_booking_days' );
    

###  Zur Buchung zugelassene Rollen

    
    
    function change_defaults_allowed_user_roles( $allowed_user_roles ) {
        //$allowed_user_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'cb_manager' ); //array of valid user roles that the booking of this timeframe should be restricted to
        return $allowed_user_roles;
    }
    add_filter( 'commonsbooking_defaults_allowed_user_roles', 'change_defaults_allowed_user_roles' );
    

###  Ganztägige Buchung

    
    
    function change_defaults_full_day( $full_day ) {
        $full_day = 0; //0 for unset, 1 for set
        return $full_day;
    }
    add_filter( 'commonsbooking_defaults_full-day', 'change_defaults_full_day');
    

###  Slotbuchung / Stundenbuchung

    
    
    function change_defaults_grid( $grid ) {
        $grid = "0"; //Full slot
        $grid = "1"; //Hourly
        return $grid;
    }
    add_filter( 'commonsbooking_defaults_grid', 'change_defaults_grid');
    

###  Startzeit

    
    
    function change_defaults_start_time( $start_time ) {
        //$start_time = "05:00"; //24h format
        return $start_time;
    }
    add_filter( 'commonsbooking_defaults_start-time', 'change_defaults_start_time' );
    

###  Endzeit

    
    
    function change_defaults_end_time( $end_time ) {
        //$end_time = "10:00"; //24h format
        return $end_time;
    }
    add_filter( 'commonsbooking_defaults_end-time', 'change_defaults_end_time' );

###  Wiederholung

    
    
    function change_defaults_timeframe_repetition( $repitition ) {
        //$repetition = "norep"; //no repetition
        //$repetition = "d"; //daily
        //$repetition = "w"; //weekly
        //$repetition = "m"; //monthly
        //$repetition = "y"; //yearly
        $repetition = "";
        return $repetition;
    }
    add_filter( 'commonsbooking_defaults_timeframe-repetition', 'change_defaults_timeframe_repetition' );
    

###  Wochentage

    
    
    function change_defaults_weekdays( $weekdays ) {
        //$weekdays = array( '1', '2', '3', '4', '5', '6', '7' ); //array of selected weekdays
        return $weekdays;
    }
    add_filter( 'commonsbooking_defaults_weekdays', 'change_defaults_weekdays' );

###  Wiederholungs-Start

    
    
    function change_defaults_repetition_start( $repetition ) {
        //$repetition = "05/27/2023"; //format mm/dd/yyyy
        return $repetition;
    }
    add_filter( 'commonsbooking_defaults_repetition-start', 'change_defaults_repetition_start' );
    

###  Wiederholungs-Ende

    
    
    function change_defaults_repetition_end( $repetition ) {
        //$repetition = "06/27/2023"; //format mm/dd/yyyy
        return $repetition;
    }
    add_filter( 'commonsbooking_defaults_repetition-end', 'change_defaults_repetition_end' );
    

###  Buchungscodes erstellen

    
    
    function change_defaults_create_booking_codes( $create_booking_codes ) {
        $create_booking_codes = ""; //0 for unset, 1 for set
        return $create_booking_codes;
    }
    add_filter( 'commonsbooking_defaults_create-booking-codes', 'change_defaults_create_booking_codes');
    

###  Buchungscodes anzeigen

    
    
    function change_defaults_show_booking_codes( $show_booking_codes ) {
        $show_booking_codes = ""; //0 for unset, 1 for set
        return $show_booking_codes;
    }
    add_filter( 'commonsbooking_defaults_show-booking-codes', 'change_defaults_show_booking_codes');
    

