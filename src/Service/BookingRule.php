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
				"TestRule",
				__("Testing these rulesets with 2 params, this rule will always fail",'commonsbooking'),
				__("This is our description",'commonsbooking'),
				__("This is our error message, it will always appear",'commonsbooking'),
				function (\CommonsBooking\Model\Booking $booking,array $args):bool{
					//$args[]
					return false;
				},
				array(
					__("This is the description for the first parameter",'commonsbooking'),
					__("This is the description for the second parameter",'commonsbooking')
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