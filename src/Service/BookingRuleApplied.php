<?php

namespace CommonsBooking\Service;

use CommonsBooking\Settings\Settings;
use WP_Term;

class BookingRuleApplied extends BookingRule {
	private bool $appliesToAll; ///
	private array $appliedTerms;
	private array $setParams;

	/**
	 * The constructor for BookingRules after they can be applied to actual bookings
	 * @throws \Exception
	 */
	public function __construct( string $name,string $title, string $description, string $errorMessage, \Closure $validationFunction, bool $appliesToAll,array $appliedTerms = [],array $paramList = [],array $setParams = []) {
		parent::__construct( $name,$title, $description, $errorMessage, $validationFunction,$paramList );
		if ($appliesToAll){
			$this->appliesToAll = true;
		}
		else {
			$this->appliesToAll = false;
			if (empty($appliedTerms)){
				throw new \Exception("You need to specify a category, if the rule does not apply to all");
			}
			$this->appliedTerms = $appliedTerms;
		}

		if (isset($this->params)){
			if (count($paramList) == $this->params){
				$this->setParams = $paramList;
				foreach ($paramList as $param){
					if (! is_int($param)){
						throw new \Exception("Parameter must be an int");
					}
				}
			}
			else {
				throw new \Exception("Unexpected parameter length");
			}
		}
	}

	public static function fromBookingRule(BookingRule $rule, bool $appliesToAll, array $appliedTerms = [], array $setParams = []){
		try {
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
		} catch ( \Exception $e ) {
			set_transient(
				OptionsTab::ERROR_TYPE,
				$e->getMessage());
		}
	}

	public static function bookingConformsToRules(\CommonsBooking\Model\Booking $booking):bool {
		try {
			$ruleset = self::getAll();
		} catch ( \Exception $e ) {
			//booking always conforms to rules if ruleset is not available
			return true;
		}
		foreach ( $ruleset as $rule ) {
			if ( ! ($rule instanceof BookingRuleApplied )) {
				throw new \InvalidArgumentException( "Value must be a BookingRuleApplied" );
			}
			$validationFunction = $rule->validationFunction;
			if (! ($validationFunction($booking,$rule->setParams ?? []))){
				throw new \Exception($rule->errorMessage);
			}
		}
		return true;
	}

	/**
	 * Tries to create objects for alle applied Booking rules from the settings
	 * @throws \Exception
	 */
	public static function getAll():array{
		$validRules = parent::init();
		$rulesConfig = Settings::getOption('commonsbooking_options_restrictions', 'rules_group');
		$appliedRules = [];

		if (!is_array($rulesConfig)) {
			throw new \Exception('No valid booking rules found');
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
}