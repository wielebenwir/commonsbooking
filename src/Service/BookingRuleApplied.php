<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\OptionsTab;

/**
 * Represents a valid configuration of a {@see BookingRule}, which can be applied to bookings.
 * Instances of BookingRule's are saved when configured via admin backend access.
 *
 * It extends BookingRule to also hold the configured parameters for the rule, the categories it applies to and
 * the roles it is exempt from. (Configured when setting up a rule from the backend)
 */
class BookingRuleApplied extends BookingRule {

	private bool $appliesToAll;
	private array $appliedTerms;
	private array $appliedParams;
	/**
	 * @var int|string
	 */
	private $appliedSelectParam;
	private array $excludedRoles;

	/**
	 * Will construct a BookingRuleApplied object from an existing BookingRule.
	 * @param BookingRule $rule
	 *
	 * @throws BookingRuleException
	 */
	public function __construct( BookingRule $rule) {
		parent::__construct(
			$rule->name,
			$rule->title,
			$rule->description,
			$rule->errorMessage,
			$rule->validationFunction,
			$rule->params ?? [],
			$rule->selectParam ?? [],
			$rule->errorFromArgs ?? null
		);
	}

	/**
	 * Will set what this Booking Rule applies to, either needs to be all or at least one category
	 *
	 * @param   bool   $appliesToAll
	 * @param   array  $appliedTerms
	 *
	 * @throws BookingRuleException
	 */
	public function setAppliesToWhat(bool $appliesToAll, array $appliedTerms = []): void {
		if (! $appliesToAll && empty($appliedTerms)){
			throw new BookingRuleException(__("You need to specify a category, if the rule does not apply to all items", 'commonsbooking'));
		}
		$this->appliesToAll = $appliesToAll;
		$this->appliedTerms = $appliedTerms;
	}

	/**
	 * Will set the necessary params for the BookingRule to work
	 *
	 * @param   array       $paramsToSet needs to be numeric
	 * @param   int|string  $selectParam needs to be numeric
	 *
	 * @throws BookingRuleException - if not enough params were specified for the BookingRule
	 */
	public function setAppliedParams( array $paramsToSet, $selectParam ): void {
		if (! empty($this->params)){
			if (count($this->params) == count($paramsToSet) ){
				$this->appliedParams = $paramsToSet;
			}
			else {
				throw new BookingRuleException(__("Booking rules: Not enough parameters specified.", 'commonsbooking'));
			}
			foreach ( $paramsToSet as $param ) {
				if ( ! is_numeric( $param ) ) {
					throw new BookingRuleException( __( "Booking rules: Parameters need to be a number.", 'commonsbooking' ) );
				}
			}
		}
		if (! empty($this->selectParam)){
			if ( empty ( $selectParam ) || ! is_numeric($selectParam)){
				throw new BookingRuleException(__("Booking rules: Select parameter has not been properly set.", 'commonsbooking'));
			}
 			$this->appliedSelectParam = $selectParam;
		}
	}

	/**
	 * Sets the roles that the rule will not apply to
	 *
	 * @param   array  $excludedRoles
	 *
	 */
	public function setExcludedRoles( array $excludedRoles ): void {
		$this->excludedRoles = $excludedRoles;
	}

	/**
	 * checkBookingRulesCompliance takes in a booking object and checks if it complies with the rules.
	 * If the booking complies with all the rules, an empty array will be returned.
	 * If the booking violates any of the rules, an array of conflicting bookings will be returned.
	 *
	 * @param Booking $booking - The booking object to check for rule compliance
	 *
	 * @return array|null - An array of conflicting bookings or an empty array if the booking complies with all rules
	 */
	public function checkBookingCompliance( Booking $booking ): ?array {
		if ($booking->isUserPrivileged()){
			return null;
		}

		// Check if a rule is excluded for the user because of their role
		if (isset ($this->excludedRoles)){
			if (
				UserRepository::userHasRoles(
					$booking->getUserData()->ID,
					$this->excludedRoles
				)
			){
				return null;
			}
		}

		if (! $this->appliesToAll && ! $booking->termsApply($this->appliedTerms)){
			return null;
		}

		$validationFunction = $this->validationFunction;
		return $validationFunction( $booking, $this->getArgs(), $this->appliesToAll ? false : $this->appliedTerms );
	}

