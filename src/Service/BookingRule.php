<?php

namespace CommonsBooking\Service;

use Closure;
use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\OptionsTab;
use DateTime;
use Exception;

class BookingRule {
	/**
	 * The name of the rule, needs to be unique and is used to identify the rule in the code
	 * @var String
	 */
	protected string $name;
	/**
	 * The title of the rule, will be shown to the configuration admin
	 * @var String
	 */
	protected string $title;
	/**
	 * A detailed description of the rule, will be shown to the configuration admin
	 * @var String
	 */
	protected string $description;
	/**
	 * The static error message that will be shown to the booking user if the rule is not met
	 * @var String
	 */
	protected string $errorMessage;
	/**
	 * Allows to set a custom error message that can be based on parameters (i.e. "You can only book for %s days")
	 * @var Closure
	 */
	protected ?Closure $errorFromArgs;
	/**
	 * Array of associative arrays in which the key "title" is the title of the parameter and "description" is the description of the parameter.
	 * These parameters are text fields that can be used to configure the rule. We can currently only support 2 parameters
	 * @var array
	 */
	protected array $params = [];
	/**
	 * Array where first element is the description of the select field and the second element is an associative array of the select options
	 * @var array
	 */
	protected array $selectParam;
	/**
	 * The function that will be called to validate the rule. This is a closure that takes a Booking object, the passed args and an array of the selected terms as arguments
	 * and returns either null if the rule is met or an array of bookings that are in conflict with the current booking.
	 * @var Closure
	 */
	protected Closure $validationFunction;

	/**
	 * The constructor for BookingRules before they are applied
	 *
	 * @param   String   $name The name of the rule, needs to be unique and is used to identify the rule in the code
	 * @param   String   $title The title of the rule, will be shown to the configuration admin
	 * @param   String   $description A detailed description of the rule, will be shown to the configuration admin
	 * @param   String   $errorMessage The static error message that will be shown to the booking user if the rule is not met
	 * @param   Closure  $validationFunction The function that will be called to validate the rule. This is a closure that takes a Booking object, the passed args and an array of the selected terms as arguments
	 * @param   array    $params Array of associative arrays in which the key "title" is the title of the parameter and "description" is the description of the parameter.
	 * @param   array    $selectParam Array where first element is the description of the select field and the second element is an associative array of the select options
	 *
	 * @throws BookingRuleException
	 */
	public function __construct(String $name,String $title, String $description,String $errorMessage, Closure $validationFunction,array $params = [], array $selectParam = [], ?Closure $errorFromArgs = null) {
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
		$this->errorFromArgs = $errorFromArgs;
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
	public function getErrorMessage($args = []): string {
		$errorMessage = commonsbooking_sanitizeHTML( $this->errorMessage );
		if ( $this->errorFromArgs !== null) {
			$errorMessageFunction = $this->errorFromArgs;
			$errorMessage .= PHP_EOL;
			$errorMessage .= $errorMessageFunction($args);
		}
		return $errorMessage;
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
					array(
						"title" => __("Maximum booked days per week",'commonsbooking'),
						"description" => __("Number of days each user is allowed to book per week",'commonsbooking'),
					)
				),
				array(
					__("At what day of the week should the counter be reset?",'commonsbooking'),
					array(
						0 => __("Sunday",'commonsbooking'),
						1 => __("Monday",'commonsbooking'),
						2 => __("Tuesday",'commonsbooking'),
						3 => __("Wednesday",'commonsbooking'),
						4 => __("Thursday",'commonsbooking'),
						5 => __("Friday",'commonsbooking'),
						6 => __("Saturday",'commonsbooking')
					)
				),
				Closure::fromCallable(array(self::class,'maxDaysWeekErrorMessage'))
			),
			new BookingRule(
				"maxBookingPerMonth",
				__("Maximum booked days per month",'commonsbooking'),
				__("Users are only allowed to book a limited amount of days per month.",'commonsbooking'),
				__("You have reached your booking limit. Please leave some time in between bookings.",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkMaxBookingsPerMonth')),
				array(
					array(
						"title" => __("Maximum booked days per month",'commonsbooking'),
						"description" => __("Number of days each user is allowed to book per month",'commonsbooking'),
					)
				),
				array(
					__("At what day of the month should the counter be reset?",'commonsbooking'),
					array(
						1 => __("1st",'commonsbooking'),
						2 => __("2nd",'commonsbooking'),
						3 => __("3rd",'commonsbooking'),
						4 => __("4th",'commonsbooking'),
						5 => __("5th",'commonsbooking'),
						6 => __("6th",'commonsbooking'),
						7 => __("7th",'commonsbooking'),
						8 => __("8th",'commonsbooking'),
						9 => __("9th",'commonsbooking'),
						10 => __("10th",'commonsbooking'),
						11 => __("11th",'commonsbooking'),
						12 => __("12th",'commonsbooking'),
						13 => __("13th",'commonsbooking'),
						14 => __("14th",'commonsbooking'),
						15 => __("15th",'commonsbooking'),
						16 => __("16th",'commonsbooking'),
						17 => __("17th",'commonsbooking'),
						18 => __("18th",'commonsbooking'),
						19 => __("19th",'commonsbooking'),
						20 => __("20th",'commonsbooking'),
						21 => __("21st",'commonsbooking'),
						22 => __("22nd",'commonsbooking'),
						23 => __("23rd",'commonsbooking'),
						24 => __("24th",'commonsbooking'),
						25 => __("25th",'commonsbooking'),
						26 => __("26th",'commonsbooking'),
						27 => __("27th",'commonsbooking'),
						28 => __("28th",'commonsbooking'),
						29 => __("29th",'commonsbooking'),
						30 => __("30th",'commonsbooking'),
						31 => __("31st",'commonsbooking'),
					)
				),
				Closure::fromCallable(array(self::class,'maxDaysMonthErrorMessage'))
			),
			new BookingRule(
				"maxBookingDays",
				__("Maximum of bookable days in time period",'commonsbooking'),
				__("Allow x booked days over the period of y days for user.",'commonsbooking'),
				__("Booking limit exceeded. ",'commonsbooking'),
				Closure::fromCallable(array(self::class,'checkMaxBookingDays')),
				array(
					array(
						"title"       => __("Allow x booked days",'commonsbooking'),
						"description" => __("How many days are free to book in the given period of days",'commonsbooking'),
					),
					array(
						"title"       => __("In the period of y days",'commonsbooking'),
						"description" => __("The length of the period for which the booking is limited. This period always lies in the middle, so if you define 30 days, the 15 days before and after will count towards the maximum quota.",'commonsbooking'),
					)
				),
				[],
				Closure::fromCallable(array(self::class,'maxBookingDaysErrorMessage'))
			),
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

		return array_filter($userBookings,function($userBooking) use ($booking){
			return $userBooking->hasTimeframeDateOverlap($booking);
		});
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
		if ($timeframe === null){
			return null;
		}
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
			//remove the checked unallowed booking from the collection
			return array_filter($bookingCollection, function (Booking $collectionItem) use ($booking){
				return $collectionItem->ID !== $booking->ID;
			});
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
		$startOfPeriod      = $booking->getStartDateDateTime()->modify( "-" . $daysLeft . " days" );
		$endOfPeriod          = $booking->getEndDateDateTime()->modify( "+" . $daysRight . " days" );

		return self::checkBookingRange($startOfPeriod, $endOfPeriod, $booking, $appliedTerms, $allowedBookedDays);
	}

