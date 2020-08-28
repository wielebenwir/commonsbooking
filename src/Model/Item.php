<?php


namespace CommonsBooking\Model;


use CommonsBooking\Repository\Timeframe;

class Item extends CustomPost
{
    /**
     * @return array
     * 
     * @TODO: should support $args 
     */
    public function getBookableTimeframes()
    {
        return Timeframe::get([], [$this->ID], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID], NULL, TRUE);
    }

    /**
     * Returns bookable timeframes for a specific location
     * @param $locationId
     *
     * @return array
     */
    public function getBookableTimeframesByLocation($locationId)
    {
        return Timeframe::get([$locationId], [$this->ID], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID]);
    }
}
