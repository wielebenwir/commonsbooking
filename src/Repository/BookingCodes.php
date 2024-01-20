<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Exception\BookingCodeException;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use DateInterval;
use DatePeriod;

/**
 *  This class generates booking codes for a timeframe.
 *  Currently, booking codes can only be created for timeframes with an end date and where one slot fills the whole day.
 */
class BookingCodes {

	/**
	 * Table name of booking codes.
	 * @var string
	 */
	public static string $tablename = 'cb_bookingcodes';

	/**
	 * Days to advance generation of booking codes.
	 * @var int
	 */
	public const ADVANCE_GENERATION_DAYS = 365;

	/**
	 * Returns booking codes for timeframe to display in backend Timeframe window.
	 *
	 * @param int $timeframeId - ID of timeframe to get codes for
	 * @param int|null $startDate - Where to get booking codes from (timestamp)
	 * @param int|null $endDate - Where to get booking codes to (timestamp)
	 * @param int $advanceGenerationDays - Open-ended timeframes: If 0, generate code(s) until today. If >0, generate additional codes after today
	 *
	 * @return array
	 * @throws BookingCodeException
	 */
	public static function getCodes( int $timeframeId, int $startDate = null, int $endDate = null, int $advanceGenerationDays = self::ADVANCE_GENERATION_DAYS ): array {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$timeframe          = new Timeframe( $timeframeId );
			$timeframeStartDate = $timeframe->getStartDate();
			$timeframeEndDate   = $timeframe->getRawEndDate();

			// If timeframe does not qualify for booking codes, return empty array
			if ( ! $timeframe->bookingCodesApplicable() ){
				return [];
			}

			if ( ! $startDate || $startDate < $timeframeStartDate ) {
				$startDate = $timeframeStartDate;
			}

			if ($timeframeEndDate && (! $endDate || $endDate > $timeframeEndDate ) ) {
				$endDate = $timeframeEndDate;
			}
			//when we still don't have an end-date, we will just get the coming ADVANCE_GENERATION_DAYS (should default to 365 days)
			if (! $endDate ) {
				$endDate = strtotime( '+' . $advanceGenerationDays . ' days', null ); // null means date and time now
				// a code will be generated for $endDate because time generally > 00:00:00 (due to initialitaion with current date and time)
			}

			$startDate = date( 'Y-m-d', $startDate );
			$endDate   = date( 'Y-m-d', $endDate );

			//check, if we have enough codes for the timeframe or if we need to generate more
			//we only need to check, if we have an open-ended timeframe
			//we check, if the end date of the last generated code is before the end date of the requested time period
			if ( ! $timeframe->getRawEndDate() && self::getLastCode( $timeframe )
				&& strtotime( self::getLastCode( $timeframe )->getDate() ) < strtotime( $endDate )
			) {
				$startGenerationPeriod = new \DateTime( self::getLastCode($timeframe)->getDate() );
				$endGenerationPeriod = new \DateTime( $endDate );
				// set $endGenerationPeriod's time > 00:00:00 such that $endDate is included in DatePeriod iteration
				// and code is generated for $endDate
				$endGenerationPeriod->setTime(0, 0, 1);
				static::generatePeriod( $timeframe,
					new DatePeriod(
						$startGenerationPeriod,
						new DateInterval( 'P1D' ),
						$endGenerationPeriod,
					)
				);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . self::$tablename;

			$sql = $wpdb->prepare(
				"SELECT * FROM $table_name
                WHERE item = %d
                AND date BETWEEN %s AND %s
                ORDER BY item ASC ,date ASC
            	",
				$timeframe->getItem()->ID,
				$startDate,
				$endDate
			);
			$bookingCodes = $wpdb->get_results($sql);

			$codes = [];
			foreach ( $bookingCodes as $bookingCode ) {
				$bookingCodeObject = new BookingCode(
					$bookingCode->date,
					$bookingCode->item,
					$bookingCode->location,
					$bookingCode->timeframe,
					$bookingCode->code
				);
				$codes[]           = $bookingCodeObject;
			}

			Plugin::setCacheItem( $codes, [$timeframeId] );

			return $codes;
		}
	}

