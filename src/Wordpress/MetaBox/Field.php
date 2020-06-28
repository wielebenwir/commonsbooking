<?php


namespace CommonsBooking\Wordpress\MetaBox;

class Field
{

    protected $name;

    protected $title;

    protected $description;

    protected $type;

    protected $capability;

    /**
     * @var array
     */
    protected $options;

    /**
     * Field constructor.
     *
     * @param $name
     * @param $title
     * @param $description
     * @param $type
     * @param $capability
     * @param array $options
     */
    public function __construct($name, $title, $description, $type, $capability, array $options = [])
    {
        $this->name = $name;
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->capability = $capability;
        $this->options = $options;
    }

    public function getParamsArray() {
        $params = array(
            'name' => $this->getTitle(),
            'description' => $this->getDescription(),
            'id' => $this->getName(),
            'type' => $this->getType()
        );

        if($this->getType() == 'select') {
            $params['show_option_none'] = __('-- Please select --', CB_TEXTDOMAIN);
        }

        if(count($this->getOptions())) {
            foreach ($this->getOptions() as $key => $item) {

                if($item instanceof \WP_Post) {
                    $key = $item->ID;
                    $label = $item->post_title;
                } else {
                    $label = $item;
                }
                $params['options'][$key] = $label;
            }
        }

        return $params;
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getOptionFieldNames() {
        $fieldNames = [];
        foreach ($this->getOptions() as $key => $label) {
            $fieldNames[] = $this->getName() . "-" . $key;
        }
        return $fieldNames;
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

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return Field
     */
    public function setOptions(array $options): Field
    {
        $this->options = $options;

        return $this;
    }

}
