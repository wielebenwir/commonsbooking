<?php


namespace CommonsBooking\PostType;


abstract class PostType
{

    abstract public function getPostType();

    abstract public function getArgs();

    public function getMenuParams()
    {
        return [
            'cb-dashboard',
            $this->getArgs()['labels']['name'],
            $this->getArgs()['labels']['name'],
            'manage_options',
            'edit.php?post_type=' . $this->getPostType()
        ];
    }

}
