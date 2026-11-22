<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Restriction;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Restriction as RestrictionPostType;

class RestrictionDateFormatTest extends CustomPostTypeTest {

	/**
	 * Regression test: with a non-German locale and the d/m/Y date format, a day > 12
	 * was deleted on save and a day <= 12 had day and month swapped.
	 */
	public function testSaveRestrictionWithDayFirstDateFormat() {
		add_filter( 'locale', fn() => 'fr_FR' );
		update_option( 'date_format', 'd/m/Y' );
		$restrictionId = $this->createRestriction( Restriction::TYPE_REPAIR, $this->locationId, $this->itemId, strtotime( self::CURRENT_DATE ), null );

		( new RestrictionPostType() )->registerMetabox();
		cmb2_get_metabox( RestrictionPostType::getPostType() . '-custom-fields' )->save_fields(
			$restrictionId,
			'post',
			[
				Restriction::META_START => [
					'date' => '09/11/2026',
					'time' => '00:00',
				],
				Restriction::META_END   => [
					'date' => '16/11/2026',
					'time' => '23:55',
				],
			]
		);

		$this->assertEquals( strtotime( '2026-11-09 00:00' ), get_post_meta( $restrictionId, Restriction::META_START, true ) );
		$this->assertEquals( strtotime( '2026-11-16 23:55' ), get_post_meta( $restrictionId, Restriction::META_END, true ) );
	}
}
