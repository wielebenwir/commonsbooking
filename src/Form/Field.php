<?php


namespace CommonsBooking\Form;


use CommonsBooking\View\Form;

class Field
{

    protected $name;

    protected $title;

    protected $description;

    protected $type;

    protected $capability;

    /**
     * Field constructor.
     *
     * @param $name
     * @param $title
     * @param $description
     * @param $type
     * @param $capability
     */
    public function __construct($name, $title, $description, $type, $capability)
    {
        $this->name = $name;
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->capability = $capability;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Field
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return Field
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return Field
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return Field
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * @param mixed $capability
     *
     * @return Field
     */
    public function setCapability($capability)
    {
        $this->capability = $capability;

        return $this;
    }

}
