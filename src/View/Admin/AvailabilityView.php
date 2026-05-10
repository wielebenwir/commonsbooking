<?php

namespace CommonsBooking\View\Admin;

use CommonsBooking\Model\Timeframe;

/**
 * Admin list view showing all timeframes with their linked Item and Location.
 */
class AvailabilityView {

	/**
	 * Renders the availability list page.
	 *
	 * Called by add_submenu_page() as the page callback.
	 *
	 * @return void
	 */
	public static function index(): void {
		global $templateData;

		$templateData = [];

		$query = new \WP_Query(
			[
				'post_type'      => \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType,
				'post_status'    => [ 'publish', 'pending', 'draft', 'private' ],
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$timeframes = [];
		foreach ( $query->posts as $tfPost ) {
			$itemId     = (int) get_post_meta( $tfPost->ID, Timeframe::META_ITEM_ID, true );
			$locationId = (int) get_post_meta( $tfPost->ID, Timeframe::META_LOCATION_ID, true );
			$startTs    = get_post_meta( $tfPost->ID, Timeframe::REPETITION_START, true );
			$endTs      = get_post_meta( $tfPost->ID, Timeframe::REPETITION_END, true );

			$timeframes[] = [
				'timeframe'    => $tfPost,
				'item'         => $itemId ? get_post( $itemId ) : null,
				'location'     => $locationId ? get_post( $locationId ) : null,
				'start_date'   => $startTs ? date_i18n( get_option( 'date_format' ), (int) $startTs ) : '',
				'end_date'     => $endTs ? date_i18n( get_option( 'date_format' ), (int) $endTs ) : '',
			];
		}

		$templateData['timeframes'] = $timeframes;

		ob_start();
		commonsbooking_sanitizeHTML( commonsbooking_get_template_part( 'availabilityview', 'index' ) );
		echo ob_get_clean();
	}
}
