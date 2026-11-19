<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking as BookingPostType;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use CommonsBooking\Wordpress\CustomPostType\Restriction as RestrictionPostType;

/**
 * The date fields of restrictions and bookings (CMB2 text_datetime_timestamp) are displayed
 * in a locale dependent format: d.m.Y (de_*), m/d/Y (en_*) or d/m/Y (every other locale).
 * When saving, CMB2 falls back to strtotime(), which reads every date with slashes as m/d/Y.
 * For d/m/Y this deleted dates with a day > 12 and swapped day and month otherwise.
 */
class DateFieldLocaleTest extends CustomPostTypeTest {

	/**
	 * Dates as the datepicker writes them for 9 and 16 November 2026.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function provideDatesPerLocale(): array {
		return [
			'fr_FR uses d/m/Y'                   => [ 'fr_FR', '09/11/2026', '16/11/2026' ],
			'nl_NL uses d/m/Y'                   => [ 'nl_NL', '09/11/2026', '16/11/2026' ],
			'de_DE uses d.m.Y'                   => [ 'de_DE', '09.11.2026', '16.11.2026' ],
			'en_US uses m/d/Y'                   => [ 'en_US', '11/09/2026', '11/16/2026' ],
			'fr_FR, ISO date typed in by hand'   => [ 'fr_FR', '2026/11/09', '2026/11/16' ],
		];
	}

	/**
	 * @dataProvider provideDatesPerLocale
	 */
	public function testRestrictionDatesAreReadInFieldFormat( string $locale, string $startDate, string $endDate ) {
		$this->switchLocale( $locale );
		$restrictionId = $this->createRestriction(
			Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);

		$this->getMetabox( new RestrictionPostType() )->save_fields(
			$restrictionId,
			'post',
			[
				Restriction::META_START => [
					'date' => $startDate,
					'time' => '00:00',
				],
				Restriction::META_END   => [
					'date' => $endDate,
					'time' => '23:55',
				],
			]
		);

		$this->assertSame(
			[
				Restriction::META_START => '2026-11-09 00:00',
				Restriction::META_END   => '2026-11-16 23:55',
			],
			$this->getSavedDates( $restrictionId, [ Restriction::META_START, Restriction::META_END ] )
		);
	}

	/**
	 * @dataProvider provideDatesPerLocale
	 */
	public function testBookingDatesAreReadInFieldFormat( string $locale, string $startDate, string $endDate ) {
		$this->switchLocale( $locale );
		$bookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);

		$this->getMetabox( new BookingPostType() )->save_fields(
			$bookingId,
			'post',
			[
				Timeframe::REPETITION_START => [
					'date' => $startDate,
					'time' => '08:30',
				],
				Timeframe::REPETITION_END   => [
					'date' => $endDate,
					'time' => '23:59',
				],
			]
		);

		$this->assertSame(
			[
				Timeframe::REPETITION_START => '2026-11-09 08:30',
				Timeframe::REPETITION_END   => '2026-11-16 23:59',
			],
			$this->getSavedDates( $bookingId, [ Timeframe::REPETITION_START, Timeframe::REPETITION_END ] )
		);
	}

	/**
	 * Emptying the field must still remove the date, as before.
	 */
	public function testEmptyRestrictionDateIsRemoved() {
		$this->switchLocale( 'fr_FR' );
		$restrictionId = $this->createRestriction(
			Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);

		$this->getMetabox( new RestrictionPostType() )->save_fields(
			$restrictionId,
			'post',
			[
				Restriction::META_END => [
					'date' => '',
					'time' => '',
				],
			]
		);

		$this->assertSame( '', get_post_meta( $restrictionId, Restriction::META_END, true ) );
	}

	/**
	 * Impossible dates (31 February) are not silently moved to another day.
	 */
	public function testImpossibleDateIsNotRolledOver() {
		$this->switchLocale( 'fr_FR' );
		$restrictionId = $this->createRestriction(
			Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);

		$this->getMetabox( new RestrictionPostType() )->save_fields(
			$restrictionId,
			'post',
			[
				Restriction::META_END => [
					'date' => '31/02/2026',
					'time' => '00:00',
				],
			]
		);

		$this->assertNotSame(
			'2026-03-03 00:00',
			$this->getSavedDates( $restrictionId, [ Restriction::META_END ] )[ Restriction::META_END ]
		);
	}

	private function switchLocale( string $locale ): void {
		add_filter(
			'locale',
			function () use ( $locale ) {
				return $locale;
			}
		);
	}

	/**
	 * Registers the metabox of the post type (with the fields for the current locale) and returns it.
	 */
	private function getMetabox( CustomPostType $postType ): \CMB2 {
		$postType->registerMetabox();
		$cmb = cmb2_get_metabox( $postType::getPostType() . '-custom-fields' );
		$this->assertInstanceOf( \CMB2::class, $cmb );

		return $cmb;
	}

	/**
	 * @param string[] $metaKeys
	 *
	 * @return array<string, string> Saved dates as Y-m-d H:i, 'deleted' if the meta is gone.
	 */
	private function getSavedDates( int $postId, array $metaKeys ): array {
		$dates = [];
		foreach ( $metaKeys as $metaKey ) {
			$saved             = get_post_meta( $postId, $metaKey, true );
			$dates[ $metaKey ] = '' === $saved ? 'deleted' : gmdate( 'Y-m-d H:i', (int) $saved );
		}

		return $dates;
	}
}
