<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Geocoder\Location as GeocoderLocation;
use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\Helper;
use CommonsBooking\View\Calendar;
use CommonsBooking\Tests\CPTCreationTrait;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Tests\Helper\GeoHelperTest;

/**
 *
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
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
		$calendar = Calendar::renderTable( [] );
	}

	public function setUp(): void {
		error_reporting( E_ALL & ~E_DEPRECATED ); // do not warn about deprecations, deprecations make benchmarks fails
		// prevent calling geocoder on startup
		$sut = new class() implements GeoCodeService {
			public function getAddressData( string $addressString ): ?GeocoderLocation {
				return GeoHelperTest::mockedLocation();
			}
		};
		GeoHelper::setGeoCodeServiceInstance( $sut );

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
		error_log('[fixture] Import files ...');
		require_once __DIR__ . '/../fixtures/benchmark-data.php';
		error_log('Load data ...');
		cb_benchmark_load_fixtures();
		error_log('Done loading.');

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
		cb_benchmark_cleanup_fixtures();
		// $this->tearDownAllPosts();
	}
}
