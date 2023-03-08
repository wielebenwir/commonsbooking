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
	 * @param   array  $paramsToSet
	 *
	 * @throws \CommonsBooking\Exception\BookingRuleException - if not enough params were specified for the BookingRule
	 */
	public function setAppliedParams( array $paramsToSet ): void {
		if (! empty($this->params)){
			if (count($this->params) == count($paramsToSet) ){
				$this->appliedParams = $paramsToSet;
			}
			else {
				throw new BookingRuleException(__("Booking rules: Not enough parameters specified.", 'commonsbooking'));
			}
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
		return $validationFunction( $booking, $this->appliedParams ?? [], $this->appliesToAll ? false : $this->appliedTerms );
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
			$ruleset = self::getAll();
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
	 * Tries to create objects for all applied Booking rules from the settings
	 * @throws BookingRuleException
	 */
	public static function getAll():array{
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
				if (isset($ruleConfig['rule-param1'])) { $ruleParams[] = $ruleConfig['rule-param1']; }
				if (isset($ruleConfig['rule-param2'])) { $ruleParams[] = $ruleConfig['rule-param2']; }
				if (isset($ruleConfig['rule-param3'])) { $ruleParams[] = $ruleConfig['rule-param3']; }

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
					$ruleParams ?? []
				);
				$bookingRule->setExcludedRoles(
					$ruleConfig['rule-applies-roles'] ?? []);
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
			self::getAll();
		} catch ( BookingRuleException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}
	}
}