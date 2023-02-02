<?php

namespace CommonsBooking\Service;

use Closure;
use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\OptionsTab;

/**
 *
 */
class BookingRuleApplied extends BookingRule {
	private bool $appliesToAll;
	private array $appliedTerms;
	private array $setParams;

	/**
	 * The constructor for BookingRules after they can be applied to actual bookings
	 * @throws BookingRuleException
	 */
	public function __construct( string $name,string $title, string $description, string $errorMessage, Closure $validationFunction, bool $appliesToAll,array $appliedTerms = [],array $paramList = [],array $setParams = []) {
		parent::__construct( $name,$title, $description, $errorMessage, $validationFunction,$paramList );
		if ($appliesToAll){
			$this->appliesToAll = true;
		}
		else {
			$this->appliesToAll = false;
			if (empty($appliedTerms)){
				throw new BookingRuleException(__("You need to specify a category, if the rule does not apply to all items", 'commonsbooking'));
			}
			$this->appliedTerms = $appliedTerms;
		}

		if ($paramList){
			if (count($paramList) == count($setParams) ){
				$this->setParams = $setParams;
			}
			else {
				throw new BookingRuleException(__("Booking rules: Not enough parameters specified.", 'commonsbooking'));
			}
		}
	}

	/**
	 * Checks if the booking violates any of our rules, return false if it doesn't, true if it does
	 * @param Booking $booking
	 *
	 * @return bool
	 */
	public function checkBooking( Booking $booking ): bool {
		if ($booking->isUserPrivileged()){
			return false;
		}

		if (! $this->appliesToAll){
			if (! $booking->termsApply($this->appliedTerms) ){
				return false;
			}
		}

		$validationFunction = $this->validationFunction;
		return $validationFunction( $booking, $this->setParams ?? [], $this->appliesToAll ? false : $this->appliedTerms );
	}

	/**
	 * @throws BookingRuleException
	 */
	public static function fromBookingRule(BookingRule $rule, bool $appliesToAll, array $appliedTerms = [], array $setParams = []): BookingRuleApplied {
		return new self(
			$rule->name,
			$rule->title,
			$rule->description,
			$rule->errorMessage,
			$rule->validationFunction,
			$appliesToAll,
			$appliedTerms ?? [],
			$rule->params ?? [],
			$setParams ?? []
		);
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

			if ( ! ($rule instanceof BookingRuleApplied )) {
				throw new BookingRuleException( "Value must be a BookingRuleApplied" );
			}
			if ($rule->checkBooking( $booking )){
				throw new BookingRuleException( $rule->getErrorMessage() );
			}
		}
		return true;
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
				$appliedRules[] = self::fromBookingRule(
					$validRule,
					isset ( $ruleConfig['rule-applies-all'] ) && $ruleConfig['rule-applies-all'] === 'on',
					(isset($ruleConfig['rule-applies-categories']) && $ruleConfig['rule-applies-categories'] !== false) ? $ruleConfig['rule-applies-categories'] : [],
					$ruleParams ?? []
				);
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