<?php


namespace CommonsBooking\Model;

use WP_User;

class User extends WP_User
{
    public function __construct($user)
    {
        if($user instanceof \WP_User) {
            $this->user = $user;
        } elseif (is_int($user)) {
            $this->user = get_user_by('ID', $user);
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
        return get_user_meta($this->user->ID, $field, true);
    }

    public function __get($name)
    {
        if(property_exists($this->user, $name)) {
            return $this->post->$name;
        }
    }

}