<?php

namespace CommonsBooking\Model;

class Week
{

    /**
     * @var integer
     */
    protected $year;

    /**
     * Week of year.
     * @var integer
     */
    protected $week;

    protected $locations;

    protected $items;

    protected $types;

    /**
     * Week constructor.
     *
     * @param null $year
     * @param $week
     * @param array $locations
     * @param array $items
     * @param array $types
     */
    public function __construct($year = null, $week, $locations = [], $items = [], $types = [])
    {
        if($year === null) $year = date('Y');
        $this->year = $year;
        $this->week = $week;
        $this->locations = $locations;
        $this->items = $items;
        $this->types = $types;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getDays() {
        $dto = new \DateTime();
        $dto->setISODate($this->getYear(), $this->getWeek());

        $days = [];
        for($i = 0; $i < 7; $i++) {
            $days[] = new Day($dto->format('Y-m-d'), $this->locations, $this->items, $this->types);
            $dto->modify('+1 day');
        }

        return $days;
    }

    /**
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @return mixed
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @param mixed $week
     *
     * @return Week
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

}
