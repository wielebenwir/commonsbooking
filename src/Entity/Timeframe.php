<?php


namespace CommonsBooking\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class Timeframe
 *
 * @ORM\Entity(repositoryClass="CommonsBooking\Repository\TimeframeRepository")
 */
class Timeframe
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type;

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
     * @return Timeframe
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return Timeframe
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }



}