	/**
	 * Gets a specific booking code by item ID and date.
	 *
	 * @param int $itemId - ID of item attached to timeframe
	 * @param string $date - Date in format Y-m-d
	 *
	 * @return BookingCode|null
	 */
	private static function lookupCode(int $itemId, string $date): ?BookingCode {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tablename;

		$sql = $wpdb->prepare(
			"SELECT * FROM $table_name
			WHERE 
				item = %s AND 
				date = %s
			ORDER BY item ASC, date ASC
			LIMIT 1",
			$itemId,
			$date
		);

		$bookingCodes = $wpdb->get_results($sql);

		if (count($bookingCodes)) {
			return new BookingCode(
				$bookingCodes[0]->date,
				$bookingCodes[0]->item,
				$bookingCodes[0]->location,
				$bookingCodes[0]->timeframe,
				$bookingCodes[0]->code
			);
		}

		return null;
	}

	/**
	 * Returns booking code by timeframe, location, item and date. If no code exist yet and timeframe does not have end-date, generate it.
	 *
	 * @param Timeframe $timeframe - Timeframe object to get code for
	 * @param int $itemId - ID of item attached to timeframe
	 * @param int $locationId - ID of location attached to timeframe
	 * @param string $date - Date in format Y-m-d
	 * @param int $advanceGenerationDays - Open-ended timeframes: If 0, generates code(s) until $date. If >0, generate additional codes after $date.
	 *
	 * @return BookingCode|null
	 * @throws BookingCodeException
	 */
	public static function getCode( Timeframe $timeframe, int $itemId, int $locationId, string $date, int $advanceGenerationDays = self::ADVANCE_GENERATION_DAYS ) : ?BookingCode {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {

			$bookingCodeObject = static::lookupCode($itemId, $date);

			if ( ! $bookingCodeObject ) {
				//when we have a timeframe without end-date we generate as many codes as we need
				if (! $timeframe->getRawEndDate() && $timeframe->bookingCodesApplicable() ) {
					$begin = $timeframe->getUTCStartDateDateTime();
					$endDate = new \DateTime($date);
					$endDate->modify('+' . $advanceGenerationDays . ' days');
					// set $endDate's time > 00:00:00 so it will included in DatePeriod iteration and a code will be
					// generated for $endDate
					$endDate->setTime(0, 0, 1);
					$interval = DateInterval::createFromDateString( '1 day' );
					$period = new DatePeriod( $begin, $interval, $endDate );
					static::generatePeriod($timeframe,$period);
					$bookingCodeObject = static::lookupCode($itemId, $date);
				}
			}

			Plugin::setCacheItem( $bookingCodeObject, [$timeframe->ID] );

			return $bookingCodeObject;
		}
	}

	/**
	 * Will get the last booking code that was generated for a given timeframe, item and location.
	 * This can be used to determine if we need to generate new codes.
	 *
	 * @param Timeframe $timeframe
	 * @param int $itemId
	 * @param int $locationId
	 *
	 * @return BookingCode|null
	 */
	public static function getLastCode(Timeframe $timeframe) : ?BookingCode {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tablename;

		$sql = $wpdb->prepare(
			"SELECT * FROM $table_name
			WHERE 
				timeframe = %s
			ORDER BY date DESC",
			$timeframe->ID
		);
		$bookingCodes = $wpdb->get_results($sql);

		$bookingCodeObject = null;
		if ( count( $bookingCodes ) ) {
			$bookingCodeObject = new BookingCode(
				$bookingCodes[0]->date,
				$bookingCodes[0]->item,
				$bookingCodes[0]->location,
				$bookingCodes[0]->timeframe,
				$bookingCodes[0]->code
			);
		}

		return $bookingCodeObject;
	}