	public static function maxBookingDaysErrorMessage( $args ){
		$allowedBookedDays = $args[0];
		$periodDays        = $args[1];
		return sprintf( __( 'You can only book %1$s days out of %2$s days. Please wait a while in-between bookings.', 'commonsbooking' ), $allowedBookedDays, $periodDays );
	}

	/**
	 * This rule will check if the user has exceeded their maximum booking allowance per week
	 * Will return the conflicting bookings if a user has too many in the week
	 *
	 * Params: $args[0] : The amount of days the user is allowed to book per week
	 * 	       $args[1] : Unused
	 *         $args[2]:  The day on which the counter is reset, default: 0 = monday
	 *                     1 = Tuesday, 2 = Wednesday, ..., 6 = sunday
	 *
	 *
	 * @param Booking $booking
	 * @param   array                          $args
	 * @param   bool|array                     $appliedTerms
	 *
	 * @return array|null
	 */
	public static function checkMaxBookingsPerWeek(Booking $booking, array $args, $appliedTerms = false): ?array {
		$allowedBookableDays = $args[0];
		$resetDay = $args[2];
		switch ($resetDay):
			case 0:
				$resetDayString = 'sunday';
				break;
			case 1:
				$resetDayString = 'monday';
				break;
			case 2:
				$resetDayString = 'tuesday';
				break;
			case 3:
				$resetDayString = 'wednesday';
				break;
			case 4:
				$resetDayString = 'thursday';
				break;
			case 5:
				$resetDayString = 'friday';
				break;
			case 6:
				$resetDayString = 'saturday';
				break;
			default:
				$resetDayString = 'monday';
				break;
		endswitch;
		$bookingDate = $booking->getStartDateDateTime();
		$startOfWeek = clone $bookingDate;
		$endOfWeek = clone $bookingDate;

		//check if the current day is the reset day
		if ($startOfWeek->format('w') == $resetDay) {
			//if so, we need to just need to add 7 days to the end of the week
			$endOfWeek->modify('+7 days');
		}
		else {
			$startOfWeek->modify('last ' . $resetDayString);
			$endOfWeek->modify('next ' . $resetDayString);
		}
		return self::checkBookingRange( $startOfWeek, $endOfWeek, $booking, $appliedTerms, $allowedBookableDays );

	}

