<?php

namespace CommonsBooking\Model;

class Calendar
{

    /**
     * @var Day
     */
    protected $startDate;

    /**
     * @var Day
     */
    protected $endDate;

    protected $items;

    protected $locations;

    protected $types;

    protected $weeks;

    /**
     * Calendar constructor.
     *
     * @param $startDate
     * @param $endDate
     * @param array $locations
     * @param array $items
     * @param array $types
     */
    public function __construct($startDate, $endDate, $locations = [], $items = [], $types = [])
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->items = $items;
        $this->locations = $locations;
        $this->types = $types;
    }


    public function getWeeks()
    {
        $weeks = [];
        $firstWeek = date('W', strtotime($this->startDate->getDate()));
        $lastWeek = date('W', strtotime($this->endDate->getDate()));

        $weeks[] = new Week($firstWeek, $this->locations, $this->items, $this->types);
        if ($lastWeek > $firstWeek) {
            while ($firstWeek < $lastWeek) {
                $weeks[] = new Week(++$firstWeek, $this->locations, $this->items, $this->types);
            }
        }

        return $weeks;
    }


}
