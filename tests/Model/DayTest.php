<?php

namespace Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DayTest extends TestCase {

	private $instance;

	private $locationId;

	private $itemId;

	private $timeframeId;

	const DATE = '01.07.2021';

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	function start_transaction() {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	protected function setUp() {
		global $wpdb;
		$table_name      = $wpdb->prefix . BookingCodes::$tablename;
		$charset_collate = $wpdb->get_charset_collate();

//		$wpdb->query( 'SET autocommit = 0;' );
//		$wpdb->query( 'START TRANSACTION;' );

		$sql = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code) 
        ) $charset_collate;";

		$wpdb->query($sql);

		// Create location
		$this->locationId = wp_insert_post( [
			'post_title'   => 'TestLocation',
			'post_type' => Location::$postType,
			'post_status' => 'publish'
		] );

		// Create Item
		$this->itemId = wp_insert_post( [
			'post_title'   => 'TestItem',
			'post_type'=> Item::$postType,
			'post_status' => 'publish'
		] );

		// Create Timeframe
		$this->timeframeId = wp_insert_post( [
			'post_title'   => 'TestTimeframe',
			'post_type'=> Timeframe::$postType,
			'post_status' => 'publish'
		] );
		update_post_meta( $this->timeframeId, 'type', Timeframe::BOOKABLE_ID );
		update_post_meta( $this->timeframeId, 'timeframe-repetition', 'w');
		update_post_meta( $this->timeframeId, 'start-time','8:00 AM');
		update_post_meta( $this->timeframeId, 'end-time', '12:00 PM');
		update_post_meta( $this->timeframeId, 'timeframe-max-days', '3');
		update_post_meta( $this->timeframeId, 'location-id', $this->locationId);
		update_post_meta( $this->timeframeId, 'item-id', $this->itemId);
		update_post_meta( $this->timeframeId, 'grid','0');
		update_post_meta( $this->timeframeId, 'repetition-start', self::REPETITION_START);
		update_post_meta( $this->timeframeId, 'repetition-end', self::REPETITION_END);
		update_post_meta( $this->timeframeId,
			'weekdays',
			[ "1", "2", "3", "4" ]
		);

		$this->instance = new Day(
			self::DATE,
			[$this->locationId],
			[$this->itemId]
		);
	}

	protected function tearDown() {
		wp_delete_post( $this->itemId, true );
		wp_delete_post( $this->locationId, true );
		wp_delete_post( $this->timeframeId, true );

		global $wpdb;
		$table_name      = $wpdb->prefix . BookingCodes::$tablename;
		$sql = "DROP TABLE $table_name";
		$wpdb->query($sql);
	}


	public function testGetFormattedDate() {
		$this->assertTrue('01.07.2021' == $this->instance->getFormattedDate('d.m.Y'));
	}

	public function testGetDayOfWeek() {
		$this->assertTrue('4' == $this->instance->getDayOfWeek());
	}

	public function testGetDate() {
		$this->assertTrue(self::DATE == $this->instance->getDate());
	}

	protected static function getInstanceMethod($methodName): \ReflectionMethod {
		$class = new ReflectionClass(Day::class);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}

	public function testGetEndDate() {
		$method = self::getInstanceMethod('getEndDate');
		/** @var \DateTime $dateTime */
		$dateTime = $method->invokeArgs($this->instance, [get_post($this->timeframeId)]);
		$this->assertTrue(
			$dateTime->format('d.m.Y') == date('d.m.Y', self::REPETITION_END)
		);
	}

	public function testIsInTimeframe() {
		$timeframe = get_post($this->timeframeId);
		$this->assertTrue($this->instance->isInTimeframe($timeframe));
	}

	public function testGetName() {
		$this->assertTrue(date( 'l', strtotime( self::DATE ) ) == $this->instance->getName());
	}
}
