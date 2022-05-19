<?php

use CommonsBooking\View\Booking;
require(dirname(__FILE__) . '/../../../../wp-load.php');
require_once('../includes/Users.php');

$user_id = $_GET["user_id"];
$user_hash = $_GET["user_hash"];

if (commonsbooking_isUIDHashComboCorrect($user_id,$user_hash)){

    $bookingiCal = Booking::getBookingListiCal($user_id);
    if ($bookingiCal) {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="ical.ics"');
        echo $bookingiCal;
    }
    else {
        die("Error in retrieving booking list.");
    }

}
else {
    if (!$user_id){
        die("user id missing");
    }
    elseif (!$user_hash){
        die("user hash missing");
    }
    else {
        die("user_id and user_hash mismatch. Authentication failed.");
    }
}


?>
