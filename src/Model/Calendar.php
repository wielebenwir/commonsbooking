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

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $locations;

    /**
     * @var array
     */
    protected $types;

    /**
     * @var
     */
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

    /**
     * Returns weeks for calendar time range.
     * @return array
     */
    public function getWeeks()
    {
        $weeks = [];
        $startDate = strtotime($this->startDate->getDate());
        $endDate = strtotime($this->endDate->getDate());

        while($startDate <= $endDate) {
            $weeks[] = new Week(date('Y', $startDate), date('W', $startDate), $this->locations, $this->items, $this->types);
            $startDate = strtotime("next monday", $startDate);
        }

        return $weeks;
    }


}