	/**
	 * Checks if a booking conforms to the rule sets, will always allow bookings from item/location admins & administrators
	 *
	 * @param Booking $booking
	 *
	 * @return void
	 * @throws BookingDeniedException
	 */
	public static function bookingConformsToRules( Booking $booking):void {
		try {
			$ruleset = self::init();
		} catch ( BookingRuleException $e ) {
			//booking always conforms to rules if ruleset is not available / invalid
			return;
		}

		if($booking->isUserPrivileged()){
			return;
		}

		foreach ( $ruleset as $rule ) {

			if ( ! ($rule instanceof BookingRuleApplied )) {
				continue; //skip invalid rules during booking validation
			}
			$conflictingBookings = $rule->checkBookingCompliance( $booking );
			if ( $conflictingBookings ){
				$errorMessage =
					$rule->getErrorMessage($rule->getArgs()) .
					PHP_EOL .
					__( "This affects the following bookings:", 'commonsbooking' ) .
					PHP_EOL
				;
				/** @var Booking $conflictingBooking */
				foreach ($conflictingBookings as $conflictingBooking){
					$errorMessage .= $conflictingBooking->bookingLink(
						sprintf(
							'%1s - %2s | %3s @ %4s',
							$conflictingBooking->pickupDatetime(),
							$conflictingBooking->returnDatetime(),
							$conflictingBooking->getItem()->post_title,
							$conflictingBooking->getLocation()->post_title
						) . PHP_EOL
					);
				}
				throw new BookingDeniedException( $errorMessage );
			}
		}
	}

	/**
	 * Gets a string of all rule properties, so they can be displayed using CMB2
	 *
	 * Will ignore errors, so that the settings page can still display the selected values even if they are invalid
	 * @return string
	 */
	public static function getRulesJSON(): string {
		$ruleObjects = static::init( true );

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
	 * Tries to create objects for all applied Booking rules from the settings
	 *
	 * @param bool $ignoreErrors - When this variable is set, the objects are created no matter what. This is used for the settings page to still get the selected values even if they are invalid.
	 *
	 * @return BookingRuleApplied[]
	 *
	 * @throws BookingRuleException
	 * @OVERRIDE
	 */
	public static function init( bool $ignoreErrors = false ):array{
		$validRules = parent::init();
		$rulesConfig = Settings::getOption('commonsbooking_options_restrictions', 'rules_group');
		$appliedRules = [];

		if (!is_array($rulesConfig)) {
			if ($ignoreErrors){
				return [];
			}
			throw new BookingRuleException('No valid booking rules found');
		}

		foreach ($rulesConfig as $ruleConfig) {
			/** @var BookingRule $validRule */
			foreach ($validRules as $validRule){
				if ( ! isset( $ruleConfig['rule-type'] ) ) {
					if ($ignoreErrors){
                        continue;
                    }
					throw new BookingRuleException( __( 'Booking rules: No rule type specified.', 'commonsbooking' ) );
				}
				if ($validRule->name !== $ruleConfig['rule-type']) {
					continue;
				}

				$ruleParams = [];
 				if (
				    ! empty($ruleConfig['rule-param1']) &&
					 count($validRule->params) >= 1)
				 {
					 $ruleParams[] = $ruleConfig['rule-param1'];
				 }
				if (
					! empty($ruleConfig['rule-param2']) &&
					count($validRule->params) >= 2
					)
				{
					$ruleParams[] = $ruleConfig['rule-param2'];
				}

				if (! empty ( $ruleConfig['rule-select-param'] )) { $selectParam = $ruleConfig['rule-select-param']; }

				if (! empty ( $ruleConfig['rule-applies-all'] ) && $ruleConfig['rule-applies-all'] === 'on'){
					$appliesToAll = true;
				}

				if ( ! empty ( $ruleConfig['rule-applies-categories'] ) ){
					$appliedTerms = $ruleConfig['rule-applies-categories'];
				}

				$ruleExemptRoles = empty($ruleConfig['rule-exempt-roles']) ? null : $ruleConfig['rule-exempt-roles'];

				$bookingRule = new self($validRule);
				try {
					$bookingRule->setAppliesToWhat(
						$appliesToAll ?? false,
						$appliedTerms ?? []
					);
				} catch ( BookingRuleException $e ) {
					if ( $ignoreErrors ) {
						continue;
					}
					else {
						throw $e;
					}
				}
				try {
					$bookingRule->setAppliedParams(
						$ruleParams ?? [],
						$selectParam ?? null
					);
				} catch ( BookingRuleException $e ) {
					if ( $ignoreErrors ) {
						continue;
					}
					else {
						throw $e;
					}
				}
				$bookingRule->setExcludedRoles(
					$ruleExemptRoles ?? []);
				$appliedRules[] = $bookingRule;

				}
			}

		return $appliedRules;
	}

	/**
	 * Checks if it can create all the rules, sets an error transient if it can't
	 * @return void
	 */
	public static function validateRules():void{

		if ( self::hasDefaultSettings() ) {
			return;
		}
		try {
			self::init();
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}
	}

	/**
	 * Will get the args array that belongs to the rule
	 * @return null[]
	 */
	private function getArgs(): array {
		$args = $this->appliedParams ?? [ null, null ];
		//add null value to array to keep the params in the right order
		if ( count( $args ) == 1 ) {
			$args[] = null;
		}
		if ( ! empty ( $this->appliedSelectParam ) ) {
			$args[] = $this->appliedSelectParam;
		} else {
			$args[] = null;
		}

		return $args;
	}
}