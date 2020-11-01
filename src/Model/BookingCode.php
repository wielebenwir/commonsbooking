<?php


namespace CommonsBooking\Model;


class BookingCode
{

    /**
     * Datestring
     * @var string
     */
    protected $date;

    /**
     * Item ID
     * @var int
     */
    protected $item;

    /**
     * Location ID
     * @var int
     */
    protected $location;

    /**
     * Timeframe ID
     * @var int
     */
    protected $timeframe;

    /**
     * Code
     * @var string
     */
    protected $code;

    /**
     * BookingCode constructor.
     *
     * @param $date
     * @param $item
     * @param $location
     * @param $timeframe
     * @param $code
     */
    public function __construct($date, $item, $location, $timeframe, $code)
    {
        $this->date = $date;
        $this->item = $item;
        $this->location = $location;
        $this->timeframe = $timeframe;
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     *
     * @return BookingCode
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    public function getItemName() {
        $post = get_post($this->getItem());
        return $post->post_title;
    }

    /**
     * @param mixed $item
     *
     * @return BookingCode
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     *
     * @return BookingCode
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimeframe()
    {
        return $this->timeframe;
    }

    /**
     * @param mixed $timeframe
     *
     * @return BookingCode
     */
    public function setTimeframe($timeframe)
    {
        $this->timeframe = $timeframe;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     *
     * @return BookingCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

}
