<?php


namespace CommonsBooking\View;


use CommonsBooking\Helper\Wordpress;
use DateTime;
use Exception;
use DatePeriod;
use DateInterval;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Repository\Timeframe;

/**
 * The TimeframeExport class handles the download of timeframe data as CSV.
 * This also includes booking data (bookings used to be a type of timeframe).
 * This is used by users to export all their data or a time range of their data for statistical analysis.
 * This can not be used for backups, as it does not include all data and there is no way to import it again.
 */
class TimeframeExport {

	/**
	 * @param $field_args
	 * @param $field
	 */
	public static function renderExportForm( $field_args, $field ) {
		?>
        <div class="cmb-row cmb-type-text ">
            <div class="cmb-th">
                <label for="timeframe-export"><?php echo esc_html__( 'Download CSV', 'commonsbooking' ); ?></label>
            </div>
            <div class="cmb-td">
                <button type="submit" id="timeframe-export" class="button button-secondary" name="submit-cmb"
                        value="download-export">
					<?php echo esc_html__( 'Download Export', 'commonsbooking' ); ?>
                </button>
            </div>
        </div>
		<?php
	}

	/**
	 * @param string $outputFile
	 *
	 * @throws Exception
	 */
	public static function exportCsv( string $outputFile = 'php://output' ) {
		$exportFilename = 'timeframe-export-' .  date('Y-m-d-H-i-s') . '.csv';

		$inputFields = [
			'location' => self::getInputFields( 'location-fields' ),
			'item'     => self::getInputFields( 'item-fields' ),
			'user'     => self::getInputFields( 'user-fields' )
		];

		if ( $outputFile == 'php://output' ) {
			$timeframes = self::getExportData();

			// output headers so that the file is downloaded rather than displayed
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $exportFilename );
		} else {
			$timeframes = self::getExportData( true );
			$outputFile = $outputFile . $exportFilename;
		}

		// create a file pointer connected to the output stream
		$output = fopen( $outputFile, 'w' );

		$headline = false;

		/** @var \CommonsBooking\Model\Timeframe $timeframePost */
		foreach ( $timeframes as $timeframePost ) {
			$timeframeData = self::getTimeframeData( $timeframePost );

			if ( ! $headline ) {
				$headline    = true;
				$headColumns = array_keys( $timeframeData );

				// Iterate through in put fields
				foreach ( $inputFields as $type => $fields ) {
					$columnNames = $fields;
					array_walk( $columnNames, function ( &$item ) use ( $type ) {
						$item = $type . ': ' . $item;
					} );
					$headColumns = array_merge( $headColumns, $columnNames );
				}

				// output the column headings
				fputcsv( $output, $headColumns, ";" );
			}

			// output the column values
			$valueColumns = array_values( $timeframeData );

			// Get values for user defined input fields.
			foreach ( $inputFields as $type => $fields ) {
				// Location fields
				if ( $type == 'location' ) {
					$location = $timeframePost->getLocation();
					foreach ( $fields as $field ) {
						$valueColumns[] = $location->getFieldValue( $field );
					}
				}

				// Item fields
				if ( $type == 'item' ) {
					$item = $timeframePost->getItem();
					foreach ( $fields as $field ) {
						$valueColumns[] = $item->getFieldValue( $field );
					}
				}

				// User fields
				if ( $type == 'user' ) {
					$user = $timeframePost->getUserData();
					foreach ( $fields as $field ) {
						$valueColumns[] = $user->get( $field );
					}
				}
			}

			fputcsv( $output, $valueColumns, ";" );
		}