	public static function maxDaysWeekErrorMessage(array $args): string {
		$maxDays = $args[0];
		$resetDay = $args[2];
		switch ($resetDay):
			case 0:
				$resetDayString = __('Sunday', 'commonsbooking');
				break;
			case 2:
				$resetDayString = __('Tuesday', 'commonsbooking');
				break;
			case 3:
				$resetDayString = __('Wednesday', 'commonsbooking');
				break;
			case 4:
				$resetDayString = __('Thursday', 'commonsbooking');
				break;
			case 5:
				$resetDayString = __('Friday', 'commonsbooking');
				break;
			case 6:
				$resetDayString = __('Saturday', 'commonsbooking');
				break;
			default:
				$resetDayString = __('Monday', 'commonsbooking');
				break;
		endswitch;
		return sprintf(__('You can only book %1$s days per week, starting on %2$s.', 'commonsbooking'), $maxDays, $resetDayString);
	}

	/**
	 * This rule will check if the user has exceeded their maximum booking allowance per month
	 * Will return the conflicting bookings if a user has too many in the month
	 *
	 * Params: $args[0] : The amount of days the user is allowed to book per week
	 * 	       $args[1] : Unused
	 *         $args[2]:  The day on which the counter is reset, from 0 to max 31.
	 *
	 *
	 * @param   Booking $booking
	 * @param   array                          $args
	 * @param   bool|array                     $appliedTerms
	 *
	 * @return array|null
	 */
	public static function checkMaxBookingsPerMonth(Booking $booking, array $args, $appliedTerms = false): ?array {
		$allowedBookableDays = $args[0];
		$resetDay = $args[2];
		$bookingDate = $booking->getStartDateDateTime();
		// if the reset day is higher than the current max day of the month, we need to adjust the reset day
		$maxDayOfMonth = $bookingDate->format('t');
		$resetDay = ($resetDay > $maxDayOfMonth) ? $maxDayOfMonth: $resetDay;


		//get the current month and year

		$day = $bookingDate->format('d');
		$month = $bookingDate->format('m');
		$year = $bookingDate->format('Y');

		// if the reset day is higher than the current day, we need to adjust the month and year
		$startDate = new DateTime($resetDay . '.' . $month . '.' . $year);
		$endDate = clone $startDate;
		if ($resetDay > $day){
			$startDate->modify('-1 month');
		}
		else {
			$endDate->modify('+1 month');
		}

		return self::checkBookingRange( $startDate, $endDate, $booking, $appliedTerms, $allowedBookableDays );

	}

	public static function maxDaysMonthErrorMessage(array $args): string {
		$maxDays = $args[0];
		$resetDay = $args[2];
		return sprintf(__('You can only book %1$s days per month, starting on the %2$s', 'commonsbooking'), $maxDays, $resetDay);
	}

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

	/**
	 * Will check if a range of bookings exceeds the allowed amount of days.
	 * Will also filter out bookings that are not in the same category as the booking.
	 * Will also filter out bookings that are not made by the same user as the booking.
	 * Will return the conflicting bookings if a user has too many in the range.
	 * Will also consider the setting if cancelled bookings should be considered.
	 *
	 * @param DateTime $startOfMonth
	 * @param DateTime $endOfMonth
	 * @param Booking $booking
	 * @param array|false $appliedTerms
	 * @param int $allowedBookableDays
	 *
	 * @return array|null - conflicting bookings in order of post_date
	 * @throws Exception
	 */
	private static function checkBookingRange( DateTime $startOfMonth, DateTime $endOfMonth, Booking $booking, $appliedTerms, int $allowedBookableDays ): ?array {
		$countedPostTypes        = [ 'confirmed' ];
		if (Settings::getOption('commonsbooking_options_restrictions','bookingrules-count-cancelled') == 'on') {
			$countedPostTypes[] = 'canceled';
		}
		$rangeBookingsArray = \CommonsBooking\Repository\Booking::getByTimerange(
			$startOfMonth->getTimestamp(),
			$endOfMonth->getTimestamp(),
			null,
			null,
			[
				// Ordered by post_date in ascending order
				'orderby' => 'date',
				'order'   => 'ASC',
			],
			$countedPostTypes
		);
		$rangeBookingsArray = self::filterBookingsForTermsAndUser( $rangeBookingsArray, $booking->getUserData(), $appliedTerms );
		if ( empty ( $rangeBookingsArray ) ) {
			return null;
		}
		$totalLength     = Booking::getTotalLength( $rangeBookingsArray );
		$length          = $booking->getLength();
		$totalLengthDays = $totalLength + $length;
		if ( $totalLengthDays > $allowedBookableDays ) {
			return $rangeBookingsArray;
		} else {
			return null;
		}
	}
}
