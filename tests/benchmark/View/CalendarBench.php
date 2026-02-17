<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Helper\Helper;
use CommonsBooking\View\Calendar;
use CommonsBooking\Tests\CPTCreationTrait;

/**
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class CalendarBench {

	use CPTCreationTrait;

	const BOOKINGS_PER_ITEM = 100;
	const ITEMS_TOTAL       = 30;
	const USER_ID           = 1;


	/**
	 * @Revs(5)
	 * @return void
	 * @throws \Exception
	 */
	public function benchRenderTable() {
		$calendar = Calendar::renderTable( [] );
	}

	public function setUp(): void {
		global $wpdb;
		$wpdb->query( 'SET autocommit=0' );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		define( 'WP_IMPORTING', true );
		add_filter(
			'pre_wp_unique_post_slug',
			fn( $override_slug, $slug, $post_id, $post_status, $post_type, $post_parent ) => Helper::generateRandomString(),
			10,
			6
		);
		$repetitions = [];
		$start       = new \DateTime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE );
		$end         = new \DateTime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE );
		$end->modify( self::BOOKINGS_PER_ITEM . ' days' );
		$period = new \DatePeriod( $start, new \DateInterval( 'P1D' ), $end );
		foreach ( $period as $date ) {
			$startTs = $date->getTimestamp();
			$date->modify( '23:59:59' );
			$endTs         = $date->getTimestamp();
			$repetitions[] = [
				'start' => $startTs,
				'end' => $endTs,
			];
		}
		for ( $i = 0; $i < self::ITEMS_TOTAL; $i++ ) {
			$item      = $this->createItem( "Benchmark Item $i" );
			$location  = $this->createLocation( "Benchmark Location $i" );
			$timeframe = $this->createTimeframe(
				$location,
				$item,
				$start->getTimestamp(),
				null // Make timeframe infinite
			);
			foreach ( $repetitions as $repetition ) {
				$this->createBooking(
					$location,
					$item,
					$repetition['start'],
					$repetition['end']
				);
			}
		}
		// disable performance tweaks
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		$wpdb->query( 'COMMIT;' );
		$wpdb->query( 'SET autocommit = 1;' );
		remove_filter(
			'pre_wp_unique_post_slug',
			fn( $override_slug, $slug, $post_id, $post_status, $post_type, $post_parent ) => Helper::generateRandomString()
		);
	}

	public function tearDown(): void {
		foreach ( $this->bookingIDs as $bookingID ) {
			wp_delete_post( $bookingID, true );
		}
		foreach ( $this->itemIDs as $itemID ) {
			wp_delete_post( $itemID, true );
		}
		foreach ( $this->locationIDs as $locationID ) {
			wp_delete_post( $locationID, true );
		}
		foreach ( $this->timeframeIDs as $timeframeID ) {
			wp_delete_post( $timeframeID, true );
		}
		foreach ( $this->bookingIDs as $bookingID ) {
			wp_delete_post( $bookingID, true );
		}
	}
}
