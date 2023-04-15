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
	// Array where first element is the description of the parameter and second element is an associative array with the options
	protected array $selectParam;
	protected Closure $validationFunction;

	/**
	 * The constructor for BookingRules before they are applied
	 *
	 * @param   String   $name
	 * @param   String   $title
	 * @param   String   $description
	 * @param   String   $errorMessage
	 * @param   Closure  $validationFunction
	 * @param   array    $params
	 * @param   array    $selectParam
	 *
	 * @throws \CommonsBooking\Exception\BookingRuleException
	 */
	public function __construct(String $name,String $title, String $description,String $errorMessage, Closure $validationFunction,array $params = [], array $selectParam = []) {
		if (! empty($params) ){

			if (count($params) > 2 ){
				throw new BookingRuleException("No more than 2 parameters are currently supported");
			}

			$this->params = $params;
		}
		$this->name = $name;
		$this->title = $title;
		$this->description = $description;
		$this->errorMessage = $errorMessage;
		$this->validationFunction = $validationFunction;
		$this->selectParam = $selectParam;
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
			$ruleObjects = static::init();
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}

		if ( isset( $ruleObjects ) ) {
			return wp_json_encode(
				array_map(
					function( $rule){
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
				"maxBookingPerWeek",
				__( "Maximum booked days per week", 'commonsbooking'),
				__("Users are only allowed to book a limited amount of days per week.",'commonsbooking'),
				__("You have reached your booking limit. Please leave some time in between bookings.",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkMaxBookingsPerWeek')),
				array(
					__("Number of days each user is allowed to book per week",'commonsbooking'),
				),
				array(
					__("At what day of the week should the counter be reset?",'commonsbooking'),
					array(
						0 => __("Monday",'commonsbooking'),
						1 => __("Tuesday",'commonsbooking'),
						2 => __("Wednesday",'commonsbooking'),
						3 => __("Thursday",'commonsbooking'),
						4 => __("Friday",'commonsbooking'),
						5 => __("Saturday",'commonsbooking'),
						6 => __("Sunday",'commonsbooking')
					)
				)
			),
			new BookingRule(
				"maxBookingDays",
				__("Maximum of bookable days",'commonsbooking'),
				__("Allow x booked days over the period of y days for user.",'commonsbooking'),
				__("Too many booked days over the period of y days TODO",'commonsbooking'),
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
				function( Booking $booking,array $args = [], $appliedTerms = false):?array{
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
		$userBookings = Booking::filterTermsApply($userBookings,$appliedTerms);
		if (empty($userBookings)){
			return null;
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
		$bookingUser = $booking->getUserData();
		if ( empty($adjacentBookings))
		{
			return null;
		}
		$adjacentBookings = self::filterBookingsForTermsAndUser($adjacentBookings, $bookingUser, $appliedTerms);
		if ( empty($adjacentBookings) ){
			return null;
		}
		$bookingCollection = $booking->getBookingChain($bookingUser);
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
	 * Params: $args[0} = The amount of days the user is allowed to book
	 *         $args[1] = The period over which the user is allowed to book
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
			),
		);

		$rangeBookingsArray = self::filterBookingsForTermsAndUser($rangeBookingsArray, $booking->getUserData(), $appliedTerms);
		if (empty ($rangeBookingsArray)) {
			return null;
		}

		$totalLengthDays = Booking::getTotalLength($rangeBookingsArray) + $booking->getLength();

		if ($totalLengthDays > $allowedBookedDays){
			return $rangeBookingsArray;
		}
		else {
			return null;
		}
	}

	/**
	 * This rule will check if the user has exceeded their maximum booking allowance per week
	 * Will return the conflicting bookings if a user has too many in the week
	 *
	 * Params: $args[0] : The amount of days the user is allowed to book per week
	 *         $args[1]:  The day on which the counter is reset, default: 0 = monday
	 *                     1 = Tuesday, 2 = Wednesday, ..., 6 = sunday
	 *
	 *
	 * @param   \CommonsBooking\Model\Booking  $booking
	 * @param   array                          $args
	 * @param   bool|array                     $appliedTerms
	 *
	 * @return array|null
	 */
	public static function checkMaxBookingsPerWeek(Booking $booking, array $args, $appliedTerms = false): ?array {
		$allowedBookableDays = $args[0];
		$resetDay = $args[2];
		$resetDayString = 'monday';
		switch ($resetDay):
			case 0:
				$resetDayString = 'monday';
				break;
			case 1:
				$resetDayString = 'tuesday';
				break;
			case 2:
				$resetDayString = 'wednesday';
				break;
			case 3:
				$resetDayString = 'thursday';
				break;
			case 4:
				$resetDayString = 'friday';
				break;
			case 5:
				$resetDayString = 'saturday';
				break;
			case 6:
				$resetDayString = 'sunday';
				break;
		endswitch;
		$bookingDate = $booking->getStartDateDateTime();
		$startOfWeek = clone $bookingDate;
		$startOfWeek->modify('last ' . $resetDayString);
		$endOfWeek = clone $bookingDate;
		$endOfWeek->modify('next ' . $resetDayString);
		$rangeBookingsArray = \CommonsBooking\Repository\Booking::getByTimerange(
			$startOfWeek->getTimestamp(),
			$endOfWeek->getTimestamp(),
			null,
			null,
			[],
			[ 'confirmed' ]
		);
		$rangeBookingsArray = self::filterBookingsForTermsAndUser($rangeBookingsArray, $booking->getUserData(), $appliedTerms);
		if (empty ($rangeBookingsArray)) {
			return null;
		}
		$totalLength     = Booking::getTotalLength( $rangeBookingsArray );
		$length          = $booking->getLength();
		$totalLengthDays = $totalLength + $length;
		if ($totalLengthDays > $allowedBookableDays){
			return $rangeBookingsArray;
		}
		else {
			return null;
		}

	}

	/**
	 * This rule will check if the user has exceeded their maximum booking allowance per month
	 * Will return the conflicting bookings if a user has too many in the month
	 *
	 * Params: $args[0] : The amount of days the user is allowed to book per week
	 *         $args[1]:  The day on which the counter is reset, from 0 to max 31.
	 *
	 *
	 * @param   \CommonsBooking\Model\Booking  $booking
	 * @param   array                          $args
	 * @param   bool|array                     $appliedTerms
	 *
	 * @return array|null
	public static function checkMaxBookingsPerMonth(Booking $booking, array $args, $appliedTerms = false): ?array {
		$allowedBookableDays = $args[0];
		$resetDay = $args[1];


	}
	*/

	/**
	 * Will filter an array of bookings on the condition that they are from a specific user AND that the terms apply
	 * to the given booking.
	 *
	 * Is often used by BookingRule to determine if a booking should be taken into consideration
	 * @param   Booking[]     $bookings
	 * @param   \WP_User  $user
	 * @param             $terms
	 *
	 * @return array|null
	 */
	public static function filterBookingsForTermsAndUser(array $bookings, \WP_User $user, $terms ): ?array {
		$filteredTerms = Booking::filterTermsApply($bookings, $terms);
		if (! empty ($filteredTerms)) {
			return Booking::filterForUser($bookings, $user);
		}
		else {
			return null;
		}
	}
}