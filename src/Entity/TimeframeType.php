<?php


namespace CommonsBooking\Entity;

/**
 * Class TimeframeType
 * @package CommonsBooking\Entity
 * @ORM\Entity
 * @ORM\Table(name="cb_timeframe_type")
 */
class TimeframeType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * @return TimeframeType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }



}
