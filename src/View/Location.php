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
     * @param $startDate
     * @param $endDate
     * @param $locations
     * @param $items
     * @return array
     * @throws \Exception
     */
    protected static function prepareJsonResponse($startDate, $endDate, $locations, $items) {
        $calendar = new Calendar(
            $startDate,
            $endDate,
            $locations,
            $items
        );

        $jsonResponse = [
            'startDate' => $startDate->getFormattedDate('Y-m-d'),
            'endDate' => $endDate->getFormattedDate('Y-m-d'),
            'days' => [],
            'bookedDays' => [],
            'lockDays' => [],
            'holidays' => [],
            'highlightedDays' => []
        ];

        /** @var Week $week */
        foreach ($calendar->getWeeks() as $week) {
            /** @var Day $day */
            foreach ($week->getDays() as $day) {
                $dayArray = [
                    'date' => $day->getFormattedDate('d.m.Y'),
                    'slots' => [],
                    'locked' => false,
                    'holiday' => true,
                    'firstSlotBooked' => null,
                    'lastSlotBooked' => null
                ];

                // If all slots are locked, day cannot be selected
                $allLocked = true;

                // If no slots are existing, day shall be locked
                $noSlots = true;

                foreach ($day->getGrid() as $slot) {

                    // Add only bookable slots for time select
                    if (!empty($slot['timeframe']) && $slot['timeframe'] instanceof \WP_Post) {
                        // We have at least one slot ;)
                        $noSlots = false;

                        $timeFrameType = get_post_meta($slot['timeframe']->ID, 'type', true);

                        // save bookable state for first and last slot
                        if ($dayArray['firstSlotBooked'] === null) {
                            if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                                $dayArray['firstSlotBooked'] = false;
                            } else {
                                $dayArray['firstSlotBooked'] = true;
                            }
                        } else {
                            if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                                $dayArray['lastSlotBooked'] = false;
                            } else {
                                $dayArray['lastSlotBooked'] = true;
                            }
                        }

                        if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                            $dayArray['slots'][] = $slot;
                        }

                        // Remove holiday flag, if there is at least one slot that isn't of type holiday
                        if (!in_array($timeFrameType, [Timeframe::HOLIDAYS_ID, Timeframe::OFF_HOLIDAYS_ID])) {
                            $dayArray['holiday'] = false;
                        }

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
                if ($noSlots) {
                    $dayArray['locked'] = true;
                    $dayArray['holiday'] = false;
                }

                $jsonResponse['days'][$day->getFormattedDate('Y-m-d')] = $dayArray;
                if ($dayArray['locked']) {
                    if ($allLocked) {
                        if($dayArray['holiday']) {
                            $jsonResponse['holidays'][] = $day->getFormattedDate('Y-m-d');
                        } else {
                            $jsonResponse['lockDays'][] = $day->getFormattedDate('Y-m-d');
                        }

                    } else {
                        $jsonResponse['bookedDays'][] = $day->getFormattedDate('Y-m-d');
                    }
                }
            }
        }

        return $jsonResponse;
    }

    /**
     * Returns json-formatted calendardata.
     * @throws \Exception
     */
    public static function get_calendar_data()
    {
        $startDate = new Day(date('Y-m-d'));
        $endDate = new Day(date('Y-m-d', strtotime('last day of next month')));

        $startDateString = $_POST['sd'];
        if($startDateString) {
            $startDate = new Day($startDateString);
        }

        $endDateString =  $_POST['ed'];
        if($endDateString) {
            $endDate = new Day($endDateString);
        }

        $item = isset($_POST['item']) && $_POST['item'] != "" ? $_POST['item'] : false;
        $location = isset($_POST['location']) && $_POST['location'] != "" ? $_POST['location'] : false;

        if (!$item || !$location) {
            header('Content-Type: application/json');
            echo json_encode(false);
            wp_die(); // All ajax handlers die when finished
        }

        $jsonResponse = self::prepareJsonResponse($startDate, $endDate, [$location], [$item]);

        header('Content-Type: application/json');
        echo json_encode($jsonResponse);
        wp_die(); // All ajax handlers die when finished
    }

    public static function getTemplateData(\WP_Post $post = null) {
        if ($post == null) {
            global $post;
        }
        $location = $post->ID;
        $args = [
            'post' => $post,
            'wp_nonce' => Timeframe::getWPNonceField(),
            'actionUrl' => admin_url('admin.php'),
            'location' => $location,
            'postUrl' => get_permalink($location),
            'type' => Timeframe::BOOKING_ID
        ];

        $item = isset($_GET['item']) && $_GET['item'] != "" ? $_GET['item'] : false;
        $items = \CommonsBooking\Repository\Item::getByLocation($location);

        // If theres no item selected, we'll show all available.
        if (!$item) {
            if (count($items)) {
                // If there's only one item available, we'll show it directly.
                if (count($items) == 1) {
                    $args['item'] = $items[0];
                } else {
                    $args['items'] = $items;
                }
            }
        } else {
            $args['item'] = get_post($item);
        }

        return $args;
    }

    public static function index(\WP_Post $post = null)
    {
        echo self::render(self::$template, self::getTemplateData($post));
    }

}
