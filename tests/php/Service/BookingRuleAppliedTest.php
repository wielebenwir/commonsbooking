<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Service\BookingRule;
use CommonsBooking\Service\BookingRuleApplied;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Item;

class BookingRuleAppliedTest extends CustomPostTypeTest {

	private Booking $testBookingTomorrow;
	private int $testBookingId;
	protected BookingRuleApplied $appliedAlwaysAllow, $appliedAlwaysDeny;
	protected BookingRule $alwaysallow;
	protected BookingRule $alwaysdeny;

	protected function setUpTestBooking(): void {
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time() ),
			strtotime( '+2 days', time() ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed',
			$this->subscriberId
		);
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );
	}

	public function testRuleExceptions() {
		$bookingRule = new BookingRuleApplied( $this->alwaysallow );
		try {
			$bookingRule->setAppliesToWhat( false, [] );
			$this->fail( 'Expected exception not thrown' );
		} catch ( BookingRuleException $e ) {
			$this->assertEquals( 'You need to specify a category, if the rule does not apply to all items', $e->getMessage() );
		}

		$alwaysAllowWithParams = new BookingRule(
			'alwaysAllow',
			'Always allow',
			'Rule will always evaluate to null',
			'Rule did not evaluate to null',
			function ( \CommonsBooking\Model\Booking $booking ) {
				return null;
			},
			array(
				array(
					'title' => 'Test Param',
					'description' => 'Test Param Description',
				),
			)
		);
		$bookingRule           = new BookingRuleApplied( $alwaysAllowWithParams );
		try {
			$bookingRule->setAppliedParams( [], '' );
			$this->fail( 'Expected exception not thrown' );
		} catch ( BookingRuleException $e ) {
			$this->assertEquals( 'Booking rules: Not enough parameters specified.', $e->getMessage() );
		}
	}
	public function testCheckBooking() {
		$this->assertNull( $this->appliedAlwaysAllow->checkBookingCompliance( $this->testBookingTomorrow ) );
		$this->assertNotNull( $this->appliedAlwaysDeny->checkBookingCompliance( $this->testBookingTomorrow ) );
	}

	public function testCheckTermsApplied() {
		// set up the term named test
		$term         = wp_insert_term( 'test-item', Item::$postType . 's_category' );
		$itemWithTerm = $this->createItem( 'Test item with test-item term', 'publish' );
		wp_set_post_terms( $itemWithTerm, array( $term['term_id'] ), Item::$postType . 's_category' );
		$newLocation   = $this->createLocation( 'Test Location', 'publish' );
		$termTimeframe = $this->createBookableTimeFrameIncludingCurrentDay( $newLocation, $itemWithTerm );
		$termBooking   = new Booking(
			$this->createBooking(
				$newLocation,
				$itemWithTerm,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->subscriberId
			)
		);
		$termRule      = new BookingRuleApplied( $this->alwaysdeny );
		$termRule->setAppliesToWhat( false, array( $term['term_id'] ) );
		// this should not fail because the rule is only applied to items with the test-item term
		$this->assertNull( $termRule->checkBookingCompliance( $this->testBookingTomorrow ) );
		// this should fail
		$sameBooking = $termRule->checkBookingCompliance( $termBooking );
		$this->assertNotNull( $sameBooking );
		$this->assertEquals( $termBooking->ID, reset( $sameBooking )->ID );
	}

	public function testCheckExcludedRoles() {
		// first, we check if the rule applies to the subscriber (as it should)
		$subscriberBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->subscriberId
			)
		);
		$this->assertNotNull( $this->appliedAlwaysDeny->checkBookingCompliance( $subscriberBooking ) );
		// no we check if the rule applies to the admin (as it should not)
		$this->createAdministrator();
		$adminBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->adminUserID
			)
		);
		$this->assertNull( $this->appliedAlwaysDeny->checkBookingCompliance( $adminBooking ) );
		// now we check if the rule applies to the editor (should still apply)
		$this->createEditor();
		$editorBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->editorUserID
			)
		);
		$this->assertNotNull( $this->appliedAlwaysDeny->checkBookingCompliance( $editorBooking ) );
		// now we add the editor role to the excluded roles
		$this->appliedAlwaysDeny->setExcludedRoles( array( 'editor' ) );
		$this->assertNull( $this->appliedAlwaysDeny->checkBookingCompliance( $editorBooking ) );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->createSubscriber();
		$this->firstTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ) )
		);
		$this->alwaysallow      = new BookingRule(
			'alwaysAllow',
			'Always allow',
			'Rule will always evaluate to null',
			'Rule did not evaluate to null',
			function ( \CommonsBooking\Model\Booking $booking ) {
				return null;
			}
		);
		$this->alwaysdeny       = new BookingRule(
			'alwaysDeny',
			'Always deny',
			'Rule will always deny and return the current booking as conflict',
			'Rule evaluated correctly',
			function ( \CommonsBooking\Model\Booking $booking ) {
				return array( $booking );
			}
		);
		$this->firstTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ) )
		);
		$this->setUpTestBooking();
		$this->appliedAlwaysAllow = new BookingRuleApplied( $this->alwaysallow );
		$this->appliedAlwaysAllow->setAppliesToWhat( true );
		$this->appliedAlwaysDeny = new BookingRuleApplied( $this->alwaysdeny );
		$this->appliedAlwaysDeny->setAppliesToWhat( true );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
