<?php

namespace CommonsBooking\Service;

use Closure;

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
	 */
	public function __construct(String $name,String $title, String $description,String $errorMessage, Closure $validationFunction,array $params = []) {
		if (! empty($params) ){

			if (count($params) > 3 ){
				throw new \InvalidArgumentException("No more than 3 parameters are currently supported");
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
	 * Gets the localized & sanitized version of the BookingRule name
	 * @return string
	 */
	public function getTitle(): string {
		return commonsbooking_sanitizeArrayorString(__($this->title,'commonsbooking'));
	}

	/**
	 * Gets the localized & sanitized version of the BookingRule description
	 * @return string
	 */
	public function getDescription(): string {
		return commonsbooking_sanitizeHTML(__($this->description,'commonsbooking'));
	}

	/**
	 * Gets the localized & sanitized version of the BookingRule error message
	 * @return string
	 */
	public function getErrorMessage(): string {
		return commonsbooking_sanitizeHTML(__($this->errorMessage));
	}

	/**
	 * Create associative array for CMB2 select
	 * @return array
	 */
	public static function getRulesForSelect(): array {
		$assoc_array = [];
		foreach ( self::init() as $bookingRule) {
			$assoc_array[$bookingRule->name] = $bookingRule->title;
		}

		return $assoc_array;
	}

	/**
	 * Gets a string of all rule properties, so they can be displayed using CMB2
	 * TODO: Fix localization problems
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
	 */
	public static function init(): array {
		$defaultRuleSet = [
			new BookingRule(
				"noSimultaneousBooking",
				"Forbid simultaneous Bookings",
				"Users can no longer book two items on the same day.",
				"You can not book more than one item at a time.",
				function(\CommonsBooking\Model\Booking $booking):bool{
					$user = $booking->getPost()->post_author;
					$userBookings = \CommonsBooking\Repository\Booking::getForUser($user);
					foreach ($userBookings as $userBooking){
						if ($booking->hasTimeframeDateOverlap($booking,$userBooking)){
							return false;
						}
					}
					return true;
				} ),

			new BookingRule(
				"TestRule",
				"Testing these rulesets with 2 params, this rule will always fail",
				"This is our description",
				"This is our error message, it will always appear",
				function (\CommonsBooking\Model\Booking $booking,array $args):bool{
					//$args[]
					return false;
				},
				array(
					"This is the description for the first parameter",
					"This is the description for the second parameter"
				)
			),
			new BookingRule(
				"FailRule",
				"Alwaysfailnoparam",
				"This is a rule without params that will always fail",
				"It has always failed alwaysfailnoparam",
				function(\CommonsBooking\Model\Booking $booking,array $args = []):bool{
					return false;
				}
			)
		];

		return apply_filters( COMMONSBOOKING_PLUGIN_SLUG . '_booking-rules',$defaultRuleSet);
	}

}