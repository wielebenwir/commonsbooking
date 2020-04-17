<?php


namespace CommonsBooking\View;


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

        $week = isset($_GET['cw']) ? $_GET['cw'] : date('W');

        echo self::render(
            self::$template,
            [
                'locations' => Location::getAllPosts(),
                'items' => Item::getAllPosts(),
                'week' => new Week($week)
            ]
        );
    }

}
