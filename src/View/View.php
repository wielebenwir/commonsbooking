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
	 * @param $cpt
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getShortcodeData( $cpt, $type ) {
		$cptData    = [];
		$timeframes = $cpt->getBookableTimeframes( true );
		/** @var Timeframe $timeframe */
		foreach ( $timeframes as $timeframe ) {
			if(!$timeframe->getStartDate()) continue;

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
				foreach ( $cptData[ $item->ID ]['ranges'] as &$range ) {
					// Check if Timeframe overlaps or differs max. 1 day with existing one.
					$overlaps =
						(
							$timeframe->getStartDate() >= ( $range['start_date'] - 86400 ) &&
							$timeframe->getStartDate() <= ( $range['end_date'] + 86400 )
						) ||
						(
							$timeframe->getEndDate() >= ( $range['start_date'] - 86400 ) &&
							$timeframe->getEndDate() <= ( $range['end_date'] + 86400 )
						);

					// If timeframe overlaps, check if we need to extend existing one.
					if ( $overlaps ) {
						if (
							! $range['start_date'] ||
							$range['start_date'] > $timeframe->getStartDate()
						) {
							$range['start_date'] = $timeframe->getStartDate();
						}

						if (
							! $range['end_date'] ||
							$range['end_date'] < $timeframe->getStartDate()
						) {
							$range['end_date'] = $timeframe->getEndDate();
						}
						// Otherwise create new range
					} else {
						$cptData[ $item->ID ]['ranges'][] = [
							'start_date' => $timeframe->getStartDate(),
							'end_date'   => $timeframe->getEndDate(),
						];
					}
				}
			}

			//Remove duplicate ranges
			$cptData[ $item->ID ]['ranges'] = array_unique( $cptData[ $item->ID ]['ranges'], SORT_REGULAR );
		}

		return $cptData;
	}

}
