<?php

namespace CommonsBooking\Service;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\Options\OptionsTab;
use Exception;

/**
 *
 */
class BookingRuleApplied extends BookingRule {
	private bool $appliesToAll;
	private array $appliedTerms;
	private array $setParams;

	/**
	 * The constructor for BookingRules after they can be applied to actual bookings
	 * @throws Exception
	 */
	public function __construct( string $name,string $title, string $description, string $errorMessage, \Closure $validationFunction, bool $appliesToAll,array $appliedTerms = [],array $paramList = [],array $setParams = []) {
		parent::__construct( $name,$title, $description, $errorMessage, $validationFunction,$paramList );
		if ($appliesToAll){
			$this->appliesToAll = true;
		}
		else {
			$this->appliesToAll = false;
			if (empty($appliedTerms)){
				throw new \InvalidArgumentException(__("You need to specify a category, if the rule does not apply to all items", 'commonsbooking'));
			}
			$this->appliedTerms = $appliedTerms;
		}

		if (isset($this->params)){
			if (count($paramList) == $this->params){
				$this->setParams = $paramList;
			}
			else {
				throw new \InvalidArgumentException(__("Booking rules: Not enough parameters specified.", 'commonsbooking'));
			}
		}
	}

	/**
	 * @throws Exception
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
			$rule->setParams ?? []
		);
	}

	/**
	 * Checks if a booking conforms to the rule sets, will always allow bookings from item/location admins & administrators
	 *
	 * @param \CommonsBooking\Model\Booking $booking
	 * @throws Exception
	 */
	public static function bookingConformsToRules(\CommonsBooking\Model\Booking $booking):bool {
		try {
			$ruleset = self::getAll();
		} catch ( Exception $e ) {
			//booking always conforms to rules if ruleset is not available / invalid
			return true;
		}
		$bookingItem = $booking->getItem()->getPost();
		$bookingLocation = $booking->getLocation()->getPost();

		if(commonsbooking_isCurrentUserAllowedToEdit($bookingItem) || commonsbooking_isCurrentUserAllowedToEdit($bookingLocation)){
			return true;
		}

		/** @var BookingRuleApplied $rule */
		foreach ( $ruleset as $rule ) {

			//check if rule applies to my current booking
			if (! $rule->appliesToAll){
				$isInItemCat = has_term( $rule->appliedTerms, Item::$postType . 's_category', $bookingItem );
				$isInLocationCat = has_term( $rule->appliedTerms, Location::$postType . 's_category', $bookingLocation );
				if ( ! ($isInItemCat || $isInLocationCat)){
					continue;
				}
			}

			if ( ! ($rule instanceof BookingRuleApplied )) {
				throw new Exception( "Value must be a BookingRuleApplied" );
			}
			$validationFunction = $rule->validationFunction;
			if (! ($validationFunction($booking,$rule->setParams ?? []))){
				throw new Exception($rule->getErrorMessage());
			}
		}
		return true;
	}

	/**
	 * Tries to create objects for all applied Booking rules from the settings
	 * @throws Exception
	 */
	public static function getAll():array{
		$validRules = parent::init();
		$rulesConfig = Settings::getOption('commonsbooking_options_restrictions', 'rules_group');
		$appliedRules = [];

		if (!is_array($rulesConfig)) {
			throw new Exception('No valid booking rules found');
		}

		foreach ($rulesConfig as $ruleConfig) {
			/** @var BookingRule $validRule */
			foreach ($validRules as $validRule){
				if ($validRule->name !== $ruleConfig['rule-type']) {
					continue;
				}

				$ruleParams = [];
				if (isset($ruleConfig['rule-param1'])) { $ruleParams[] = $ruleConfig['rule-param1']; };
				if (isset($ruleConfig['rule-param2'])) { $ruleParams[] = $ruleConfig['rule-param2']; };
				if (isset($ruleConfig['rule-param3'])) { $ruleParams[] = $ruleConfig['rule-param3']; };
				$appliedRules[] = self::fromBookingRule(
					$validRule,
					isset ( $ruleConfig['rule-applies-all'] ) && $ruleConfig['rule-applies-all'] === 'on',
					$ruleConfig['rule-applies-categories'] ?? [],
					$ruleParams ?? []
				);
				}
			}

		return $appliedRules;
	}

	/**
	 * Checks if it can create all the rules, sets an error transient if it can't
	 * @return void
	 * @throws Exception
	 */
	public static function validateRules():void{
		try {
			self::getAll();
		} catch ( \InvalidArgumentException $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage()
			);
		}
	}
}