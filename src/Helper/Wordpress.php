<?php

namespace CommonsBooking\Helper;

class Wordpress
{



    static function getPageListTitle() {

        $pages = \get_pages();

        foreach ($pages AS $key => $value ) {
            $pagelist[$value->ID] = $value->post_title;
        }

        return $pagelist;
    }    
}

?>