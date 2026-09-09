<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Item;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class ItemTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// Create timeframe with location and item, so that we can search for it
		$this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( 'midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ) )
		);
	}

	public function testGetByCloakedId(): void {
		$itemModel = new \CommonsBooking\Model\Item( $this->itemId );

		// basic test, without any booking
		$cloakedId = $itemModel->getCloakedId();
		$this->assertEquals( $this->itemId, Item::getByCloakedId( $cloakedId )->ID );

		// with booking in past
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) )
		);
		$bookingCloakedId = $itemModel->getCloakedId();
		$this->assertNotEquals( $bookingCloakedId, $cloakedId );
		$this->assertEquals( $this->itemId, Item::getByCloakedId( $bookingCloakedId )->ID );

		// test for invalid cloaked id
		$this->assertNull( Item::getByCloakedId( 'invalid-cloaked-id' ) );
	}

	public function testGetByLocation(): void {
		$this->assertEquals(
			[ $this->itemId ],
			array_map(
				fn( $item ) => $item->ID,
				Item::getByLocation( $this->locationId, true )
			)
		);
	}

	public function testGetPostByIdDoesNotFallBackToGlobalPostForEmptyId(): void {
		$previousPost = $GLOBALS['post'] ?? null;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reproduce WordPress' empty get_post() fallback.
		$GLOBALS['post'] = get_post( $this->itemId );

		try {
			$this->assertNull( Item::getPostById( null ) );
		} finally {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the shared test state.
			$GLOBALS['post'] = $previousPost;
		}
	}
}
