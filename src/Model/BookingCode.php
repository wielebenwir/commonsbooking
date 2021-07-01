<?php


namespace CommonsBooking\Model;


class BookingCode {

	/**
	 * Error type id.
	 */
	public const ERROR_TYPE = "bookingCodeCreationError";

	/**
	 * Datestring
	 * @var string
	 */
	protected $date;

	/**
	 * Item ID
	 * @var int
	 */
	protected $item;

	/**
	 * Location ID
	 * @var int
	 */
	protected $location;

	/**
	 * Timeframe ID
	 * @var int
	 */
	protected $timeframe;

	/**
	 * Code
	 * @var string
	 */
	protected $code;

	/**
	 * BookingCode constructor.
	 *
	 * @param $date
	 * @param $item
	 * @param $location
	 * @param $timeframe
	 * @param $code
	 */
	public function __construct( $date, $item, $location, $timeframe, $code ) {
		$this->date      = $date;
		$this->item      = $item;
		$this->location  = $location;
		$this->timeframe = $timeframe;
		$this->code      = $code;
	}

	/**
	 * @return string
	 */
	public function getDate(): string {
		return $this->date;
	}

	/**
	 * @param mixed $date
	 *
	 * @return BookingCode
	 */
	public function setDate( $date ): BookingCode {
		$this->date = $date;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getItem(): int {
		return $this->item;
	}

	public function getItemName() {
		$post = get_post( $this->getItem() );

		return $post->post_title;
	}

	/**
	 * @param mixed $item
	 *
	 * @return BookingCode
	 */
	public function setItem( $item ): BookingCode {
		$this->item = $item;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLocation(): int {
		return $this->location;
	}

	/**
	 * @param mixed $location
	 *
	 * @return BookingCode
	 */
	public function setLocation( $location ): BookingCode {
		$this->location = $location;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTimeframe(): int {
		return $this->timeframe;
	}

	/**
	 * @param mixed $timeframe
	 *
	 * @return BookingCode
	 */
	public function setTimeframe( $timeframe ): BookingCode {
		$this->timeframe = $timeframe;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode(): string {
		return $this->code;
	}

	/**
	 * @param mixed $code
	 *
	 * @return BookingCode
	 */
	public function setCode( $code ): BookingCode {
		$this->code = $code;

		return $this;
	}

}
