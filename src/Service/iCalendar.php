<?php

namespace CommonsBooking\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\ValueObject\MultiDay;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;

use DateTimeImmutable;
use DateInterval;

/**
 *
 */
class iCalendar {

	private ?Calendar $calendar;

	public const URL_SLUG = COMMONSBOOKING_PLUGIN_SLUG . '_ical_download';
	public const QUERY_USER = COMMONSBOOKING_PLUGIN_SLUG . '_user';
	public const QUERY_USERHASH = COMMONSBOOKING_PLUGIN_SLUG . '_userhash';

	public function __construct() {
		$this->calendar = new Calendar();
	}

	/**
	 * Registers url to download ics file.
	 * Only enabled, when the Setting is set in the advanced options
	 * @return void
	 */
	public static function initRewrite() {
		if ( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'feed_enabled' ) == 'on' ) {
			add_action( 'wp_loaded', function () {
				add_rewrite_rule( self::URL_SLUG, 'index.php?' . self::URL_SLUG . '=1', 'top' );
			} );

			add_filter( 'query_vars', function ( $query_vars ) {
				$query_vars[] = self::URL_SLUG;

				return $query_vars;
			} );

			add_action( 'parse_request', function ( &$wp ) {

				if ( ! array_key_exists( self::URL_SLUG, $wp->query_vars ) ) {
					return;
				}
				self::getICSDownload();
			} );
		}
	}

	/**
	 * Returns a valid link to retrieve iCalendar data for the current user,
	 * for this it takes the user id and hashes it using the wp_hash algorithm.
	 * This should be relatively secure, since the hash is salted.
	 * Returns false when user is not logged in
	 *
	 * @return string | bool
	 */
	public static function getCurrentUserCalendarLink() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id         = wp_get_current_user()->ID;
		$user_hash       = wp_hash( $user_id );
		$script_location = get_site_url() . '/';

		return add_query_arg(
			array(
				self::QUERY_USER     => $user_id,
				self::QUERY_USERHASH => $user_hash,
				self::URL_SLUG       => true
			),
			$script_location
		);
	}

	/**
	 * Adds Model\Booking to Calendar
	 *
	 */
	public function addBookingEvent(
		Booking $booking,
		string $eventTitle,
		string $eventDescription
	) {
		$bookingLocation           = $booking->getLocation();
		$bookingLocation_latitude  = $bookingLocation->getMeta( 'geo_latitude' );
		$bookingLocation_longitude = $bookingLocation->getMeta( 'geo_longitude' );

		//create immutable DateTime objects from Mutable (recommended by iCal library developer)
		$booking_startDateDateTime = DateTimeImmutable::createFromMutable( $booking->getStartDateDateTime() );
		$booking_endDateDateTime   = DateTimeImmutable::createFromMutable( $booking->getEndDateDateTime() );

		// Create timezone entity
		$timezone = \Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone(
			wp_timezone(),
			$booking_startDateDateTime,
			$booking_endDateDateTime
		);

		//Create event occurence
		if ( $booking->isFullDay() ) {
			if ( $booking_startDateDateTime->format( 'Y-m-d' ) == $booking_endDateDateTime->format( 'Y-m-d' ) ) { //is single day event
				$occurence = new SingleDay(
					new Date( $booking_startDateDateTime )
				);
			} else { //is multi day event
				$occurence = new MultiDay(
					new Date( $booking_startDateDateTime ),
					new Date( $booking_endDateDateTime )
				);
			}
		} else { //is timespan

			//add one minute to EndDate (this minute was removed to prevent overlapping but would confuse users)
			$booking_endDateDateTime = $booking_endDateDateTime->add( new DateInterval( 'PT1M' ) );

			$occurence = new TimeSpan(
				new \Eluceo\iCal\Domain\ValueObject\DateTime( $booking_startDateDateTime, true ),
				new \Eluceo\iCal\Domain\ValueObject\DateTime( $booking_endDateDateTime, true )
			);
		}

		// Create Event domain entity.
		$event = new Event();
		$event
			->setSummary( $eventTitle )
			->setDescription( $eventDescription )
			->setLocation(
				(
				new Location( $bookingLocation->formattedAddressOneLine(), $bookingLocation->post_title ) )
					->withGeographicPosition(
						new GeographicPosition(
							floatval( $bookingLocation_latitude ),
							floatval( $bookingLocation_longitude )
						)
					)
			)
			->setOccurrence( $occurence );


		$this->calendar->addEvent( $event );
	}

	public function getCalendarData(): string {
		// Transform domain entity into an iCalendar component
		$componentFactory  = new CalendarFactory();
		$calendarComponent = $componentFactory->createCalendar( $this->calendar );

		return $calendarComponent->__toString();
	}

	/**
	 * Returns ics download file for current user.
	 * @return void
	 */
	public static function getICSDownload() {

		$user_id   = intval( $_GET[ self::QUERY_USER ] );
		$user_hash = strval( $_GET[ self::QUERY_USERHASH ] );

		if ( commonsbooking_isUIDHashComboCorrect( $user_id, $user_hash ) ) {

			$bookingiCal = \CommonsBooking\View\Booking::getBookingListiCal( $user_id );
			if ( $bookingiCal ) {
				header( 'Content-Type: text/calendar; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="ical.ics"' );
				echo $bookingiCal;
				die();
			} else {
				die( "Error in retrieving booking list." );
			}

		} else {
			if ( ! $user_id ) {
				die( "user id missing" );
			} elseif ( ! $user_hash ) {
				die( "user hash missing" );
			} else {
				die( "user_id and user_hash mismatch. Authentication failed." );
			}
		}
	}
}