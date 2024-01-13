<?php


namespace CommonsBooking\Model;


/**
 * This is the data type for a single booking code.
 * It is generated by the BookingCodes repository.
 *
 * TODO: Remove setters and make the class immutable.
 *
 * @see \CommonsBooking\Repository\BookingCodes
 */
class BookingCode {

	/**
	 * Error type id.
	 */
	public const ERROR_TYPE = "bookingCodeCreationError";

	/**
	 * Datestring in the format Y-m-d
	 * @var string
	 */
	protected $date;

	/**
	 * Item ID
	 * @var int
	 */
	protected $item;

	/**
	 * Code string
	 * @var string
	 */
	protected $code;

	/**
	 * BookingCode constructor.
	 *
	 * @param $date
	 * @param $item
	 * @param $code
	 */
	public function __construct( $date, $item, $location, $timeframe, $code ) {
		$this->date      = $date;
		$this->item      = $item;
		$this->code      = $code;
	}

	/**
	 * @return string
	 */
	public function getDate(): string {
		return $this->date;
	}

	/**
	 * TODO: The setters should be obsolete because these values should not be changed after creation.
	 * CURRENTLY NOT USED
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
	 * TODO: The setters should be obsolete because these values should not be changed after creation.
	 * CURRENTLY NOT USED
	 * @param mixed $item
	 *
	 * @return BookingCode
	 */
	public function setItem( $item ): BookingCode {
		$this->item = $item;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCode(): string {
		return $this->code;
	}

	/**
	 * TODO: The setters should be obsolete because these values should not be changed after creation.
	 * CURRENTLY NOT USED
	 * @param mixed $code
	 *
	 * @return BookingCode
	 */
	public function setCode( $code ): BookingCode {
		$this->code = $code;

		return $this;
	}

}
