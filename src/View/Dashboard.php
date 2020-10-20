<?php

namespace CommonsBooking\View;

use CommonsBooking\Migration\Migration;
use CommonsBooking\Repository\CB1;

class Dashboard extends View
{

    public static function index()
    {
        foreach (CB1::getLocations() as $location) {
            Migration::migrateLocation($location);
        }

        foreach (CB1::getItems() as $item) {
            Migration::migrateItem($item);
        }

        foreach (CB1::getTimeframes() as $timeframe) {
            Migration::migrateTimeframe($timeframe);
        }

        foreach (CB1::getBookings() as $booking) {
            Migration::migrateBooking($booking);
        }

        ob_start();
        cb_get_template_part('dashboard', 'index');
        echo ob_get_clean();
    }

}
