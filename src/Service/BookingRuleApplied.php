<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\OptionsTab;

/**
 *
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
	 * @param   \CommonsBooking\Service\BookingRule  $rule
	 *
	 * @throws \CommonsBooking\Exception\BookingRuleException
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
		);
	}

	/**
	 * Will set who this Booking Rule applies to, either needs to be all or at least one category
	 *
	 *
	 * @param   bool   $appliesToAll
	 * @param   array  $appliedTerms
	 *
	 * @throws \CommonsBooking\Exception\BookingRuleException
	 */
	public function setAppliesToWho(bool $appliesToAll, array $appliedTerms = []): void {
		if (! $appliesToAll){
			$this->appliesToAll = false;
			if (empty($appliedTerms)){
				throw new BookingRuleException(__("You need to specify a category, if the rule does not apply to all items", 'commonsbooking'));
			}
			$this->appliedTerms = $appliedTerms;
		}
		else {
			$this->appliesToAll = true;
		}
	}

	/**
	 * Will set the necessary params for the BookingRule to work
	 *
	 * @param   array       $paramsToSet
	 * @param   int|string  $selectParamSet
	 *
	 * @throws \CommonsBooking\Exception\BookingRuleException - if not enough params were specified for the BookingRule
	 */
	public function setAppliedParams( array $paramsToSet, $selectParamSet ): void {
		if (! empty($this->params)){
			if (count($this->params) == count($paramsToSet) ){
				$this->appliedParams = $paramsToSet;
			}
			else {
				throw new BookingRuleException(__("Booking rules: Not enough parameters specified.", 'commonsbooking'));
			}
		}
		if (! empty($this->selectParam)){
			$this->appliedSelectParam = $selectParamSet;
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

		if (! $this->appliesToAll){
			if (! $booking->termsApply($this->appliedTerms) ){
				return null;
			}
		}

		$validationFunction = $this->validationFunction;

		//construct the args array
		$args = $this->appliedSelectParam ?? [null,null];
		//add null value to array to keep the params in the right order
		if (count($args) == 1) {
			$args[] = null;
		}
		if (! empty ($this->appliedSelectParam)){
			$args[] = $this->appliedSelectParam;
		}
		else {
			$args[] = null;
		}
		return $validationFunction( $booking, $args, $this->appliesToAll ? false : $this->appliedTerms );
	}

	/**
	 * Checks if a booking conforms to the rule sets, will always allow bookings from item/location admins & administrators
	 *
	 * @param Booking $booking
	 *
	 * @return void
	 * @throws BookingDeniedException|BookingRuleException
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

		/** @var BookingRuleApplied $rule */
		foreach ( $ruleset as $rule ) {

			// Check if a rule is excluded for the user because of their role
			if ($rule->excludedRoles){
				if (
					 UserRepository::userHasRoles(
						$booking->getUserData()->ID,
						$rule->excludedRoles
					)){
						continue;
					}
			}

			if ( ! ($rule instanceof BookingRuleApplied )) {
				throw new BookingRuleException( "Value must be a BookingRuleApplied" );
			}
			$conflictingBookings = $rule->checkBookingCompliance( $booking );
			if ( $conflictingBookings ){
				$errorMessage =
					$rule->getErrorMessage() .
					PHP_EOL .
					__( "This affects the following bookings:", 'commonsbooking' ) .
					PHP_EOL
				;
				/** @var Booking $conflictingBooking */
				foreach ($conflictingBookings as $conflictingBooking){
					$errorMessage .= sprintf(
						'%1s - %2s | %3s @ %4s',
						$conflictingBooking->pickupDatetime(),
						$conflictingBooking->returnDatetime(),
						$conflictingBooking->getItem()->post_title,
						$conflictingBooking->getLocation()->post_title
					) . PHP_EOL;
				}
				throw new BookingDeniedException( $errorMessage );
			}
		}
	}

	/**
	 * Gets a string of all rule properties, so they can be displayed using CMB2
	 *
	 * I would love to not repeat myself here, but I don't know how to do it. This is a carbon copy of the function in BookingRule.
	 * TODO: Find a way to not repeat myself here
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
	 * Tries to create objects for all applied Booking rules from the settings
	 * @throws BookingRuleException
	 * @OVERRIDE
	 */
	public static function init():array{
		$validRules = parent::init();
		$rulesConfig = Settings::getOption('commonsbooking_options_restrictions', 'rules_group');
		$appliedRules = [];

		if (!is_array($rulesConfig)) {
			throw new BookingRuleException('No valid booking rules found');
		}

		foreach ($rulesConfig as $ruleConfig) {
			/** @var BookingRule $validRule */
			foreach ($validRules as $validRule){
				if ($validRule->name !== $ruleConfig['rule-type']) {
					continue;
				}

				$ruleParams = [];
 				if (
					 isset($ruleConfig['rule-param1']) &&
					 count($validRule->params) >= 1)
				 {
					 $ruleParams[] = $ruleConfig['rule-param1'];
				 }
				if (
					isset($ruleConfig['rule-param2']) &&
					count($validRule->params) >= 2
					)
				{
					$ruleParams[] = $ruleConfig['rule-param2'];
				}

				if (isset($ruleConfig['rule-select-param'])) { $selectParam = $ruleConfig['rule-select-param']; }

				if (isset ( $ruleConfig['rule-applies-all'] ) && $ruleConfig['rule-applies-all'] === 'on'){
					$appliesToAll = true;
				}

				if ( isset( $ruleConfig['rule-applies-categories']) && $ruleConfig['rule-applies-categories'] !== FALSE ){
					$appliedTerms = $ruleConfig['rule-applies-categories'];
				}

				$bookingRule = new self($validRule);
				$bookingRule->setAppliesToWho(
					$appliesToAll ?? false,
					$appliedTerms ?? []
				);
				$bookingRule->setAppliedParams(
					$ruleParams ?? [],
					$selectParam ?? null
				);
				$bookingRule->setExcludedRoles(
					$ruleConfig['rule-exempt-roles'] ?? []);
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
		try {
			self::init();
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}
	}
}