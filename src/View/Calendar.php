<?php


namespace CommonsBooking\View;


use CommonsBooking\CB\CB;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Calendar
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
        if($jsonResponse = Plugin::getCacheItem()) {
            return $jsonResponse;
        } else {
            $calendar = new \CommonsBooking\Model\Calendar(
                $startDate,
                $endDate,
                $locations,
                $items
            );

            $jsonResponse = [
                'minDate'             => $startDate->getFormattedDate('Y-m-d'),
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
                        'repair'             => true,
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
                        $dayArray['repair'] = false;
                        $dayArray['bookedDay'] = false;
                    } else if (count($dayArray['slots']) === 1) {
                        $timeframe = $dayArray['slots'][0]['timeframe'];
                        $dayArray['fullDay'] = get_post_meta($timeframe->ID, 'full-day', true) == "on";
                    }

                    // Add day to calendar data.
                    $jsonResponse['days'][$day->getFormattedDate('Y-m-d')] = $dayArray;

                    if ($dayArray['locked'] || $allLocked) {
                        if ($allLocked) {
                            if ($dayArray['holiday']) {
                                $jsonResponse['holidays'][] = $day->getFormattedDate('Y-m-d');
                                // if all slots are booked or we have a changed timeframe, where a booking was done before change
                            } elseif ($dayArray['bookedDay'] || $dayArray['partiallyBookedDay']) {
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

            Plugin::setCacheItem($jsonResponse);
            return $jsonResponse;
        }
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

            // Add slot to array
            $dayArray['slots'][] = $slot;

            // Remove holiday flag, if there is at least one slot that isn't of type holiday
            if ( ! in_array($timeFrameType, [Timeframe::HOLIDAYS_ID, Timeframe::OFF_HOLIDAYS_ID])) {
                $dayArray['holiday'] = false;
            }

            // Remove repair flag, if there is at least one slot that isn't of type repair
            if ( $timeFrameType !== Timeframe::REPAIR_ID ) {
                $dayArray['repair'] = false;
            }

            // Remove bookedDay flag, if there is at least one slot that isn't of type bookedDay
            if ( ! in_array($timeFrameType, [Timeframe::BOOKING_ID, Timeframe::REPAIR_ID])) {
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
        $startDateString = date('Y-m-d', strtotime('first day of this month', time()));
        $endDateString = date('Y-m-d', strtotime('+3 months', time()));

        $gotStartDate = false;
        if(array_key_exists('sd', $_POST)) {
            $gotStartDate = true;
            $startDateString = sanitize_text_field($_POST['sd']);
        }
        $startDate = new Day($startDateString);

        $gotEndDate = false;
        if(array_key_exists('ed', $_POST)) {
            $gotEndDate = true;
            $endDateString = sanitize_text_field($_POST['ed']);
        }
        $endDate = new Day($endDateString);

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

        if($item && $location) {
            $bookableTimeframes = \CommonsBooking\Repository\Timeframe::get(
                [$location],
                [$item],
                [Timeframe::BOOKABLE_ID],
                null,
                true
            );

            if(count($bookableTimeframes)) {
                /** @var \CommonsBooking\Model\Timeframe $firstBookableTimeframe */
                $firstBookableTimeframe = array_pop($bookableTimeframes);

                // Check if start-/enddate was requested, then don't change it
                // otherwise start with first bookable month
                if(!($gotStartDate || $gotEndDate)) {
                    $startDateTimestamp = $firstBookableTimeframe->getStartDate();
                    if(!$gotStartDate)
                        $startDate = new Day(date('Y-m-d', $startDateTimestamp));

                    if(!$gotEndDate)
                        $endDate = new Day(date('Y-m-d', strtotime('+3 months', $startDateTimestamp)));
                }
            }
        }

        return self::prepareJsonResponse($startDate, $endDate, $location ? [$location] : [], $item ? [$item] : []);
    }

}
