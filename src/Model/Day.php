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

    public function getSlotStartTimestamp($slotNr) {
        $slot = $this->getSlot($slotNr);
        return intval(strtotime($this->getDate() . ' ' . $slot['timestart']));
    }

    public function getSlotEndTimestamp($slotNr) {
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
     * @param \DateTime $time
     * @param $grid
     *
     * @return float|int
     */
    protected function getSlotByTime(\DateTime $time, $grid, $timeframe, $type)
    {
        $hourSlots = $time->format('H') / $grid;
        $minuteSlots = $time->format('i') / 60 / $grid;

        $slot = $hourSlots + $minuteSlots;

        $multidayTimeframeTypes = Timeframe::$multiDayFrames;
        $multidayTimeframe = in_array(get_post_meta($timeframe->ID, 'type', true), $multidayTimeframeTypes);

        if ($multidayTimeframe) {

            // Check if Timeframe starts on another day before.
            if (
                $type == 'start' &&
                $time->getTimestamp() < $this->getDateObject()->setTime(0, 0)->getTimestamp()) {
                $slot = 0;
            }

            // Check if Timeframe ends on another day after.
            if (
                $type == 'end' &&
                $time->getTimestamp() > $this->getDateObject()->setTime(23, 59)->getTimestamp()) {
                $slot = (24 / $grid) - 1;
            }
        }

        return $slot;
    }

    /**
     * @param \DateTime $time
     * @param $grid
     * @param $timeframe
     *
     * @return float|int
     */
    protected function getStartSlot(\DateTime $time, $grid, $timeframe)
    {
        return $this->getSlotByTime($time, $grid, $timeframe, 'start');
    }

    /**
     * @param \DateTime $time
     * @param $grid
     * @param $timeframe
     *
     * @return float|int
     */
    protected function getEndSlot(\DateTime $time, $grid, $timeframe)
    {
        return $this->getSlotByTime($time, $grid, $timeframe, 'end');
    }

    /**
     * Returns minimal grid from list of timeframes.
     *
     * @param $timeframes
     *
     * @return bool|float
     */
    protected function getMinimalGridFromTimeframes($timeframes)
    {
        $grid = 24;
        // Get grid size from existing timeframes
        foreach ($timeframes as $timeframe) {
            $fullday = get_post_meta($timeframe->ID, 'full-day', true);
            $timeframeGrid = !$fullday ? intval($timeframe->grid) : 24;
            if ($timeframeGrid < $grid) {
                if (is_numeric($timeframeGrid) && $timeframeGrid > 0) {
                    $grid = $timeframeGrid;
                }
            }
        }

        return $grid;
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
            if(
                get_post_meta($timeframe->ID, 'timeframe-repetition', true) == "rep"
            ) {
                // Weekly Rep
                if(get_post_meta($timeframe->ID, 'repetition', true) == "w") {
                    $dayOfWeek = intval($this->getDateObject()->format('w'));
                    $timeframeWeekdays = get_post_meta($timeframe->ID, 'weekdays', true);

                    // Because of different day of week calculation we need to recalculate
                    if($dayOfWeek == 0) $dayOfWeek = 7;
                    if(is_array($timeframeWeekdays) && !in_array( $dayOfWeek, $timeframeWeekdays)) {
                        continue;
                    }
                }

                // Monthly Rep
                if(get_post_meta($timeframe->ID, 'repetition', true) == "m") {
                    $dayOfMonth = intval($this->getDateObject()->format('j'));
                    $timeframeStartDayOfMonth = $startDate->format('j');

                    if($dayOfMonth != $timeframeStartDayOfMonth) {
                        continue;
                    }
                }

                // Yearly Rep
                if(get_post_meta($timeframe->ID, 'repetition', true) == "y") {
                    $date = intval($this->getDateObject()->format('dm'));
                    $timeframeDate = $startDate->format('dm');
                    if($date != $timeframeDate) {
                        continue;
                    }
                }

            }

            // Slots
            $startSlot = $this->getStartSlot($startDate, $grid, $timeframe);
            $endSlot = $this->getEndSlot($endDate, $grid, $timeframe);

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
        foreach($slots as $slotNr => $slot) {
            if(!array_key_exists('timeframe', $slot) || !($slot['timeframe'] instanceof \WP_Post)) {
                unset($slots[$slotNr]);
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
        $grid = $this->getMinimalGridFromTimeframes($timeframes);

        $slotsPerDay = 24 / $grid;

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

    protected function getSlotTimestampStart($slotsPerDay, $slotNr) {
        return strtotime($this->getDate()) + ($slotNr * ((24 / $slotsPerDay) * 3600));
    }

    protected function getSlotTimestampEnd($slotsPerDay, $slotNr) {
        return strtotime($this->getDate()) + (($slotNr + 1) * ((24 / $slotsPerDay) * 3600)) - 1;
    }

}
