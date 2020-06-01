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
        $weekNr = isset($_POST['cw']) ? $_POST['cw'] : date('W');
        $week = new Week($weekNr);
        $lastWeek = new Week($weekNr + 5);

        $item = isset($_POST['item']) && $_POST['item'] != "" ? $_POST['item'] : false;
        $location = isset($_POST['location']) && $_POST['location'] != "" ? $_POST['location'] : false;

        if(!$item || !$location) {
            header('Content-Type: application/json');
            echo json_encode(false);
            wp_die(); // All ajax handlers die when finished
        }

        $calendar = new Calendar(
            $week->getDays()[0],
            $lastWeek->getDays()[6],
            [$location],
            [$item]
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

                $allLocked = true;
                foreach ($day->getGrid() as $slot) {

                    // Add only bookable slots for time select
                    if(
                        $slot['timeframe'] instanceof \WP_Post &&
                        get_post_meta($slot['timeframe']->ID, 'type', true) == Timeframe::BOOKABLE_ID) {
                        $dayArray['slots'][] = $slot;
                    }

                    // If there's no timeframe, nothing can be selected
                    if (!empty($slot['timeframe'])) {
                        // If it's a locked timeframe, nothing can be selected
                        if ($slot['timeframe']->locked) {
                            $dayArray['locked'] = true;
                        } else {
                            // if not all slots are locked, the day should be selectable
                            $allLocked = false;
                        }
                    }
                }

                // If there are no slots defined, there's nothing bookable.
                if(!count($dayArray['slots'])) {
                    $dayArray['locked'] = true;
                }

                $jsonResponse['days'][$day->getFormattedDate('Y-m-d')] = $dayArray;
                if ($dayArray['locked']) {
                    if($allLocked) {
                        $jsonResponse['lockDays'][] = $day->getFormattedDate('Y-m-d');
                    } else {
                        $jsonResponse['bookedDays'][] = $day->getFormattedDate('Y-m-d');
                    }
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
        $location = $post->ID;
        $args = [
            'post' => $post,
            'wp_nonce' => Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'location' => $location,
            'postUrl' => get_permalink($location)
        ];

        $item = isset($_GET['item']) && $_GET['item'] != "" ? $_GET['item'] : false;
        $items = \CommonsBooking\Repository\Item::getByLocation($location);

        // If theres no item selected, we'll show all available.
        if(!$item) {
            if(count($items)) {
                // If there's only one item available, we'll show it directly.
                if(count($items) == 1) {
                    $args['item'] = $items[0];
                } else {
                    $args['items'] = $items;
                }
            }
        } else {
            $args['item'] = get_post($item);
        }

        echo self::render(self::$template, $args);
    }

}
