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
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;

use DateTimeImmutable;
use DateInterval;

/**
 * The class used to generate ics files for the user.
 * These ics files can either be per booking, contain all bookings for the user or even all bookings that a CB-Manager or an admin has access to.
 *
 * The functionality of this heavily relies on the iCal library by eluceo.
 */
class iCalendar {

	/**
	 * @var Calendar
	 */
	private Calendar $calendar;

	public const URL_SLUG       = COMMONSBOOKING_PLUGIN_SLUG . '_ical_download';
	public const QUERY_USER     = COMMONSBOOKING_PLUGIN_SLUG . '_user';
	public const QUERY_USERHASH = COMMONSBOOKING_PLUGIN_SLUG . '_userhash';

	public function __construct() {
		// Create new calendar instance (eluceo/iCal)
		$this->calendar = new Calendar();
	}

	/**
	 * Registers url to download ics file.
	 * Only enabled, when the setting is set in the advanced options
	 *
	 * @return void
	 */
	public static function initRewrite() {
		if ( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'feed_enabled' ) == 'on' ) {
			add_action(
				'wp_loaded',
				function () {
					add_rewrite_rule( self::URL_SLUG, 'index.php?' . self::URL_SLUG . '=1', 'top' );
				}
			);

			add_filter(
				'query_vars',
				function ( $query_vars ) {
					$query_vars[] = self::URL_SLUG;
					return $query_vars;
				}
			);

			add_action(
				'parse_request',
				function ( &$wp ) {

					if ( ! array_key_exists( self::URL_SLUG, $wp->query_vars ) ) {
						return;
					}
					self::getICSDownload();
				}
			);
		}
	}

	/**
	 * Returns a valid link to retrieve iCalendar data for the current user,
	 * for this it takes the user id and hashes it using the wp_hash algorithm.
	 * This should be relatively secure, since the hash is salted.
	 * Returns false when user is not logged in
	 *
	 * @return string | false - false when user is not logged in
	 */
	public static function getCurrentUserCalendarLink() {
		if ( ! is_user_logged_in() ) {
			return false;}

		$user_id         = wp_get_current_user()->ID;
		$user_hash       = wp_hash( (string) $user_id );
		$script_location = get_site_url() . '/';

		return add_query_arg(
			array(
				self::QUERY_USER => $user_id,
				self::QUERY_USERHASH => $user_hash,
				self::URL_SLUG => true,
			),
			$script_location
		);
	}

	/**
	 * Get the ics file for an existing booking. Will be called, when the "Add to Calendar" button on the booking page is pressed
	 *
	 * @param $bookingID
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function downloadICS( $bookingID ): void {
		$postID           = $bookingID;
		$booking          = \CommonsBooking\Repository\Booking::getPostById( $postID );
		$template_objects = [
			'booking'  => $booking,
			'item'     => $booking->getItem(),
			'location' => $booking->getLocation(),
			'user'     => $booking->getUserData(),
		];

		$eventTitle       = Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title' );
		$eventTitle       = commonsbooking_sanitizeHTML( commonsbooking_parse_template( $eventTitle, $template_objects ) );
		$eventDescription = Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-description' );
		$eventDescription = commonsbooking_sanitizeHTML( strip_tags( commonsbooking_parse_template( $eventDescription, $template_objects ) ) );
		$calendar         = $booking->getiCal( $eventTitle, $eventDescription );
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="booking.ics"' );
		echo $calendar;
	}

	/**
	 * Adds Model\Booking to Calendar.
	 * This will take all the information like title, description, location, start and end date and add it to the calendar as an event.
	 *
	 * The title and description for the iCalendar, that can be set by the user in the options, will be used as the title and description of the event.
	 *
	 * @param Booking $booking - The booking to add to the calendar
	 * @param String  $eventTitle - The title of the event in the ics calendar
	 * @param String  $eventDescription - The description of the event in the ics calendar
	 *
	 * @throws \Exception
	 */
	public function addBookingEvent(
		Booking $booking,
		string $eventTitle,
		string $eventDescription
	) {
			$eventDescription = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $eventDescription ); // remove empty lines from the description, they are not part of the standard

			$bookingLocation           = $booking->getLocation();
			$bookingLocation_latitude  = $bookingLocation->getMeta( 'geo_latitude' );
			$bookingLocation_longitude = $bookingLocation->getMeta( 'geo_longitude' );

			// create immutable DateTime objects from Mutable (recommended by iCal library developer)
			$booking_startDateDateTime = DateTimeImmutable::createFromMutable( $booking->getUTCStartDateDateTime() );
			$booking_endDateDateTime   = DateTimeImmutable::createFromMutable( $booking->getUTCEndDateDateTime() );

			// Create timezone entity
			$php_date_time_zone = wp_timezone();
			// will only get timezone object if current timezone has transitions that can be fetched
		if ( $php_date_time_zone->getTransitions() ) {
			$timezone = \Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone(
				$php_date_time_zone,
				$booking_startDateDateTime,
				$booking_endDateDateTime
			);
			if ( empty( $this->calendar->getTimeZones() ) ) {
				$this->calendar->addTimeZone( $timezone );
			}
		}

			// Create event occurrence
		if ( $booking->isFullDay() ) {
			if ( $booking_startDateDateTime->format( 'Y-m-d' ) == $booking_endDateDateTime->format( 'Y-m-d' ) ) { // is single day event
				$occurrence = new SingleDay(
					new Date( $booking_startDateDateTime )
				);
			} else { // is multi day event
				$occurrence = new MultiDay(
					new Date( $booking_startDateDateTime ),
					new Date( $booking_endDateDateTime )
				);
			}
		} else { // is timespan

			// add one minute to EndDate (this minute was removed to prevent overlapping but would confuse users)
			$booking_endDateDateTime = $booking_endDateDateTime->add( new DateInterval( 'PT1M' ) );

			$occurrence = new TimeSpan(
				new \Eluceo\iCal\Domain\ValueObject\DateTime( $booking_startDateDateTime, true ),
				new \Eluceo\iCal\Domain\ValueObject\DateTime( $booking_endDateDateTime, true )
			);
		}

			$eventStatus = EventStatus::CONFIRMED();
		if ( $booking->isCancelled() ) {
			$eventStatus = EventStatus::CANCELLED();
		}

			// Create unique identifier

			$uniqueIdentifier = new UniqueIdentifier( $booking->post_name );
			// Create Event domain entity.
			$event = new Event( $uniqueIdentifier );
			$event
				->setSummary( $eventTitle )
				->setDescription( $eventDescription )
				->setOccurrence( $occurrence )
				->setStatus( $eventStatus )
				->touch( new Timestamp() );

			// Add location to domain entity

			$location_address = $bookingLocation->formattedAddressOneLine();
		if ( ! empty( $location_address ) ) {
			$event->setLocation(
				(
				new Location( $location_address, $bookingLocation->post_title ) )
					->withGeographicPosition(
						new GeographicPosition(
							floatval( $bookingLocation_latitude ),
							floatval( $bookingLocation_longitude )
						)
					)
			);
		}

			$this->calendar->addEvent( $event );
	}

	/**
	 * Will get the string representation of the current calendar.
	 * This can be used to display to save the calendar to a file or serve it via URL.
	 *
	 * @return String - The string representation of the calendar
	 */
	public function getCalendarData(): string {
		// Transform domain entity into an iCalendar component
		$componentFactory  = new CalendarFactory();
		$calendarComponent = $componentFactory->createCalendar( $this->calendar );

		return $calendarComponent->__toString();
	}

	/**
	 * Adds a generic event to Calendar
	 *
	 * @param DateTimeImmutable[]|DateTimeImmutable $eventDate
	 * @param string                                $eventTitle
	 * @param string                                $eventDescription
	 * @param bool                                  $isTimeSpan
	 *
	 * @return Event|false
	 */
	public function addEvent(
		$eventDate,
		string $eventTitle,
		string $eventDescription,
		bool $isTimeSpan = false
	) {

		if ( is_array( $eventDate ) ) {
			if ( count( $eventDate ) < 2 || $eventDate[0] > $eventDate[1] ) {
				return false; // FIXME Why fail siltenly?
			}
			if ( $isTimeSpan ) {
				$occurence = new TimeSpan( new DateTime( $eventDate[0], false ), new DateTime( $eventDate[1], false ) );
			} else {
				$occurence = new MultiDay( new Date( $eventDate[0] ), new Date( $eventDate[1] ) );
			}
		} else {
			$occurence = new SingleDay( new Date( $eventDate ) );
		}

		// Create Event domain entity.
		$event = new Event();
		$event
			->setSummary( $eventTitle )
			->setDescription( $eventDescription )
			->setOccurrence( $occurence );

		$this->calendar->addEvent( $event );

		return $event;
	}

	/**
	 * Returns ics download file for current user.
	 * This will check if the user is logged in and if the user id and hash are correct.
	 * If they are correct, all of the user's bookings will be added to the calendar and the calendar will be returned as a file.
	 *
	 * This can be used to integrate the calendar dynamically via URL into other calendar applications.
	 *
	 * This function is called when the user visits the site with the URL_SLUG parameter.
	 * ie. https://example.org/?commonsbooking_user=13&commonsbooking_userhash=51679946bee67c128a82a2219b7d00a2&commonsbooking_ical_download=1
	 *
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
				die( 'Error in retrieving booking list.' );
			}
		} elseif ( ! $user_id ) {
				die( 'user id missing' );
		} elseif ( ! $user_hash ) {
			die( 'user hash missing' );
		} else {
			die( 'user_id and user_hash mismatch. Authentication failed.' );
		}
	}
}
