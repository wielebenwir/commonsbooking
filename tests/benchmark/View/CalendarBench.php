<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Geocoder\Location as GeocoderLocation;
use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\Helper;
use CommonsBooking\View\Calendar;
use CommonsBooking\Tests\CPTCreationTrait;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Tests\Helper\GeoHelperTest;
use WP_Query;

/**
 *
 * @BeforeClassMethods({"setUpBeforeClass"})
 * @AfterClassMethods({"tearDownAfterClass"})
 * @BeforeMethods({"setUp"})
 * 
 */
class CalendarBench {

	use CPTCreationTrait;

	const BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE = 77; // Simulate bookings that are in the past
	const BOOKINGS_PER_ITEM_AFTER_CURRENTDATE  = 33; // Simulate bookings in the future
	const ITEMS_TOTAL                          = 100; // The total amount of items that are connected with a timeframe

	const ITEMS_DISCONNECTED     = 20; // items without a timeframe, see #2084
	const LOCATIONS_DISCONNECTED = 20; // locations without a timeframe, see #2084
	const USER_ID                = 1; // The user that owns all of those bookings


	/**
	 * @Iterations(3)
	 * @Revs(3)
	 * @return void
	 * @throws \Exception
	 */
	public function benchRenderTable() {
		//ClockMock::freeze(new \DateTime(\CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE));
		$calendar = Calendar::renderTable( [] );
		file_put_contents("cal.txt",$calendar);
		//ClockMock::reset();
	}

	public function setUp(): void {
		add_filter('commonsbooking_disableCache', function() {
			return true;
		} ); //always disable cache so caching doesn't influence our performance metrics
	}

	public static function setUpBeforeClass(): void {
		error_reporting( E_ALL & ~E_DEPRECATED ); // do not warn about deprecations, deprecations make benchmarks fails
		// prevent calling geocoder on startup
		// geocoder always gets called upon creating new locations
		$sut = new class() implements GeoCodeService {
			public function getAddressData( string $addressString ): ?GeocoderLocation {
				return GeoHelperTest::mockedLocation();
			}
		};
		GeoHelper::setGeoCodeServiceInstance( $sut );

		$benchmark = new self();
		// make sure that caching is disabled
		add_filter(
			'commonsbooking_disableCache',
			function () {
				return true;
			}
		);

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
		// every day has exactly one booking
		$start = new \DateTime();
		$start->modify( '- ' . self::BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE . ' days' );
		$end = new \DateTime();
		$end->modify( self::BOOKINGS_PER_ITEM_AFTER_CURRENTDATE . ' days' );
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
			$item      = $benchmark->createItem( "Benchmark Item $i" );
			$location  = $benchmark->createLocation( "Benchmark Location $i" );
			$timeframe = $benchmark->createTimeframe(
				$location,
				$item,
				$start->getTimestamp(),
				null // Make timeframe infinite
			);
			foreach ( $repetitions as $repetition ) {
				$benchmark->createBooking(
					$location,
					$item,
					$repetition['start'],
					$repetition['end']
				);
			}
		}

		for ( $i = 0; $i < self::ITEMS_DISCONNECTED; $i++ ) {
			$benchmark->createItem( "Benchmark Disconnected Item $i" );
		}

		for ( $i = 0; $i < self::LOCATIONS_DISCONNECTED; $i++ ) {
			$benchmark->createLocation( "Benchmark Disconnected Location $i" );
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

	public static function tearDownAfterClass(): void {
		$query = [
			'post_type' => [
				\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
				\CommonsBooking\Wordpress\CustomPostType\Item::$postType,
				\CommonsBooking\Wordpress\CustomPostType\Location::$postType
			],
			'posts_per_page' => -1,
			'post_status' => ['confirmed', 'unconfirmed', 'canceled', 'publish', 'inherit']
		];
		$query = new WP_Query($query);
		$posts = $query->get_posts();
		foreach ($posts as $post) {
			wp_delete_post($post->ID, true);
		}
	}
}
