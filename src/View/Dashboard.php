<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Dashboard extends View
{

    public static function index() {
        ob_start();
        cb_get_template_part('dashboard', 'index');
        echo ob_get_clean();
    }

}
