<?php


namespace CommonsBooking\View;


use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\PostType\Item;
use CommonsBooking\PostType\Location;
use CommonsBooking\PostType\Timeframe;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Dashboard extends View
{

    protected static $template = 'dashboard/index.html.twig';

    public static function index() {

        $weekNr = isset($_GET['cw']) ? $_GET['cw'] : date('W');
        $week = new Week($weekNr);
        $location = isset($_GET['location']) && $_GET['location'] != "" ? $_GET['location'] : null;
        $item = isset($_GET['item'])  && $_GET['item'] != "" ? $_GET['item'] : null;
        $type = isset($_GET['type'])  && $_GET['type'] != "" ? $_GET['type'] : null;

        echo self::render(
            self::$template,
            [
                'actionUrl' => admin_url('admin.php'),
                'currentLocation' => $location,
                'currentItem' => $item,
                'currentType' => $type,
                'locations' => Location::getAllPosts(),
                'items' => Item::getAllPosts(),
                'types' => Timeframe::getTypes(),
                'calendar' => new Calendar(
                    $week->getDays()[0],
                    $week->getDays()[6],
                    $location ? [$location] : [],
                    $item ? [$item] : [],
                    $type ? [$type] : []
                )
            ]
        );
    }

}
