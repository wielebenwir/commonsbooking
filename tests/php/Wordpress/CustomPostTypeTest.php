<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\BaseTestCase;
use CommonsBooking\Tests\CPTCreationTrait;
use SlopeIt\ClockMock\ClockMock;

/**
 * This is the test class that most other tests,
 * that check the behaviour of our CustomPostTypes inherit from.
 */
abstract class CustomPostTypeTest extends BaseTestCase {

	use CPTCreationTrait;

	/**
	 * This is the date that is used in the tests.
	 * It is a thursday.
	 */
	const CURRENT_DATE = '01.07.2021';

	const CURRENT_DATE_FORMATTED = 'July 1, 2021';

	/**
	 * The same date, but in Y-m-d format
	 * @var string
	 */
	protected string $dateFormatted;

	const USER_ID = 1;

	protected $firstTimeframeID;

	protected $secondTimeframeID;

	protected $subscriberID;

	protected int $adminUserID;

	protected int $cbManagerUserID;
	protected int $editorUserID;





	/**
	 * We create the subscriber this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	protected function createSubscriber() {
		$wp_user = get_user_by( 'email', 'a@a.de' );
		if ( ! $wp_user ) {
			$this->subscriberID = wp_create_user( 'normaluser', 'normal', 'a@a.de' );
		} else {
			$this->subscriberID = $wp_user->ID;
		}
	}

	/**
	 * We create the administrator this way, because sometimes the user is already created.
	 * In that case, the unit tests would fail, because there is already the user with this ID in the database.
	 * @return void
	 */
	public function createAdministrator() {
		$wp_user = get_user_by( 'email', 'admin@admin.de' );
		if ( ! $wp_user ) {
			$this->adminUserID = wp_create_user( 'adminuser', 'admin', 'admin@admin.de' );
			$user              = new \WP_User( $this->adminUserID );
			$user->set_role( 'administrator' );
		} else {
			$this->adminUserID = $wp_user->ID;
		}
	}

	/**
	 * We use this role to test assigning capabilities to other roles than the CBManager.
	 * @return void
	 */
	protected function createEditor() {
		$wp_user = get_user_by( 'email', 'editor@editor.de' );
		if ( ! $wp_user ) {
			$this->editorUserID = wp_create_user( 'editoruser', 'editor', 'editor@editor.de' );
			$user               = new \WP_User( $this->editorUserID );
			$user->set_role( 'editor' );
		} else {
			$this->editorUserID = $wp_user->ID;
		}
	}

	public function createCBManager() {
		// we need to run the functions that add the custom user role and assign it to the user
		Plugin::addCustomUserRoles();
		// and add the caps for each of our custom post types
		Plugin::addCPTRoleCaps();
		$wp_user = get_user_by( 'email', 'cbmanager@cbmanager.de' );
		if ( ! $wp_user ) {
			$this->cbManagerUserID = wp_create_user( 'cbmanager', 'cbmanager', 'cbmanager@cbmanager.de' );
			$user                  = new \WP_User( $this->cbManagerUserID );
			$user->set_role( Plugin::$CB_MANAGER_ID );
		} else {
			$this->cbManagerUserID = $wp_user->ID;
		}
	}

	protected function setUp(): void {
		parent::setUp();

		$this->dateFormatted = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );

		$this->setUpBookingCodesTable();

		// Create location
		$this->locationID = self::createLocation( 'Testlocation' );

		// Create Item
		$this->itemID = self::createItem( 'TestItem' );
	}

	protected function setUpBookingCodesTable() {
		global $wpdb;
		$table_name      = $wpdb->prefix . BookingCodes::$tablename;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code)
        ) $charset_collate;";

		$wpdb->query( $sql );
	}

	protected function tearDown(): void {
		parent::tearDown();

		ClockMock::reset();
		$this->tearDownAllItems();
		$this->tearDownAllLocation();
		$this->tearDownAllTimeframes();
		$this->tearDownAllBookings();
		$this->tearDownAllRestrictions();
		$this->tearDownAllMaps();
		$this->tearDownBookingCodesTable();

		wp_logout();
	}

	protected function tearDownAllLocation() {
		foreach ( $this->locationIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllItems() {
		foreach ( $this->itemIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllTimeframes() {
		foreach ( $this->timeframeIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllBookings() {
		foreach ( $this->bookingIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllRestrictions() {
		foreach ( $this->restrictionIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownAllMaps() {
		foreach ( $this->mapIDs as $id ) {
			wp_delete_post( $id, true );
		}
	}

	protected function tearDownBookingCodesTable() {
		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$sql        = "DROP TABLE $table_name";
		$wpdb->query( $sql );
	}
}
