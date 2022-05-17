<?php

use CommonsBooking\View\Booking;
require(dirname(__FILE__) . '/../../../../wp-load.php');
require_once('../includes/Users.php');

$user_id = $_GET["user_id"];
$user_hash = $_GET["user_hash"];

if (commonsbooking_isUIDHashComboCorrect($user_id,$user_hash)){

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="ical.ics"');
    echo Booking::getBookingListiCal($user_id);

}
else {
    echo wp_hash($user_id); //TODO - replace with error message
}


?>
