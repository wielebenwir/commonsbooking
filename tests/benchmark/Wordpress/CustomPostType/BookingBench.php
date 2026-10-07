<?php

namespace CommonsBooking\Tests\Benchmark\Wordpress\CustomPostType;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Benchmark\BenchmarkCase;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingBench extends BenchmarkCase {

	private int $benchmarkItemId;
	private int $benchmarkLocationId;
	private int $repetitionStart;
	private int $repetitionEnd;
	private ?int $bookingRulesUserId = null;
	private $previousBookingRules;

	/**
	 * @BeforeMethods({"setUp"})
	 * @AfterMethods({"tearDown"})
	 * @Iterations(6)
	 * @Revs(3)
	 */
	public function benchBookingLifecycle(): void {
		$this->runBookingLifecycle();
	}

	/**
	 * @BeforeMethods({"setUp", "enableBookingRule"})
	 * @AfterMethods({"tearDown"})
	 * @Iterations(6)
	 * @Revs(3)
	 * @ParamProviders({"provideBookingRules"})
	 */
	public function benchBookingLifecycleWithBookingRule( array $params ): void {
		$this->runBookingLifecycle();
	}

	private function runBookingLifecycle(): void {
		$bookingId          = $this->handleBookingRequest( 'unconfirmed' );
		$this->bookingIds[] = $bookingId;
		$postName           = get_post( $bookingId )->post_name;

		$this->handleBookingRequest( 'confirmed', $bookingId, $postName );
		$this->handleBookingRequest( 'canceled', $bookingId, $postName );
	}

	public function setUp(): void {
		parent::setUp();
		BookingCodes::initBookingCodesTable();
		$this->benchmarkItemId     = $this->createItem( 'Booking Benchmark Item' );
		$this->benchmarkLocationId = $this->createLocation( 'Booking Benchmark Location' );
		$this->repetitionStart     = strtotime( '+1 day midnight' );
		$this->repetitionEnd       = strtotime( '+3 days midnight' ) - 1;

		$timeframeId = $this->createTimeframe(
			$this->benchmarkLocationId,
			$this->benchmarkItemId,
			strtotime( 'today midnight' ),
			strtotime( '+180 days midnight' )
		);
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES, '' );
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_CREATE_BOOKING_CODES, '' );
	}

	public function enableBookingRule( array $params ): void {
		$this->previousBookingRules = Settings::getOption( 'commonsbooking_options_restrictions', 'rules_group' );
		Settings::updateOption(
			'commonsbooking_options_restrictions',
			'rules_group',
			[ $params['rule'] ]
		);

		$this->bookingRulesUserId = wp_create_user(
			'benchmark-booking-rules-' . wp_generate_uuid4(),
			wp_generate_password()
		);
		wp_set_current_user( $this->bookingRulesUserId );
	}

	public function provideBookingRules(): iterable {
		$appliesToAll = [ 'rule-applies-all' => 'on' ];

		yield 'no simultaneous booking' => [
			'rule' => $appliesToAll + [ 'rule-type' => 'noSimultaneousBooking' ],
		];
		yield 'prohibit chain booking' => [
			'rule' => $appliesToAll + [ 'rule-type' => 'prohibitChainBooking' ],
		];
		yield 'max booking days per week' => [
			'rule' => $appliesToAll + [
				'rule-type'         => 'maxBookingDaysPerWeek',
				'rule-param1'       => 100,
				'rule-select-param' => 1,
			],
		];
		yield 'max booking days per month' => [
			'rule' => $appliesToAll + [
				'rule-type'         => 'maxBookingDaysPerMonth',
				'rule-param1'       => 100,
				'rule-select-param' => 1,
			],
		];
		yield 'max booking days' => [
			'rule' => $appliesToAll + [
				'rule-type'   => 'maxBookingDays',
				'rule-param1' => 100,
				'rule-param2' => 365,
			],
		];
		yield 'max bookings per week' => [
			'rule' => $appliesToAll + [
				'rule-type'         => 'maxBookingsWeek',
				'rule-param1'       => 100,
				'rule-select-param' => 1,
			],
		];
		yield 'max bookings per month' => [
			'rule' => $appliesToAll + [
				'rule-type'         => 'maxBookingsMonth',
				'rule-param1'       => 100,
				'rule-select-param' => 1,
			],
		];
	}

	public function tearDown(): void {
		if ( $this->bookingRulesUserId !== null ) {
			Settings::updateOption(
				'commonsbooking_options_restrictions',
				'rules_group',
				$this->previousBookingRules
			);
			wp_delete_user( $this->bookingRulesUserId );
			$this->bookingRulesUserId = null;
		}

		parent::tearDown();
	}

	private function handleBookingRequest(
		string $status,
		?int $bookingId = null,
		?string $postName = null
	): int {
		return Booking::handleBookingRequest(
			(string) $this->benchmarkItemId,
			(string) $this->benchmarkLocationId,
			$status,
			$bookingId,
			null,
			(string) $this->repetitionStart,
			(string) $this->repetitionEnd,
			$postName,
			null
		);
	}
}
