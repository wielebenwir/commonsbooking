<?php


namespace CommonsBooking\View;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Settings\Settings;
use CommonsBooking\ScssPhp\ScssPhp\Compiler;
use CommonsBooking\ScssPhp\ScssPhp\ValueConverter;
use Exception;

/**
 * Serves as abstraction for view rendering of different models and custom post types.
 *
 * Important design decision/assumption: Because there can be multiple timeframes with different configurations,
 * which make rendering the presentation of them (e.g. in calendar view) more complicated. We just stick to the first
 * timeframe when rendering a view.
 */
abstract class View {

	/**
	 * List of allowed query params for shortcodes.
	 * All other query params will be ignored.
	 *
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
		'offset'         => '',
		// Order: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
		'order'          => '',
		'orderby'        => '',
	);

	/**
	 * Will generate the shortcode view for only one given item or location.
	 * This includes the availability timeframe, see assumptions in class docstring for more details.
	 *
	 * @param \CommonsBooking\Model\Item|\CommonsBooking\Model\Location $cpt location or item model object to retrieve timeframe data from.
	 * @param string                                                    $type 'Item' or 'Location'.
	 * @return array
	 * @throws Exception
	 */
	public static function getShortcodeData( $cpt, string $type ): array {
		$cptData    = [];
		$timeframes = $cpt->getBookableTimeframes( true );

		// sort by start date, to get latest possible booking date by first timeframe
		usort(
			$timeframes,
			function ( $a, $b ) {
				return $a->getStartDate() <=> $b->getStartDate();
			}
		);
		$latestPossibleBookingDate = false;

		/** @var Timeframe $timeframe */
		foreach ( $timeframes as $timeframe ) {
			if ( ! $timeframe->getStartDate() ) {
				continue;
			}

			// We only fetch the latest possible booking date from the first timeframe.
			// This is ok, because the timeframes are sorted by their start date.
			if ( ! $latestPossibleBookingDate ) {
				$latestPossibleBookingDate = $timeframe->getLatestPossibleBookingDateTimestamp();
			}

			// If start date is after latest possible booking date, we leave range out
			$endOfStartDay = strtotime( '+1 day midnight', $timeframe->getStartDate() ) - 1;
			if ( $endOfStartDay > $latestPossibleBookingDate ) {
				continue;
			}

			$item = $timeframe->{'get' . $type}();

			// We need only published items
			if ( ! $item || $item->post_status !== 'publish' ) {
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

			// Remove duplicate ranges
			$cptData[ $item->ID ]['ranges'] = array_unique( $cptData[ $item->ID ]['ranges'], SORT_REGULAR );

			// sort ranges by starting date
			usort(
				$cptData[ $item->ID ]['ranges'],
				function ( $a, $b ) {
					return $a['start_date'] <=> $b['start_date'];
				}
			);
		}

		return $cptData;
	}

	/**
	 * Compiles the user defined color scheme from settings (templates) using SCSSPHP and returns it
	 *
	 * @return string|false
	 */
	public static function getColorCSS() {
		$compiler    = new Compiler();
		$var_import  = COMMONSBOOKING_PLUGIN_DIR . 'assets/global/sass/partials/_variables.scss';
		$import_path = COMMONSBOOKING_PLUGIN_DIR . 'assets/public/sass/partials/';
		$compiler->setImportPaths( $import_path );

		$variables = [
			'color-primary' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_primarycolor' ),
			'color-secondary' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_secondarycolor' ),
			'color-buttons'   => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_buttoncolor' ),
			'color-accept' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_acceptcolor' ),
			'color-cancel' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_cancelcolor' ),
			'color-holiday' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_holidaycolor' ),
			'color-greyedout' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_greyedoutcolor' ),
			'color-bg' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_backgroundcolor' ),
			'color-noticebg' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_noticebackgroundcolor' ),
			'color-lighttext' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_lighttext' ),
			'color-darktext' => Settings::getOption( 'commonsbooking_options_templates', 'colorscheme_darktext' ),
		];

		foreach ( $variables as &$variable ) { // iterate over array, convert valid values.
			if ( $variable ) {  // values are only converted when set so ValueParser does not throw an error
				$variable = ValueConverter::parseValue( $variable );
			} else {
				return false; // do not return CSS when no values are set
			}
		}

		$compiler->replaceVariables( $variables );
		$content = '@import "' . $var_import . '";';
		$result  = $compiler->compileString( $content );
		$css     = $result->getCss();

		if ( ! empty( $css ) ) {
			return $css;
		} else {
			return false;
		}
	}
}
