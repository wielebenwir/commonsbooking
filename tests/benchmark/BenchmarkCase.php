<?php

namespace CommonsBooking\Tests\Benchmark;

use CommonsBooking\Geocoder\Location as GeocoderLocation;
use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\CPTCreationTrait;
use CommonsBooking\Tests\Helper\GeoHelperTest;

abstract class BenchmarkCase {

	use CPTCreationTrait;

	private static bool $fixtureInitialized     = false;
	private static ?BenchmarkCase $fixtureOwner = null;
	private static array $sharedPostIds         = [
		'bookingIds'     => [],
		'timeframeIds'   => [],
		'restrictionIds' => [],
		'locationIds'    => [],
		'itemIds'        => [],
		'mapIds'         => [],
	];

	/**
	 * Benchmark classes whose (persistent) cache has already been cleared.
	 *
	 * @var array<string, bool>
	 */
	private static array $cacheClearedForBenchmarks = [];

	protected const BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE = 77;
	protected const BOOKINGS_PER_ITEM_AFTER_CURRENTDATE  = 33;
	protected const ITEMS_TOTAL                          = 100;
	protected const ITEMS_DISCONNECTED                   = 20;
	protected const LOCATIONS_DISCONNECTED               = 20;

	public function setUp(): void {
		error_reporting( E_ALL & ~E_DEPRECATED );
		wp_set_current_user( 1 );

		// disable cache for fixture creation
		add_filter( 'commonsbooking_disableCache', '__return_true' );

		if ( self::$fixtureInitialized ) {
			$this->applyCacheMode();
			$this->hydrateSharedPostIds();
			return;
		}

		$geoCodeService = new class() implements GeoCodeService {
			public function getAddressData( string $addressString ): ?GeocoderLocation {
				return GeoHelperTest::mockedLocation();
			}
		};
		GeoHelper::setGeoCodeServiceInstance( $geoCodeService );

		global $wpdb;
		$wpdb->query( 'SET autocommit=0' );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		$slugFilter = fn() => Helper::generateRandomString();
		add_filter( 'pre_wp_unique_post_slug', $slugFilter, 10, 6 );

		$firstBooking = strtotime( '-' . static::BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE . ' days midnight' );
		$bookingCount = static::BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE + static::BOOKINGS_PER_ITEM_AFTER_CURRENTDATE;

		for ( $itemIndex = 0; $itemIndex < static::ITEMS_TOTAL; $itemIndex++ ) {
			$itemId     = $this->createItem( "Benchmark Item $itemIndex" );
			$locationId = $this->createLocation( "Benchmark Location $itemIndex" );
			$this->createTimeframe( $locationId, $itemId, $firstBooking, null );

			for ( $bookingIndex = 0; $bookingIndex < $bookingCount; $bookingIndex++ ) {
				$start = strtotime( "+$bookingIndex days", $firstBooking );
				$this->createBooking( $locationId, $itemId, $start, strtotime( '+1 day', $start ) - 1 );
			}
		}

		for ( $itemIndex = 0; $itemIndex < static::ITEMS_DISCONNECTED; $itemIndex++ ) {
			$this->createItem( "Benchmark Disconnected Item $itemIndex" );
		}

		for ( $locationIndex = 0; $locationIndex < static::LOCATIONS_DISCONNECTED; $locationIndex++ ) {
			$this->createLocation( "Benchmark Disconnected Location $locationIndex" );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		$wpdb->query( 'COMMIT;' );
		$wpdb->query( 'SET autocommit=1' );
		remove_filter( 'pre_wp_unique_post_slug', $slugFilter, 10 );

		self::$fixtureInitialized = true;
		self::$fixtureOwner       = $this;
		$this->captureSharedPostIds();
		register_shutdown_function( [ self::class, 'tearDownSharedFixture' ] );

		$this->applyCacheMode();
	}

	/**
	 * Whether the benchmark should run with CommonsBooking's cache enabled.
	 *
	 * Cached benchmarks override this.
	 */
	protected function useCache(): bool {
		return false;
	}

	/**
	 * Applies the cache mode requested by {@see self::useCache()}.
	 *
	 * The plugin bypasses its cache completely while the
	 * "commonsbooking_disableCache" filter is active, so cached benchmarks
	 * have to remove it again.
	 */
	private function applyCacheMode(): void {
		if ( ! $this->useCache() ) {
			return;
		}

		remove_filter( 'commonsbooking_disableCache', '__return_true' );
		$this->clearCacheOnce();
	}

	/**
	 * Clears the persistent cache once per benchmark class.
	 *
	 * This keeps the first iteration of every cached benchmark cold and makes
	 * sure that results cannot be skewed by cache entries left behind by an
	 * earlier benchmark class or a previous run.
	 */
	private function clearCacheOnce(): void {
		if ( self::$cacheClearedForBenchmarks[ static::class ] ?? false ) {
			return;
		}

		self::$cacheClearedForBenchmarks[ static::class ] = true;
		Plugin::clearCache();
	}

	public function tearDown(): void {
		$this->captureSharedPostIds();
	}

	public static function tearDownSharedFixture(): void {
		if ( ! self::$fixtureOwner ) {
			return;
		}

		self::$fixtureOwner->hydrateSharedPostIds();
		self::$fixtureOwner->tearDownAllPosts();
		remove_filter( 'commonsbooking_disableCache', '__return_true' );
	}

	private function captureSharedPostIds(): void {
		foreach ( array_keys( self::$sharedPostIds ) as $property ) {
			self::$sharedPostIds[ $property ] = array_values(
				array_unique( array_merge( self::$sharedPostIds[ $property ], $this->{$property} ) )
			);
		}
	}

	private function hydrateSharedPostIds(): void {
		foreach ( self::$sharedPostIds as $property => $postIds ) {
			$this->{$property} = $postIds;
		}
	}
}
