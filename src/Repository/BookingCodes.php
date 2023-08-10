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
	 *
	 *
	 * @param int $timeframeId - ID of timeframe to get codes for
	 * @param int|null $startDate - Where to get booking codes from (timestamp)
	 * @param int|null $endDate - Where to get booking codes to (timestamp)
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
				$endDate = strtotime( '+' . $advanceGenerationDays . ' days', $startDate );
			}

			$startDate = date( 'Y-m-d', $startDate );
			$endDate   = date( 'Y-m-d', $endDate );

			//check, if we have enough codes for the timeframe or if we need to generate more
			//we only need to check, if we have an open-ended timeframe
			//we check, if the end date of the last generated code is before the end date of the requested time period
			if ( ! $timeframe->getRawEndDate() &&
			     strtotime(self::getLastCode($timeframe)->getDate()) < strtotime($endDate)
			) {
				$startGenerationPeriod = new \DateTime( self::getLastCode($timeframe)->getDate() );
				$endGenerationPeriod = new \DateTime( $endDate );
				$endGenerationPeriod->modify( '+' . $advanceGenerationDays . ' days' );
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
                WHERE timeframe = %d
                AND date BETWEEN %s AND %s
                ORDER BY item ASC ,date ASC
            	",
				$timeframeId,
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
	 * Returns booking code by timeframe, location, item and date.
	 *
	 * @param Timeframe $timeframe - Timeframe object to get code for
	 * @param int $itemId - ID of item attached to timeframe
	 * @param int $locationId - ID of location attached to timeframe
	 * @param string $date - Date in format Y-m-d
	 * @param int $advanceGenerationDays
	 *
	 * @return BookingCode|null
	 * @throws BookingCodeException
	 */
	public static function getCode( Timeframe $timeframe, int $itemId, int $locationId, string $date, int $advanceGenerationDays = self::ADVANCE_GENERATION_DAYS ) : ?BookingCode {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . self::$tablename;

			$sql = $wpdb->prepare(
				"SELECT * FROM $table_name
                WHERE 
                    timeframe = %s AND 
                    item = %s AND 
                    location = %s AND 
                    date = %s
                ORDER BY item ASC ,date ASC",
				$timeframe->ID,
				$itemId,
				$locationId,
				$date
			);
			$bookingCodes = $wpdb->get_results($sql);

			if ( empty( $bookingCodes ) ) {
				//when we have a timeframe without end-date we generate as many codes as we need
				if (! $timeframe->getRawEndDate() && $timeframe->bookingCodesApplicable() ) {
					$begin = $timeframe->getUTCStartDateDateTime();
					$endDate = new \DateTime($date);
					$endDate->modify('+' . $advanceGenerationDays . ' days');
					$interval = DateInterval::createFromDateString( '1 day' );
					$period = new DatePeriod( $begin, $interval, $endDate );
					static::generatePeriod($timeframe,$period);
					$bookingCodes = $wpdb->get_results($sql);
				}
			}

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
			$end->setTimestamp( $end->getTimestamp() + 1 );
		}
		else {
			$end = new \DateTime();
			$end->modify( '+' . $advanceGenerationDays . 'days');
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
			$location = $timeframe->getLocation();
		} catch ( \Exception $e ) {
			throw new BookingCodeException( __( "No booking codes could be created because the location of the timeframe could not be found.", 'commonsbooking' )  );
		}
		try {
			$item = $timeframe->getItem();
		} catch ( \Exception $e ) {
			throw new BookingCodeException( __( "No booking codes could be created because the item of the timeframe could not be found.", 'commonsbooking' )  );
		}

		self::deleteOldCodes( $timeframe->ID, $location->ID, $item->ID );

		$bookingCodesRandomizer = intval( $timeframe->ID );
		$bookingCodesRandomizer += $item->ID;
		$bookingCodesRandomizer += $location->ID;

		foreach ( $period as $dt ) {
			$day = new Day( $dt->format( 'Y-m-d' ) );
			if ( $day->isInTimeframe( $timeframe ) ) {
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
	 * Removes all codes for the post, that don't have the current location-id or item-id.
	 *
	 * @param int $postId
	 * @param int $locationId
	 * @param int $itemId
	 */
	public static function deleteOldCodes( int $postId, int $locationId, int $itemId ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$tablename;

		$query = $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE timeframe = %d AND (location != %d OR item != %d)',
			$postId,
			$locationId,
			$itemId
		);
		$wpdb->query( $query );
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
