<?php

namespace CommonsBooking\Service;

use Closure;
use Exception;

class BookingRule {
	protected string $name;
	protected string $title;
	protected string $description;
	protected string $errorMessage;
	protected array $params;
	protected Closure $validationFunction;

	/**
	 * The constructor for BookingRules before they are applied
	 *
	 * @param String $name
	 * @param String $title
	 * @param String $description
	 * @param String $errorMessage
	 * @param \Closure $validationFunction
	 * @param array $params
	 *
	 * @throws Exception
	 */
	public function __construct(String $name,String $title, String $description,String $errorMessage, Closure $validationFunction,array $params = []) {
		if (! empty($params) ){

			if (count($params) > 3 ){
				throw new Exception("No more than 3 parameters are currently supported");
			}

			$this->params = $params;
		}
		$this->name = $name;
		$this->title = $title;
		$this->description = $description;
		$this->errorMessage = $errorMessage;
		$this->validationFunction = $validationFunction;
	}

	/**
	 * Gets the sanitized version of the BookingRule title
	 * @return string
	 */
	public function getTitle(): string {
		return commonsbooking_sanitizeHTML($this->title);
	}

	/**
	 * Gets the sanitized version of the BookingRule description
	 * @return string
	 */
	public function getDescription(): string {
		return commonsbooking_sanitizeHTML($this->description);
	}