		fclose( $output );
		exit();
	}

	/**
	 * Return user defined export fields.
	 *
	 * @param $inputName
	 *
	 * @return false|string[]
	 */
	protected static function getInputFields( $inputName ) {
		$inputFieldsString =
			array_key_exists( $inputName, $_REQUEST ) ? sanitize_text_field( $_REQUEST[ $inputName ] ) :
				Settings::getOption( 'commonsbooking_options_export', '$inputName' );

		return array_filter( explode( ',', $inputFieldsString ) );
	}

	/**
	 * Returns data for export.
	 *
	 * @param false $isCron
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getExportData( bool $isCron = false ): array {
		if ( $isCron ) {
			$timerange = Settings::getOption( 'commonsbooking_options_export', 'export-timerange' );
			$start     = date( 'd.m.Y' );
			$end       = date( 'd.m.Y', strtotime( '+' . $timerange . ' day' ) );
		} else {
			$start = commonsbooking_sanitizeHTML( $_REQUEST['export-timerange-start'] );
			$end   = commonsbooking_sanitizeHTML( $_REQUEST['export-timerange-end'] );
		}

		// Timerange
		$period = self::getPeriod( $start, $end );

		// Types
		$type = self::getType();

		$timeframes = [];
		foreach ( $period as $dt ) {
			$dayTimeframes = Timeframe::get(
				[],
				[],
				$type ? [$type] : [],
				$dt->format( "Y-m-d" ),
				true,
				null,
				[ 'canceled', 'confirmed', 'unconfirmed', 'publish', 'inherit' ]
			);
			foreach ( $dayTimeframes as $timeframe ) {
				$timeframes[ $timeframe->ID ] = $timeframe;
			}
		}

		return $timeframes;
	}

	protected static function getPeriod( $start, $end ) {
		// Timerange
		$begin = Wordpress::getUTCDateTime( $start );
		$end   = Wordpress::getUTCDateTime( $end );

		$interval = DateInterval::createFromDateString( '1 day' );

		return new DatePeriod( $begin, $interval, $end );
	}

	/**
	 * Returns selected timeframe type id.
	 * @return int
	 */
	protected static function getType(): int {
		$type = 0;

		// Backend download
		if ( array_key_exists( 'export-type', $_REQUEST ) && $_REQUEST['export-type'] !== 'all' ) {
			$type = intval( $_REQUEST['export-type'] );
		} else {
			//cron download
			$configuredType = Settings::getOption( 'commonsbooking_options_export', 'export-type' );
			if ( $configuredType && $configuredType != 'all' ) {
				$type = intval( $configuredType );
			}
		}

		return $type;
	}

	/**
	 * Prepares timeframe data array.
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframePost
	 *
	 * @return array
	 */
	protected static function getTimeframeData( \CommonsBooking\Model\Timeframe $timeframePost ): array {
		$timeframeData = self::getRelevantTimeframeFields( $timeframePost );

		// Timeframe typ
		$timeframeTypeId       = $timeframePost->getFieldValue( 'type' );
		$timeframetypes        = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTypes();
		$timeframeData['type'] = array_key_exists( $timeframeTypeId, $timeframetypes ) ?
			$timeframetypes[ $timeframeTypeId ] : __( 'Unknown', 'commonsbooking' );

		if ( $timeframeTypeId == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ) {
			$booking = new \CommonsBooking\Model\Booking( $timeframePost->ID );
		}

		// Repetition option
		$repetitions                           = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getTimeFrameRepetitions();
		$repetitionId                          = $timeframePost->getFieldValue( "timeframe-repetition" );
		$timeframeData["timeframe-repetition"] = array_key_exists( $repetitionId, $repetitions ) ?
			$repetitions[ $repetitionId ] : __( 'Unknown', 'commonsbooking' );

		// Grid option
		$gridOptions           = \CommonsBooking\Wordpress\CustomPostType\Timeframe::getGridOptions();
		$gridOptionId          = $timeframePost->getGrid();
		$timeframeData["grid"] = array_key_exists( $gridOptionId, $gridOptions ) ?
			$gridOptions[ $gridOptionId ] : __( 'Unknown', 'commonsbooking' );

		// get corresponding item title
		$item = $timeframePost->getItem();
		if ($item != null){
			$item_title = $item->post_title;
		}
		else {
			$item_title = __( 'Unknown', 'commonsbooking' );
		}

		// get corresponding location title
		$location = $timeframePost->getLocation();
		if ($location != null){
			$location_title = $location->post_title;
		}
		else {
			$location_title = __( 'Unknown', 'commonsbooking' );
		}

		// populate simple meta fields
		$timeframeData[ \CommonsBooking\Model\Timeframe::META_MAX_DAYS ]  = $timeframePost->getFieldValue( \CommonsBooking\Model\Timeframe::META_MAX_DAYS );
		$timeframeData["full-day"]            = $timeframePost->getFieldValue( "full-day" );
		$timeframeData[\CommonsBooking\Model\Timeframe::REPETITION_START] =
			$timeframePost->getStartDate() ?
				date( 'c', $timeframePost->getStartDate() ) : '';
		$timeframeData[\CommonsBooking\Model\Timeframe::REPETITION_END] =
			$timeframePost->getEndDate() ?
				date( 'c', $timeframePost->getEndDate() ) : '';
		$timeframeData["start-time"]          = $timeframePost->getStartTime();
		$timeframeData["end-time"]            = $timeframePost->getEndTime();
		$timeframeData["pickup"]              = isset( $booking ) ? $booking->pickupDatetime() : "";
		$timeframeData["return"]              = isset( $booking ) ? $booking->returnDatetime() : "";
		$timeframeData["booking-code"]        = $timeframePost->getFieldValue( "_cb_bookingcode" );
		$timeframeData["location-post_title"] = $location_title;
		$timeframeData["item-post_title"]     = $item_title;
		$timeframeData["user-firstname"]      = $timeframePost->getUserData()->first_name;
		$timeframeData["user-lastname"]       = $timeframePost->getUserData()->last_name;
		$timeframeData["user-login"]          = $timeframePost->getUserData()->user_login;
		$timeframeData["comment"]             = $timeframePost->getFieldValue('comment');

		return $timeframeData;
	}

	/**
	 * Removes not relevant fields from timeframedata.
	 *
	 * @param $timeframe
	 *
	 * @return array
	 */
	protected static function getRelevantTimeframeFields( $timeframe ) {
		$postArray               = get_object_vars( $timeframe->getPost() );
		$relevantTimeframeFields = [
			'ID',
			'post_title',
			"post_author",
			"post_date",
			"post_date_gmt",
			"post_content",
			"comment",
			"post_excerpt",
			"post_status",
			"post_name"
		];

		return array_filter(
			$postArray,
			function ( $key ) use ( $relevantTimeframeFields ) {
				return in_array( $key, $relevantTimeframeFields );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

}
