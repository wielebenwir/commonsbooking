<?php

namespace CommonsBooking\Helper;

class Wordpress
{

    /**
     * @return array
     */
    public static function getPageListTitle(): array
    {
        $pages = \get_pages();
        $pagelist = [];

        foreach ($pages as $key => $value) {
            $pagelist[$value->ID] = $value->post_title;
        }

        return $pagelist;
    }

}
