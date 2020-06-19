<?php

namespace CommonsBooking\Repository;


class Location
{
    private $location;
    private $locationId;

    function __construct($locationId)
    {
        $this->locationId = $locationId;
        $this->name = "Test";
    }


    /**
     * Returns array with location data (post and meta).
     * @param $locationId
     * @return array
     */
    function getLocationbyId()
    {

        $this->location = get_post($this->locationId);
        return $this->location;
    }

    /**
     * returns title 
     * @param $locationId
     * @return array
     */
    function name()
    {
        return get_the_title($this->locationId);
    }
}
