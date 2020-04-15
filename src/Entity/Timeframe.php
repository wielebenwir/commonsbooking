<?php


namespace CommonsBooking\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Class Timeframe
 *
 * @ORM\Entity
 * @ORM\Table(name="cb_timeframe")
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
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $locationId;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $itemId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $grid;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type;

    private $repetition;

    private $weekdays;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $userId;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }






}
