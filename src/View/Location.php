<?php

namespace CommonsBooking\View;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Location extends View
{

    /**
     * Returns JSON-Data for Litepicker calendar.
     *
     * @param Day $startDate
     * @param Day $endDate
     * @param $locations<int|string>
     * @param $items<int|string>
     *
     * @return array
     * @throws \Exception
     */
    public static function prepareJsonResponse(Day $startDate, Day $endDate, $locations, $items)
    {
        $calendar = new Calendar(
            $startDate,
            $endDate,
            $locations,
            $items
        );

        $jsonResponse = [
            'startDate'           => $startDate->getFormattedDate('Y-m-d'),
            'endDate'             => $endDate->getFormattedDate('Y-m-d'),
            'days'                => [],
            'bookedDays'          => [],
            'partiallyBookedDays' => [],
            'lockDays'            => [],
            'holidays'            => [],
            'highlightedDays'     => [],
            'maxDays'             => null,
            'disallowLockDaysInRange' => true
        ];

        if (count($locations) === 1) {
            $jsonResponse['location']['fullDayInfo'] = CB::get(
                'location',
                COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions',
                $locations[0]
            );
            $allowLockedDaysInRange = get_post_meta(
                $locations[0],
                COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range',
                true
            );
            $jsonResponse['disallowLockDaysInRange'] = $allowLockedDaysInRange !== 'on';
        }

        /** @var Week $week */
        foreach ($calendar->getWeeks() as $week) {
            /** @var Day $day */
            foreach ($week->getDays() as $day) {
                $dayArray = [
                    'date'               => $day->getFormattedDate('d.m.Y'),
                    'slots'              => [],
                    'locked'             => false,
                    'bookedDay'          => true,
                    'partiallyBookedDay' => false,
                    'holiday'            => true,
                    'fullDay'            => false,
                    'firstSlotBooked'    => null,
                    'lastSlotBooked'     => null
                ];

                // If all slots are locked, day cannot be selected
                $allLocked = true;

                // If no slots are existing, day shall be locked
                $noSlots = true;

                foreach ($day->getGrid() as $slot) {
                    self::processSlot($slot, $dayArray, $jsonResponse, $allLocked, $noSlots);
                }

                // If there are no slots defined, there's nothing bookable.
                if ($noSlots) {
                    $dayArray['locked'] = true;
                    $dayArray['holiday'] = false;
                    $dayArray['bookedDay'] = false;
                } else {
                    if (count($dayArray['slots']) === 1) {
                        $timeframe = $dayArray['slots'][0]['timeframe'];
                        $dayArray['fullDay'] = get_post_meta($timeframe->ID, 'full-day', true) == "on";
                    }
                }

                $jsonResponse['days'][$day->getFormattedDate('Y-m-d')] = $dayArray;
                if ($dayArray['locked'] || $allLocked) {
                    if ($allLocked) {
                        if ($dayArray['holiday']) {
                            $jsonResponse['holidays'][] = $day->getFormattedDate('Y-m-d');
                        } elseif ($dayArray['bookedDay']) {
                            $jsonResponse['bookedDays'][] = $day->getFormattedDate('Y-m-d');
                        } else {
                            $jsonResponse['lockDays'][] = $day->getFormattedDate('Y-m-d');
                        }
                    } else {
                        $jsonResponse['partiallyBookedDays'][] = $day->getFormattedDate('Y-m-d');
                    }
                }
            }
        }

        return $jsonResponse;
    }

    /**
     * Extracts calendar relevant data from slot.
     *
     * @param $slot
     * @param $dayArray
     * @param $jsonResponse
     * @param $allLocked
     * @param $noSlots
     */
    protected static function processSlot($slot, &$dayArray, &$jsonResponse, &$allLocked, &$noSlots)
    {
        // Add only bookable slots for time select
        if ( ! empty($slot['timeframe']) && $slot['timeframe'] instanceof \WP_Post) {
            // We have at least one slot ;)
            $noSlots = false;

            $timeFrameType = get_post_meta($slot['timeframe']->ID, 'type', true);

            // save bookable state for first and last slot
            if ($dayArray['firstSlotBooked'] === null) {
                if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                    $dayArray['firstSlotBooked'] = false;

                    // Set max-days setting based on first found timeframe
                    if ($jsonResponse['maxDays'] == null) {
                        $timeframeMaxDays = get_post_meta($slot['timeframe']->ID, 'timeframe-max-days', true);
                        $jsonResponse['maxDays'] = intval($timeframeMaxDays ?: 3);
                    }
                } else {
                    $dayArray['firstSlotBooked'] = true;
                }
            }

            // Checks if last slot is booked.
            if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                $dayArray['lastSlotBooked'] = false;
            } else {
                $dayArray['lastSlotBooked'] = true;
            }

            // We need only bookable slots...
            if ($timeFrameType == Timeframe::BOOKABLE_ID) {
                $dayArray['slots'][] = $slot;
            }

            // Remove holiday flag, if there is at least one slot that isn't of type holiday
            if ( ! in_array($timeFrameType, [Timeframe::HOLIDAYS_ID, Timeframe::OFF_HOLIDAYS_ID])) {
                $dayArray['holiday'] = false;
            }

            // Remove bookedDay flag, if there is at least one slot that isn't of type bookedDay
            if ( ! in_array($timeFrameType, [Timeframe::BOOKING_ID])) {
                $dayArray['bookedDay'] = false;
            }

