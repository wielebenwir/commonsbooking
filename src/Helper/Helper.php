<?php

namespace CommonsBooking\Helper;

Class Helper
{
    
    /**
     * generates a random string as hash
     *
     * @param  mixed $length
     * @return void
     */
    public static function generateRandomString($length='24') {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}