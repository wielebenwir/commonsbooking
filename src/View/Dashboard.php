<?php


namespace CommonsBooking\View;


use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\PostType\Item;
use CommonsBooking\PostType\Location;
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

        echo self::render(
            self::$template,
            [
                'currentLocation' => $location,
                'currentItem' => $item,
                'locations' => Location::getAllPosts(),
                'items' => Item::getAllPosts(),
                'calendar' => new Calendar(
                    $week->getDays()[0],
                    $week->getDays()[6],
                    $location ? [$location] : [],
                    $item ? [$item] : [],
                )
            ]
        );
    }

}