	/**
	 * Gets the sanitized version of the BookingRule error message
	 * @return string
	 */
	public function getErrorMessage(): string {
		return commonsbooking_sanitizeHTML($this->errorMessage);
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * @return Closure
	 */
	public function getValidationFunction(): Closure {
		return $this->validationFunction;
	}

	/**
	 * Create associative array for CMB2 select
	 * @return array
	 */
	public static function getRulesForSelect(): array {
		$assoc_array = [];
		foreach ( self::init() as $bookingRule) {
			$assoc_array[$bookingRule->name] = $bookingRule->getTitle();
		}

		return $assoc_array;
	}

	/**
	 * Gets a string of all rule properties, so they can be displayed using CMB2
	 * @return string
	 */
	public static function getRulesJSON(): string {
		return wp_json_encode(
			array_map(
				function( BookingRule $rule){
					return get_object_vars($rule);
				}, self::init() )
		);
	}

	/**
	 * Returns an array with the default ruleset applied, can be filtered using a filter hook
	 * @return array
	 * @throws Exception
	 */
	public static function init(): array {
		$defaultRuleSet = [
			new BookingRule(
				"noSimultaneousBooking",
				__("Forbid simultaneous Bookings",'commonsbooking'),
				__("Users can no longer book two items on the same day.",'commonsbooking'),
				__("You can not book more than one item at a time.",'commonsbooking'),
				function(\CommonsBooking\Model\Booking $booking):bool{
					/*
					 * TODO: Sollte bei Kategorieeinstellung nur auf Artikel derselben Kategorie angewendet werden
					 */
					$userBookings = \CommonsBooking\Repository\Booking::getForCurrentUser(true,time(),['confirmed']);
					if (empty($userBookings)){
						return true;
					}
					foreach ($userBookings as $userBooking){
						if ($booking->hasTimeframeDateOverlap($booking,$userBooking)){
							return false;
						}
					}
					return true;
				} ),
			new BookingRule(
				"prohibitChainBooking",
				__("Prohibit chain-bookings",'commonsbooking'),
				__("Users can no longer work around the maximum booking limit by chaining two bookings directly after another.",'commonsbooking'),
				__("You have reached your booking limit. Please leave some time in between bookings.",'commonsbooking'),
				function(\CommonsBooking\Model\Booking $booking):bool{
					//only applies if full day booking is enabled (for now) - can probably be removed
					$timeframe = $booking->getBookableTimeFrame();
					if (! $timeframe->isFullDay() ){
						return true;
					}
					$adjacentBookings = $booking->getAdjacentBookings();
					if ( empty($adjacentBookings))
					{
						return true;
					}
					$adjacentBookings = array_filter( $adjacentBookings,
						fn( \CommonsBooking\Model\Booking $adjacentBooking ) => $booking->getUserData()->ID == $adjacentBooking->getUserData()->ID
					);
					if ( empty($adjacentBookings) ){
						return true;
					}
					$bookingCollection = $booking->getBookingChain($booking->getUserData());
					//add our current booking to the collection
					$bookingCollection[] = $booking;
					uasort( $bookingCollection, function ( \CommonsBooking\Model\Booking $a, \CommonsBooking\Model\Booking $b ) {
						return $a->getStartDate() <=> $b->getStartDate();
					} );
					$collectionStartDate = reset( $bookingCollection)->getStartDateDateTime();
					$collectionEndDate   = end($bookingCollection)->getEndDateDateTime()->modify("+1 second");
					$collectionInterval  = $collectionStartDate->diff($collectionEndDate);
					//checks if the collection of chained bookings ist still in the allowed limit
					$max_days = $timeframe->getMaxDays();
					$d        = $collectionInterval->d;
					if ( $d <= $max_days ){
						return true;
					}
					else {
						return false;
					}
				} ),
			new BookingRule(
				"maxBookingDays",
				__("Maximum of bookable days",'commonsbooking'),
				__("Allow x booked days over the period of y days for user.",'commonsbooking'),
				__("Too many booked days over the period of y days",'commonsbooking'),
				function (\CommonsBooking\Model\Booking $booking,array $args):bool{
					/*
					 * TODO:
					 * Diese Funktion ist vielleicht noch nicht so super durchdacht, sie
					 * holt sich immer nur die Tage, die genau in der Mitte des Zeitraums y liegen.
					 *  - sprintf aus dem Fehlernamen machen
					 *  - Sollte bei Kategorieeinstellung nur auf Artikel derselben Kategorie angewendet werden
					 */
					$allowedBookedDays = $args[0];
					$periodDays = $args[1];
					//when the zeitraum is uneven
					$daysHalf = $periodDays / 2;
					if ( $periodDays % 2){
						$daysLeft = $daysHalf + 1;
						$daysRight = $daysHalf - 1;
					}
					else {
						$daysLeft = $daysRight = $daysHalf;
					}
					$rangeBookingsArray = array_merge(
						\CommonsBooking\Repository\Booking::getByTimerange(
							$booking->getStartDateDateTime()->modify("-" . $daysLeft . " days")->getTimestamp(),
							$booking->getStartDateDateTime()->getTimestamp(),
							null,
							null,
							[],
							['confirmed']
						),
						\CommonsBooking\Repository\Booking::getByTimerange(
							$booking->getEndDateDateTime()->getTimestamp(),
							$booking->getEndDateDateTime()->modify("+" . $daysRight . " days")->getTimestamp(),
							null,
							null,
							[],
							['confirmed']
						)
					);
					$rangeBookingsArray = array_filter( $rangeBookingsArray,
						fn( \CommonsBooking\Model\Booking $rangeBooking ) => $booking->getUserData()->ID == $rangeBooking->getUserData()->ID
					);
					$totalLengthDays = 0;
					foreach ($rangeBookingsArray as $rangeBooking){
						$totalLengthDays += $rangeBooking->getLength();
					}

					if ($totalLengthDays > $allowedBookedDays ){
						return false;
					}
					return true;
				},
				array(
					__("Allow x booked days",'commonsbooking'),
					__("In the period of y days",'commonsbooking')
				)
			),
			new BookingRule(
				__("FailRule",'commonsbooking'),
				__("Alwaysfailnoparam",'commonsbooking'),
				__("This is a rule without params that will always fail",'commonsbooking'),
				__("It has always failed alwaysfailnoparam",'commonsbooking'),
				function(\CommonsBooking\Model\Booking $booking,array $args = []):bool{
					return false;
				}
			)
		];

		return apply_filters( COMMONSBOOKING_PLUGIN_SLUG . '_booking-rules',$defaultRuleSet);
	}

}