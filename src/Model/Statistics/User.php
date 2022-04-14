<?php


namespace CommonsBooking\Model\Statistics;

use DateInterval;
use DateTime;
use DatePeriod;

class User {

    private $booking;
    private $totalBookings = 0;
    private $bookingDays;

    function __construct()
    {
        //$this->booking = $booking;
    }

    public function addBooking($booking) {

        $this->totalBookings++;

        $bookingStartDate = date('Y-m-d', $booking->getStartDate());
        $this->bookingDays[$bookingStartDate] = 1;

    }

    public function getTotalBookings() {
        return $this->totalBookings;
    }

    public function getBookingsCountforTimerange($startDate, $endDate) {

        $daysCounter = 0;
        
        $begin = new DateTime(date('Y-m-d', $startDate));
        $end = new DateTime(date('Y-m-d', $endDate));
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($begin, $interval ,$end);

        var_dump($period);

        foreach ($period as $key => $value) {
            
            if (array_key_exists($value->format('Y-m-d'), $this->bookingDays)) {
                $daysCounter++;
            }     
        }

        return $daysCounter;

    }






}