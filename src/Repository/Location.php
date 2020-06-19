<?php

namespace CommonsBooking\Repository;


class Location
{
    private $locationId;

    function __construct($locationId)
    {
        $this->locationId = $locationId;
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
     * TODO: check with markus (e.g. double methods in location and items etc.)
     * returns title 
     * @param $locationId
     * @return array
     */
    function name()
    {
        return get_the_title($this->locationId);
    }
}
