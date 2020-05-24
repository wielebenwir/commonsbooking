<?php

namespace CommonsBooking\View;


use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Location extends View
{

    protected static $template = 'location/index.html.twig';

    /**
     * Returns json-formatted calendardata.
     * @throws \Exception
     */
    public static function get_calendar_data()
    {
        global $post;
        $weekNr = isset($_GET['cw']) ? $_GET['cw'] : date('W');
        $week = new Week($weekNr);
        $lastWeek = new Week($weekNr + 5);

        $location = $post->ID;
        $item = isset($_GET['item']) && $_GET['item'] != "" ? $_GET['item'] : null;
        $type = isset($_GET['type']) && $_GET['type'] != "" ? $_GET['type'] : null;

        $calendar = new Calendar(
            $week->getDays()[0],
            $lastWeek->getDays()[6],
            $location ? [$location] : [],
            $item ? [$item] : [],
            $type ? [$type] : []
        );

        $jsonResponse = [
            'startDate' => $week->getDays()[0]->getFormattedDate('Y-m-d'),
            'endDate' => $lastWeek->getDays()[6]->getFormattedDate('Y-m-d'),
            'days' => [],
            'bookedDays' => [],
            'lockDays' => [],
            'highlightedDays' => []
        ];

        /** @var Week $week */
        foreach ($calendar->getWeeks() as $week) {

            /** @var Day $day */
            foreach ($week->getDays() as $day) {
                $dayArray = [
                    'date' => $day->getFormattedDate('d.m.Y'),
                    'slots' => [],
                    'locked' => false
                ];

                foreach ($day->getGrid() as $slot) {
                    $dayArray['slots'][] = $slot;
                    if (!empty($slot['timeframe'])) {
                        if ($slot['timeframe']->locked) {
                            $dayArray['locked'] = true;
                        }
                    }
                }

                $jsonResponse['days'][$day->getFormattedDate('Y-m-d')] = $dayArray;
                if ($dayArray['locked']) {
                    $jsonResponse['lockDays'][] = $day->getFormattedDate('Y-m-d');
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($jsonResponse);
        wp_die(); // All ajax handlers die when finished
    }

    public static function index(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }

        $weekNr = isset($_GET['cw']) ? $_GET['cw'] : date('W');
        $week = new Week($weekNr);
        $lastWeek = new Week($weekNr + 5);

        $location = $post->ID;
        $item = isset($_GET['item']) && $_GET['item'] != "" ? $_GET['item'] : 16;
        $type = isset($_GET['type']) && $_GET['type'] != "" ? $_GET['type'] : 6;

        echo self::render(self::$template, [
            'post' => $post,
            'wp_nonce' => Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'location' => $location,
            'item' => $item,
            'type' => $type,
            'items' => Item::getAllPosts(),
            'types' => Timeframe::getTypes(),
            'calendar' => new Calendar(
                $week->getDays()[0],
                $lastWeek->getDays()[6],
                $location ? [$location] : [],
                $item ? [$item] : [],
                $type ? [$type] : []
            )
        ]);
    }

}
