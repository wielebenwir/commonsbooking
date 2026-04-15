<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class ItemTest extends CustomPostTypeTest {

	private Item $itemModel;
	private Timeframe $timeframeModel;


	/**
	 * Test not working - maybe bug in function?
	 * @return void
	 */
	/*
	public function testGetBookableTimeframesByLocation() {
		$timeframeArray[] = $this->timeframeModel;
		$this->assertEquals($timeframeArray, $this->itemModel->getBookableTimeframesByItem($this->locationId)); //Not working
	}
	*/

	public function testGetAdmins() {
		// Case: No admins
		// $this->assertEquals([], $this->itemModel->getAdmins()); - Currently this function includes the post author
		$this->assertEquals( [ self::USER_ID ], $this->itemModel->getAdmins() );

		// Case: CB Manager as admin
		$this->createCBManager();
		$adminItemModel = new Item(
			$this->createItem( 'Testitem2', 'publish', [ $this->cbManagerUserID ] )
		);
		// $this->assertEquals([$this->cbManagerUserID], $adminItemModel->getAdmins()); - Currently this function includes the post author
		$this->assertEquals( [ $this->cbManagerUserID, self::USER_ID ], $adminItemModel->getAdmins() );
	}


	/**
	 * Can be used after PR #1179 is merged
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testGetRestrictions() {
		$this->restrictionIds = array_unique($this->restrictionIds);
		$restrictionArray = [];
		foreach ($this->restrictionIds as $restrictionId) {
			$restrictionArray[] = new Restriction($restrictionId);
		}
		$this->assertEquals($restrictionArray, $this->itemModel->getRestrictions());
	}
	*/

	public function testGetLocation() {
		$dt = new \DateTime( self::CURRENT_DATE );
		ClockMock::freeze( $dt );

		// just one item that is currently bookable
		$this->assertEquals( $this->locationId, $this->itemModel->getLocation()->ID );

		// in two weeks, the timeframe is expired, so the item will not be at the location anymore
		$dt->modify( '+2 weeks' );
		ClockMock::freeze( $dt );
		$this->assertNull( $this->itemModel->getLocation() );

		// item that is in location A monday-thursday and in location B friday-sunday. Position should change depending on the day of the week
		$locationA       = $this->createLocation( 'location a' );
		$locationB       = $this->createLocation( 'location b' );
		$movingItem      = $this->createItem( 'location changing item' );
		$movingItemModel = new Item( $movingItem );
		$tf1             = new Timeframe(
			$this->createTimeframe(
				$locationA,
				$movingItem,
				strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
				strtotime( '+14 days', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'8:00 AM',
				'12:00 PM',
				'publish',
				[ '1','2','3','4' ]
			)
		);
		$tf2             = new Timeframe(
			$this->createTimeframe(
				$locationB,
				$movingItem,
				strtotime( '-1 day', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
				strtotime( '+14 days', strtotime( \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'8:00 AM',
				'12:00 PM',
				'publish',
				[ '5','6','7' ]
			)
		);
		$this->assertFalse( $tf1->overlaps( $tf2 ) );
		$dt = new \DateTime( self::CURRENT_DATE );
		$dt->modify( 'monday' );
		ClockMock::freeze( $dt );
		$this->assertEquals( $locationA, $movingItemModel->getLocation()->ID );
		$dt->modify( 'friday' );
		ClockMock::freeze( $dt );
		$this->assertEquals( $locationB, $movingItemModel->getLocation()->ID );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->restrictionIds[] = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null
		);
		$this->timeframeModel   = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		$this->itemModel        = new Item( $this->itemId );
		$this->createSubscriber();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
