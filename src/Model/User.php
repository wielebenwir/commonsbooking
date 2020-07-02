<?php

namespace CommonsBooking\Model;

use stdClass;
use WP_User;

class User
{    
    /**
     * __construct
     *
     * @param  mixed $user
     * @return void
     */
    public function __construct($cb_user)
    {
    
        $cb_user = new \WP_User($cb_user);
        
        if($cb_user instanceof \WP_User) {
            $this->cb_user = $cb_user;            
        } elseif (is_int($cb_user)) {
            $this->cb_user = get_user_by('id', $cb_user);
        } else {
            throw new \Exception("invalid user param. needed WP_User or User ID (int)");
        }

    }

    /**
     * Returns meta-field value.
     * @param $field
     *
     * @return mixed
     */
    public function get_meta($field) {
        return get_user_meta($this->cb_user->ID, $field, true);
    }

    // public function __get($name)
    // {
    //     if(property_exists($this->user, $name)) {
    //         return $this->user->$name;
    //     }
    // }

    public function user_address() {
        echo  "Hallo";
    }

}