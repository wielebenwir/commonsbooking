<?php

namespace CommonsBooking\Tests\Benchmark;

use CommonsBooking\Geocoder\Location as GeocoderLocation;
use CommonsBooking\Helper\GeoCodeService;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Tests\Helper\GeoHelperTest;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

abstract class BenchmarkCase extends CustomPostTypeTest {

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

	private static ?int $subscriberUserId = null;
	private static ?int $adminUserId      = null;

	protected const BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE = 77;
	protected const BOOKINGS_PER_ITEM_AFTER_CURRENTDATE  = 33;
	protected const ITEMS_TOTAL                          = 100;
	protected const ITEMS_DISCONNECTED                   = 20;
	protected const LOCATIONS_DISCONNECTED               = 20;

	public function setUp(): void {
		error_reporting( E_ALL & ~E_DEPRECATED );

		$this->initializeBenchmarkUsers();
		wp_set_current_user( $this->adminUserID );

		if ( self::$fixtureInitialized ) {
			$this->hydrateSharedPostIds();
			return;
		}

		$geoCodeService = new class() implements GeoCodeService {
			public function getAddressData( string $addressString ): ?GeocoderLocation {
				return GeoHelperTest::mockedLocation();
			}
		};
		GeoHelper::setGeoCodeServiceInstance( $geoCodeService );

		add_filter( 'commonsbooking_disableCache', '__return_true' );

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

		for ( $itemIndex = 0; $itemIndex < static::ITEMS_TOTAL; $itemIndex++ ) {
			$itemId     = $this->createItem( "Benchmark Item $itemIndex" );
			$locationId = $this->createLocation( "Benchmark Location $itemIndex" );
			$this->createTimeframe( $locationId, $itemId, $firstBooking, null );

			for ( $bookingIndex = 0; $bookingIndex < static::BOOKINGS_PER_ITEM_BEFORE_CURRENTDATE; $bookingIndex++ ) {
				$start = strtotime( "+$bookingIndex days", $firstBooking );
				$this->createBenchmarkBooking( $locationId, $itemId, $start, $this->adminUserID );
			}

			for ( $bookingIndex = 0; $bookingIndex < static::BOOKINGS_PER_ITEM_AFTER_CURRENTDATE; $bookingIndex++ ) {
				$start    = strtotime( '+' . ( $bookingIndex + 1 ) . ' days midnight' );
				$authorId = $bookingIndex % 2 === 0 ? $this->subscriberId : $this->adminUserID;
				$this->createBenchmarkBooking( $locationId, $itemId, $start, $authorId );
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

	private function initializeBenchmarkUsers(): void {
		if ( self::$subscriberUserId === null ) {
			$this->createSubscriber();
			self::$subscriberUserId = $this->subscriberId;
		} else {
			$this->subscriberId = self::$subscriberUserId;
		}

		if ( self::$adminUserId === null ) {
			$this->createAdministrator();
			self::$adminUserId = $this->adminUserID;
		} else {
			$this->adminUserID = self::$adminUserId;
		}
	}

	private function createBenchmarkBooking( int $locationId, int $itemId, int $start, int $authorId ): int {
		return $this->createBooking(
			$locationId,
			$itemId,
			$start,
			strtotime( '+1 day', $start ) - 1,
			'12:00 AM',
			'23:59',
			'confirmed',
			$authorId
		);
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