	/**
	 * Creates booking-codes table;
	 */
	public static function initBookingCodesTable() :void {
		global $wpdb;
		global $cb_db_version;

		$table_name      = $wpdb->prefix . self::$tablename;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code) 
        ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		add_option( COMMONSBOOKING_PLUGIN_SLUG . '_bookingcodes_db_version', $cb_db_version );
	}

    /**
     * Generates booking codes for timeframe.
     *
     * @param Timeframe $timeframe
     * @param int $advanceGenerationDays - Open-ended timeframes: If 0, generate code(s) until today. If >0, generate additional codes after today.
	 *
     * @return bool
     * @return bool
     * @throws BookingCodeException
     */
	public static function generate( Timeframe $timeframe, int $advanceGenerationDays = self::ADVANCE_GENERATION_DAYS ): bool {

		if (! $timeframe->bookingCodesApplicable() ){
			return false;
		}
		$begin = Wordpress::getUTCDateTime();
		$begin->setTimestamp( $timeframe->getStartDate() );
		if ($timeframe->getRawEndDate()){
			$end = Wordpress::getUTCDateTime();
			$end->setTimestamp( $timeframe->getRawEndDate() );
			// set $end's time > 00:00:00 such that DatePeriod-iteration will include $end:
			$end->setTime(0, 0, 1);
		}
		else {
			$end = new \DateTime();
			$end->modify( '+' . $advanceGenerationDays . 'days');
			// a code will be generated for the date in $end because its time generally > 00:00:00 (due to initialitaion with current date and time)
		}

		$interval = DateInterval::createFromDateString( '1 day' );
		$period   = new DatePeriod( $begin, $interval, $end );

		return static::generatePeriod( $timeframe, $period );
	}

	/**
	 * Generate booking codes for a given period
	 * @param Timeframe $timeframe
	 * @param DatePeriod $period
	 *
	 * @return true
	 * @throws BookingCodeException
	 */
	private static function generatePeriod( Timeframe $timeframe, DatePeriod $period): bool {

		$bookingCodesArray = static::getCodesArray();
		if (! $bookingCodesArray ){
			throw new BookingCodeException( __( "No booking codes could be created because there were no booking codes to choose from. Please set some booking codes in the CommonsBooking settings.", 'commonsbooking' )  );
		}
		// Before we add new codes, we remove old ones, that are not relevant anymore
		try {
			//TODO #507
			$location = $timeframe->getLocation();
		} catch ( \Exception $e ) {
			throw new BookingCodeException( __( "No booking codes could be created because the location of the timeframe could not be found.", 'commonsbooking' )  );
		}
		try {
			//TODO #507
			$item = $timeframe->getItem();
		} catch ( \Exception $e ) {
			throw new BookingCodeException( __( "No booking codes could be created because the item of the timeframe could not be found.", 'commonsbooking' )  );
		}

		$bookingCodesRandomizer = intval( $timeframe->ID );
		$bookingCodesRandomizer += $item->ID;
		$bookingCodesRandomizer += $location->ID;

		foreach ( $period as $dt ) {
			$day = new Day( $dt->format( 'Y-m-d' ) );
			if ( $day->isInTimeframe( $timeframe ) ) {

				// Check if a code already exists, if so DO NOT generate new
				if ( static::lookupCode($item->ID, $dt->format( 'Y-m-d' )) ) {
					continue;
				}

				$bookingCode = new BookingCode(
					$dt->format( 'Y-m-d' ),
					$item->ID,
					$location->ID,
					$timeframe->ID,
					$bookingCodesArray[ ( (int) $dt->format( 'z' ) + $bookingCodesRandomizer ) % count( $bookingCodesArray ) ]
				);
				self::persist( $bookingCode );
			}
		}

		return true;
	}

	/**
	 * @param BookingCode $bookingCode
	 *
	 * @return mixed
	 */
	public static function persist( BookingCode $bookingCode ) {
		global $wpdb;
		$wpdb->show_errors( 0 );
		$table_name = $wpdb->prefix . self::$tablename;

		$result = $wpdb->replace(
			$table_name,
			array(
				'timeframe' => $bookingCode->getTimeframe(),
				'date'      => $bookingCode->getDate(),
				'location'  => $bookingCode->getLocation(),
				'item'      => $bookingCode->getItem(),
				'code'      => $bookingCode->getCode()
			)
		);
		$wpdb->show_errors( 1 );

		return $result;
	}

	/**
	 * Will get the configured booking codes from the settings and return them as an array.
	 *
	 * @return array - Array of booking codes, empty array if no booking codes are configured.
	 */
	private static function getCodesArray(): array {
		$bookingCodes      = Settings::getOption( 'commonsbooking_options_bookingcodes', 'bookingcodes' );
		if ( ! $bookingCodes ) {
			return array();
		}
		$bookingCodesArray = array_filter( explode( ',', trim( $bookingCodes ) ) );
		return array_map( function ( $item ) {
			return preg_replace( "/\r|\n/", "", $item );
		}, $bookingCodesArray );
	}

	/**
	 * Deletes booking codes for current post or if posted for post with $postId.
	 *
	 * @param null $postId
	 */
	public static function deleteBookingCodes( $postId = null ) {
		if ( $postId ) {
			$post = get_post( $postId );
		} else {
			global $post;
		}
		if (
			$post &&
			$post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType
		) {
			global $wpdb;
			$table_name = $wpdb->prefix . self::$tablename;


			$query = $wpdb->prepare( 'SELECT timeframe FROM ' . $table_name . ' WHERE timeframe = %d', $post->ID );
			$var   = $wpdb->get_var( $query );
			if ( $var ) {
				$query2 = $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE timeframe = %d', $post->ID );
				$wpdb->query( $query2 );
			}
		}
	}

}
