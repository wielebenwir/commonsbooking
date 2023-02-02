<?php

namespace CommonsBooking\Service;

use Closure;
use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Wordpress\Options\OptionsTab;
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
	 * @param Closure $validationFunction
	 * @param array $params
	 *
	 * @throws BookingRuleException
	 */
	public function __construct(String $name,String $title, String $description,String $errorMessage, Closure $validationFunction,array $params = []) {
		if (! empty($params) ){

			if (count($params) > 3 ){
				throw new BookingRuleException("No more than 3 parameters are currently supported");
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
		try {
			foreach ( self::init() as $bookingRule ) {
				$assoc_array[ $bookingRule->name ] = $bookingRule->getTitle();
			}
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}
		return $assoc_array;
	}

	/**
	 * Gets a string of all rule properties, so they can be displayed using CMB2
	 * @return string
	 */
	public static function getRulesJSON(): string {
		try {
			$ruleObjects = self::init();
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}

		if ( isset( $ruleObjects ) ) {
			return wp_json_encode(
				array_map(
					function( BookingRule $rule){
						return get_object_vars($rule);
					}, $ruleObjects )
			);
		}
		else {
			return "";
		}
	}

	/**
	 * Returns an array with the default ruleset applied, can be filtered using a filter hook
	 *
	 * Closure::fromCallable can be replaced with First Class Callable Syntax in PHP8.1
	 * @return array
	 * @throws BookingRuleException
	 */
	public static function init(): array {
		$defaultRuleSet = [
			new BookingRule(
				"noSimultaneousBooking",
				__("Forbid simultaneous Bookings",'commonsbooking'),
				__("Users can no longer book two items on the same day.",'commonsbooking'),
				__("You can not book more than one item at a time.",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkSimultaneousBookings'))
			),
			new BookingRule(
				"prohibitChainBooking",
				__("Prohibit chain-bookings",'commonsbooking'),
				__("Users can no longer work around the maximum booking limit by chaining two bookings directly after another.",'commonsbooking'),
				__("You have reached your booking limit. Please leave some time in between bookings.",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkChainBooking'))
			),
			new BookingRule(
				"maxBookingDays",
				__("Maximum of bookable days",'commonsbooking'),
				__("Allow x booked days over the period of y days for user.",'commonsbooking'),
				__("Too many booked days over the period of y days",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkMaxBookingDays')),
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
				function( Booking $booking,array $args = []):?array{
					return array($booking);
				}
			)
		];

		return apply_filters( COMMONSBOOKING_PLUGIN_SLUG . '_booking-rules',$defaultRuleSet);
	}

	/**
	 * Will check if there are booking at the same time as the defined booking.
	 * If the user has booking at the same day it will return an array with ust one conflicting booking
	 * If there is no booking at the same day, will return null
	 *
	 *
	 * @param Booking $booking
	 * @param array $args
	 * @param bool|array $appliedTerms
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public static function checkSimultaneousBookings( Booking $booking, array $args = [], $appliedTerms = false):?array {
		$userBookings = \CommonsBooking\Repository\Booking::getForUser($booking->getUserData(),true,time(),['confirmed']);
		if (empty($userBookings)){
			return null;
		}

		if ($appliedTerms){
			$userBookings = array_filter($userBookings,fn(Booking $b) => $b->termsApply($appliedTerms));
		}

		foreach ($userBookings as $userBooking){
			if ($booking->hasTimeframeDateOverlap($booking,$userBooking)){
				return array($userBooking);
			}
		}
		return null;
	}

	/**
	 * Will check if there are chained bookings for the current item that go over the total booking limit .
	 *
	 * If the user has chained too many days in that timespan will return the conflicting bookings
	 * If the user bookings are NOT above the limit, will return null
	 *
	 * @param Booking $booking
	 * @param array $args
	 * @param bool|array $appliedTerms
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public static function checkChainBooking( Booking $booking, array $args = [], $appliedTerms = false):?array{
		$timeframe = $booking->getBookableTimeFrame();
		$adjacentBookings = $booking->getAdjacentBookings();
		if ( empty($adjacentBookings))
		{
			return null;
		}
		$adjacentBookings = array_filter( $adjacentBookings,
			fn( Booking $adjacentBooking ) => $booking->getUserData()->ID == $adjacentBooking->getUserData()->ID
		);
		if ( empty($adjacentBookings) ){
			return null;
		}
		$bookingCollection = $booking->getBookingChain($booking->getUserData());
		//add our current booking to the collection
		$bookingCollection[] = $booking;
		uasort( $bookingCollection, function ( Booking $a, Booking $b ) {
			return $a->getStartDate() <=> $b->getStartDate();
		} );
		$collectionStartDate = reset( $bookingCollection)->getStartDateDateTime();
		$collectionEndDate   = end($bookingCollection)->getEndDateDateTime()->modify("+1 second");
		$collectionTotalDays  = $collectionStartDate->diff($collectionEndDate)->d;
		//checks if the collection of chained bookings ist still in the allowed limit
		$max_days = $timeframe->getMaxDays();
		if ( $collectionTotalDays <= $max_days ){
			return null;
		}
		else {
			//remove last element from collection again as it is the booking we are currently processing
			array_pop($bookingCollection);
			return $bookingCollection;
		}
	}


	/**
	 * Will check for a pre-defined maximum of x days in y timespan,
	 * If the user has booked too many days in that timespan will return the conflicting bookings
	 * If the user bookings are NOT above the limit, will return null
	 *
	 * @param Booking $booking
	 * @param array $args
	 * @param bool|array $appliedTerms
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function checkMaxBookingDays(Booking $booking, array $args, $appliedTerms = false): ?array {
		/*
		 * TODO:
		 * Diese Funktion ist vielleicht noch nicht so super durchdacht, sie
		 * holt sich immer nur die Tage, die genau in der Mitte des Zeitraums y liegen.
		 *  - Verlagern von y Tagen auf Mitte des Arrays
		 *  - sprintf aus dem Fehlernamen machen
		 */
		$allowedBookedDays = $args[0];
		$periodDays        = $args[1];
		//when the zeitraum is uneven
		$daysHalf = $periodDays / 2;
		if ( $periodDays % 2 ) {
			$daysLeft  = $daysHalf + 1;
			$daysRight = $daysHalf - 1;
		} else {
			$daysLeft = $daysRight = $daysHalf;
		}
		$rangeBookingsArray = array_merge(
			\CommonsBooking\Repository\Booking::getByTimerange(
				$booking->getStartDateDateTime()->modify( "-" . $daysLeft . " days" )->getTimestamp(),
				$booking->getStartDateDateTime()->getTimestamp(),
				null,
				null,
				[],
				[ 'confirmed' ]
			),
			\CommonsBooking\Repository\Booking::getByTimerange(
				$booking->getEndDateDateTime()->getTimestamp(),
				$booking->getEndDateDateTime()->modify( "+" . $daysRight . " days" )->getTimestamp(),
				null,
				null,
				[],
				[ 'confirmed' ]
			)
		);

		//filter out bookings that do not belong to the current user
		$rangeBookingsArray = array_filter( $rangeBookingsArray,
			fn( Booking $rangeBooking ) => $booking->getUserData()->ID == $rangeBooking->getUserData()->ID
		);

		//filter out bookings that are not in the current term
		if ($appliedTerms){
			$rangeBookingsArray = array_filter($rangeBookingsArray,
				fn( Booking $rangeBooking ) => $rangeBooking->termsApply($appliedTerms)
			);
		}

		$totalLengthDays    = 0;
		foreach ( $rangeBookingsArray as $rangeBooking ) {
			$totalLengthDays += $rangeBooking->getLength();
		}
		$totalLengthDays += $booking->getLength();

		if ($totalLengthDays > $allowedBookedDays){
			return $rangeBookingsArray;
		}
		else {
			return null;
		}
	}
}