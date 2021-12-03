<?php


namespace CommonsBooking\API;


class Share
{
    private $name;

    private $enabled;

    private $pushUrl;

    private $key;

    private $owner;

    /**
     * Shares constructor.
     * @param $name
     * @param $enabled
     * @param $pushUrl
     * @param $key
     * @param $owner
     */
    public function __construct($name, $enabled, $pushUrl, $key, $owner)
    {
        $this->name = $name;
        $this->enabled = $enabled == 'on';
        $this->pushUrl = $pushUrl;
        $this->key = $key;
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getPushUrl()
    {
        return $this->pushUrl;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

}