            // Set partiallyBookedDay flag, if there is at least one slot that is of type bookedDay
            if (in_array($timeFrameType, [Timeframe::BOOKING_ID])) {
                $dayArray['partiallyBookedDay'] = true;
            }

            // If there's a locked timeframe, nothing can be selected
            if ($slot['timeframe']->locked) {
                $dayArray['locked'] = true;
            } else {
                // if not all slots are locked, the day should be selectable
                $allLocked = false;
            }
        }
    }

    /**
     * Returns calendar data
     *
     * @param null $item
     * @param null $location
     *
     * @return array
     * @throws \Exception
     */
    public static function getCalendarDataArray($item = null, $location = null)
    {
        $startDate = new Day(date('Y-m-d'));
        $endDate = new Day(date('Y-m-d', strtotime('+3 months')));

        $startDateString = array_key_exists('sd', $_POST) ? sanitize_text_field($_POST['sd']) : date('Y-m-d',
            strtotime('first day of this month', time()));
        if ($startDateString) {
            $startDate = new Day($startDateString);
        }

        $endDateString = array_key_exists('ed', $_POST) ? sanitize_text_field($_POST['ed']) : date('Y-m-d',
            strtotime('+62 days', time()));
        if ($endDateString) {
            $endDate = new Day($endDateString);
        }

        // item by param
        if ($item === null) {
            // item by post-param
            $item = isset($_POST['item']) && $_POST['item'] != "" ? sanitize_text_field($_POST['item']) : false;
            if ($item === false) {
                // item by query var
                $item = get_query_var('item') ?: false;
                if ($item instanceof \WP_Post || $item instanceof CustomPost) {
                    $item = $item->ID;
                }
            }
        } else {
            if ($item instanceof \WP_Post || $item instanceof CustomPost) {
                $item = $item->ID;
            }
        }

        // location by param
        if ($location === null) {
            // location by post param
            $location = isset($_POST['location']) && $_POST['location'] != "" ? sanitize_text_field($_POST['location']) : false;
            if ($location === false) {
                // location by query param
                $location = get_query_var('location') ?: false;
                if ($location instanceof \WP_Post || $location instanceof CustomPost) {
                    $location = $location->ID;
                }
            }
        } else {
            if ($location instanceof \WP_Post || $location instanceof CustomPost) {
                $location = $location->ID;
            }
        }

        if ( ! $item && ! $location) {
            throw new \Exception('item or location could not be found');
        }

        return self::prepareJsonResponse($startDate, $endDate, $location ? [$location] : [], $item ? [$item] : []);
    }

    /**
     * Returns json-formatted calendardata.
     * @throws \Exception
     */
    public static function getCalendarData()
    {
        $jsonResponse = self::getCalendarDataArray();

        header('Content-Type: application/json');
        echo json_encode($jsonResponse);
        wp_die(); // All ajax handlers die when finished
    }

    /**
     * Returns template data for frontend.
     *
     * @param \WP_Post|null $post
     *
     * @return array
     * @throws \Exception
     */
    public static function getTemplateData(\WP_Post $post = null)
    {
        if ($post == null) {
            global $post;
        }
        $location = $post;
        $item = get_query_var('item') ?: false;
        $items = \CommonsBooking\Repository\Item::getByLocation($location->ID, true);

        $args = [
            'post'          => $post,
            'wp_nonce'      => Timeframe::getWPNonceField(),
            'actionUrl'     => admin_url('admin.php'),
            'location'      => new \CommonsBooking\Model\Location($location),
            'postUrl'       => get_permalink($location),
            'type'          => Timeframe::BOOKING_ID,
            'calendar_data' => json_encode(self::getCalendarDataArray($item ?: null, $location))
        ];

        // If theres no item selected, we'll show all available.
        if ( ! $item) {
            if (count($items)) {
                // If there's only one item available, we'll show it directly.
                if (count($items) == 1) {
                    $args['item'] = array_values($items)[0];
                } else {
                    $args['items'] = $items;
                }
            }
        } else {
            $args['item'] = new \CommonsBooking\Model\Item(get_post($item));
        }

        return $args;
    }

    /**
     * cb_locations shortcode
     *
     * A list of locations with timeframes.
     *
     * @param $atts
     *
     * @return false|string
     * @throws \Exception
     */
    public static function shortcode($atts)
    {
        global $templateData;
        $templateData = [];
        $locations = [];
        $queryArgs = shortcode_atts(static::$allowedShortCodeArgs, $atts,
            \CommonsBooking\Wordpress\CustomPostType\Location::getPostType());

        if (is_array($atts) && array_key_exists('item-id', $atts)) {
            $location = \CommonsBooking\Repository\Location::getByItem($atts['item-id']);
            $locations[] = $location;
        } else {
            $locations = \CommonsBooking\Repository\Location::get($queryArgs);
        }

        $locationData = [];
        /** @var \CommonsBooking\Model\Location $location */
        foreach($locations as $location) {
            $shortCodeData = self::getShortcodeData($location, 'Item');

            // Sort by start_date
            uasort($shortCodeData, function ($a,$b) {
                return $a['start_date'] > $b['start_date'];
            });

            $locationData[$location->ID] = $shortCodeData;
        }

        ob_start();
        foreach ($locationData as $id => $data) {
            $templateData['location'] = $id;
            $templateData['data'] = $data;
            commonsbooking_get_template_part('shortcode', 'locations', true, false, false);
        }

        return ob_get_clean();
    }
}
