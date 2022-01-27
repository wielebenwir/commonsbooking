<?php


namespace CommonsBooking\View;

use CommonsBooking\Model\Timeframe;
use Exception;

abstract class View {

	/**
	 * List of allowed query params for shortcodes.
	 * @var string[]
	 */
	protected static $allowedShortCodeArgs = array(
		'p'              => '', // post id
		// Author: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
		'author'         => '',
		'author_name'    => '',
		// Category: https://developer.wordpress.org/reference/classes/wp_query/#category-parameters
		'cat'            => '',
		'category_name'  => '',
		'category_slug'  => '',
		// Tag: https://developer.wordpress.org/reference/classes/wp_query/#tag-parameters
		'tag'            => '',
		'tag_id'         => '',
		// Status https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
		'post_status'    => '',
		// Pagination: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
		'posts_per_page' => '',
		'nopaging'       => '',
		'offset'         => ''
	);

	/**
	 * Generates data needed for shortcode listing.
	 *
	 * @param \CommonsBooking\Model\Item|\CommonsBooking\Model\Location $cpt
	 * @param string $type
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getShortcodeData( $cpt, string $type ): array {
		$cptData    = [];
		$timeframes = $cpt->getBookableTimeframes( true );
		/** @var Timeframe $timeframe */
		foreach ( $timeframes as $timeframe ) {
			if ( ! $timeframe->getStartDate() ) {
				continue;
			}

			$item = $timeframe->{'get' . $type}();

			// We need only published items
			if ( $item->post_status !== 'publish' ) {
				continue;
			}

			// Init Ranges array for new item in array
			if ( ! array_key_exists( $item->ID, $cptData ) ) {
				$cptData[ $item->ID ] = [
					'ranges' => [
						[
							'start_date' => $timeframe->getStartDate(),
							'end_date'   => $timeframe->getEndDate(),
						],

					],
				];
			} else {
				$addRange           = true;
				$timeframeStartDate = $timeframe->getStartDate();
				$timeframeEndDate   = $timeframe->getEndDate();

				foreach ( $cptData[ $item->ID ]['ranges'] as $key => $range ) {
					// Check if Timeframe overlaps or differs max. 1 day with existing one.
					$overlaps =
						// Startdate is in range
						(
							$timeframeStartDate >= ( $range['start_date'] - 86400 ) &&
							$timeframeStartDate <= ( $range['end_date'] + 86400 )
						) ||
						// Enddate is in range
						(
							$timeframeEndDate >= ( $range['start_date'] - 86400 ) &&
							$timeframeEndDate <= ( $range['end_date'] + 86400 )
						) ||
						// Range and Timeframe have no enddate -> must overlap
						(
							$range['end_date'] == false && $timeframeEndDate == false
						);

					// If timeframe overlaps, check if we need to extend existing one.
					if ( $overlaps ) {
						$addRange = false;

						if (
							! $range['start_date'] ||
							$range['start_date'] > $timeframeStartDate
						) {
							$cptData[ $item->ID ]['ranges'][ $key ]['start_date'] = $timeframeStartDate;
						}

						if (
							! $range['end_date'] ||
							$range['end_date'] < $timeframeStartDate
						) {
							$cptData[ $item->ID ]['ranges'][ $key ]['end_date'] = $timeframeEndDate;
						}
					}
				}

				// Only add new range if it's not starting after a repeating timeframe without an enddate
				if ( $addRange ) {
					$cptData[ $item->ID ]['ranges'][] = [
						'start_date' => $timeframeStartDate,
						'end_date'   => $timeframeEndDate,
					];
				}
			}

			//Remove duplicate ranges
			$cptData[ $item->ID ]['ranges'] = array_unique( $cptData[ $item->ID ]['ranges'], SORT_REGULAR );

			//sort ranges by starting date
			usort( $cptData[ $item->ID ]['ranges'], function ( $a, $b ) {
				return $a['start_date'] <=> $b['start_date'];
			} );
		}

		return $cptData;
	}

}
