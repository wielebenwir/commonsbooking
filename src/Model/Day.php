<?php

namespace CommonsBooking\Model;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Day
{

    protected $date;

    protected $locations;

    protected $items;

    protected $types;

    /**
     * Day constructor.
     *
     * @param $date
     * @param $locations
     * @param $items
     * @param $types
     */
    public function __construct($date, $locations = [], $items = [], $types = [])
    {
        $this->date = $date;
        $this->locations = array_map(function ($location) {
            return $location instanceof \WP_Post ? $location->ID : $location;
        }, $locations);
        $this->items = array_map(function ($item) {
            return $item instanceof \WP_Post ? $item->ID : $item;
        }, $items);

        $this->types = $types;
    }

    /**
     * @return mixed
     */
    public function getDayOfWeek()
    {
        return date('w', strtotime($this->getDate()));
    }

    public function getDateObject()
    {
        return new \DateTime($this->getDate());
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getFormattedDate($format)
    {
        return date($format, strtotime($this->getDate()));
    }

    public function getSlotStartTimestamp($slotNr)
    {
        $slot = $this->getSlot($slotNr);
        return intval(strtotime($this->getDate() . ' ' . $slot['timestart']));
    }

    public function getSlotEndTimestamp($slotNr)
    {
        $slot = $this->getSlot($slotNr);
        return intval(strtotime($this->getDate() . ' ' . $slot['timeend'])) - 1;
    }

    public function getFormattedSlotStartDate($format, $slotNr)
    {
        $time = $this->getSlotStartTimestamp($slotNr);
        return date($format, $time);
    }

    public function getFormattedSlotEndDate($format, $slotNr)
    {
        $time = $this->getSlotEndTimestamp($slotNr);
        return date($format, $time);
    }

    public function getSlot($slotNr)
    {
        $grid = $this->getGrid();

        if (count($grid) && !empty($grid[$slotNr])) {
            return $grid[$slotNr];
        } else {
            throw new \Exception(__CLASS__ . "::" . __FUNCTION__ . ": Invalid slot: " . $slotNr);
        }
    }

    /**
     * @param mixed $date
     *
     * @return Day
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getName()
    {
        return date('l', strtotime($this->getDate()));
    }

    /**
     * Returns grid of timeframes.
     * @return array
     * @throws \Exception
     */
    public function getGrid()
    {
        $timeFrames = \CommonsBooking\Repository\Timeframe::get(
            $this->locations,
            $this->items,
            $this->types,
            $this->getDate()
        );
        $slots = $this->getTimeframeSlots($timeFrames);

        return $slots;
    }

    /**
     * Returns the slot number for specific timeframe and time.
     *
     * @param \DateTime $date
     * @param $grid
     *
     * @return float|int
     */
    protected function getSlotByTime(\DateTime $date, $grid)
    {
        $hourSlots = $date->format('H') / $grid;
        $minuteSlots = $date->format('i') / 60 / $grid;

        $slot = $hourSlots + $minuteSlots;

        return $slot;
    }

    /**
     * Fills timeslots with timeframes.
     *
     * @param $slots
     * @param $timeframes
     *
     * @throws \Exception
     */
    protected function mapTimeFrames(&$slots, $timeframes)
    {
        $grid = 24 / count($slots);

        // Iterate through timeframes and fill slots
        foreach ($timeframes as $timeframe) {
            // Timeframe
            $startDateString = get_post_meta($timeframe->ID, 'start-date', true);
            $endDateString = get_post_meta($timeframe->ID, 'end-date', true);
            $startDate = new \DateTime();
            $startDate->setTimestamp($startDateString);
            $endDate = new \DateTime();
            $endDate->setTimestamp($endDateString);

            // Check for repetition timeframe selected days
            if (
                get_post_meta($timeframe->ID, 'timeframe-repetition', true) == "rep"
            ) {
                // Weekly Rep
                if (get_post_meta($timeframe->ID, 'repetition', true) == "w") {
                    $dayOfWeek = intval($this->getDateObject()->format('w'));
                    $timeframeWeekdays = get_post_meta($timeframe->ID, 'weekdays', true);

                    // Because of different day of week calculation we need to recalculate
                    if ($dayOfWeek == 0) $dayOfWeek = 7;
                    if (is_array($timeframeWeekdays) && !in_array($dayOfWeek, $timeframeWeekdays)) {
                        continue;
                    }
                }

                // Monthly Rep
                if (get_post_meta($timeframe->ID, 'repetition', true) == "m") {
                    $dayOfMonth = intval($this->getDateObject()->format('j'));
                    $timeframeStartDayOfMonth = $startDate->format('j');

                    if ($dayOfMonth != $timeframeStartDayOfMonth) {
                        continue;
                    }
                }

                // Yearly Rep
                if (get_post_meta($timeframe->ID, 'repetition', true) == "y") {
                    $date = intval($this->getDateObject()->format('dm'));
                    $timeframeDate = $startDate->format('dm');
                    if ($date != $timeframeDate) {
                        continue;
                    }
                }

            }

            // Slots
            $startSlot = $this->getSlotByTime($startDate, $grid);
            $endSlot = $this->getSlotByTime($endDate, $grid);

            // Add timeframe to relevant slots
            while ($startSlot < $endSlot) {
                if (!array_key_exists('timeframe', $slots[$startSlot]) || !$slots[$startSlot]['timeframe']) {
                    $timeframe->locked = Timeframe::isLocked($timeframe);
                    $slots[$startSlot]['timeframe'] = $timeframe;
                } else {
                    $slots[$startSlot]['timeframe'] = Timeframe::getHigherPrioFrame($timeframe, $slots[$startSlot]['timeframe']);
                }

                $startSlot++;
            }
        }

        // remove slots without timeframes
        foreach ($slots as $slotNr => $slot) {
            if (!array_key_exists('timeframe', $slot) || !($slot['timeframe'] instanceof \WP_Post)) {
                unset($slots[$slotNr]);
            }
        }

        // merge multiple slots if they are of same type
        foreach ($slots as $slotNr => $slot) {
            $slotBefore = $slots[$slotNr - 1];

            // If Slot before is of same timframe and we have no hourly grid, we merge them.
            if(
                $slotBefore &&
                $slotBefore['timeframe']->ID ==  $slot['timeframe']->ID &&
                get_post_meta($slot['timeframe']->ID, 'grid', true) == 0
            ) {
                $slots[$slotNr]['timestart'] = $slotBefore['timestart'];
                unset($slots[$slotNr - 1]['timeframe']);
            }
        }
    }

    /**
     * Returns array of timeslots filled with timeframes.
     *
     * @param $timeframes
     *
     * @return array
     * @throws \Exception
     */
    protected function getTimeframeSlots($timeframes)
    {
        $slots = [];
        $slotsPerDay = 24;

        // Init Slots
        for ($i = 0; $i < $slotsPerDay; $i++) {
            $slots[$i] = [
                'timestart' => date('H:i', $i * ((24 / $slotsPerDay) * 3600)),
                'timeend' => date('H:i', ($i + 1) * ((24 / $slotsPerDay) * 3600)),
                'timestampstart' => $this->getSlotTimestampStart($slotsPerDay, $i),
                'timestampend' => $this->getSlotTimestampEnd($slotsPerDay, $i)
            ];
        }

        $this->mapTimeFrames($slots, $timeframes);
        return $slots;
    }

    protected function getSlotTimestampStart($slotsPerDay, $slotNr)
    {
        return strtotime($this->getDate()) + ($slotNr * ((24 / $slotsPerDay) * 3600));
    }

    protected function getSlotTimestampEnd($slotsPerDay, $slotNr)
    {
        return strtotime($this->getDate()) + (($slotNr + 1) * ((24 / $slotsPerDay) * 3600)) - 1;
    }

}
