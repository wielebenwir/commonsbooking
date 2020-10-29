<?php

namespace CommonsBooking\View;

use CommonsBooking\Migration\Migration;
use CommonsBooking\Repository\CB1;

class Dashboard extends View
{

    public static function index()
    {
        ob_start();
        cb_get_template_part('dashboard', 'index');
        echo ob_get_clean();
    }

}
