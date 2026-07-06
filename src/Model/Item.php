<?php


namespace CommonsBooking\Model;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Repository\Timeframe;
use Exception;

/**
 * This is the logical wrapper for the item custom post type.
 *
 * You can get the items from the database using the @see \CommonsBooking\Repository\Item class.
 *
 * Additionally, all the public functions in this class can be called using Template Tags.
 */
class Item extends BookablePost {
	/**
	 * Returns all bookable timeframes for a specific location.
	 *
	 * @param $locationId
	 *
	 * @param bool $asModel
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBookableTimeframesByLocation( $locationId, bool $asModel = false ): array {
		return Timeframe::getBookableForCurrentUser(
			[ $locationId ],
			[ $this->ID ],
			null,
			$asModel,
			Helper::getLastFullHourTimestamp()
		);
	}

	/**
	 * Will get all the admins for this item.
	 * The admins can be configured in the backend.
	 * This will not get the admins of the location that this item belongs to. If you want that, use the function from the Model/Timeframe class.
	 *
	 * TODO: This currently includes the author of the item as an admin.
	 *       This does not make sense in all contexts and should be fixed.
	 *       Duplicated implementation in Model/Location.php
	 *
	 * @return array|mixed|string[]
	 */
	public function getAdmins() {
		$itemId       = $this->ID;
		$itemAdminIds = get_post_meta( $itemId, '_' . \CommonsBooking\Wordpress\CustomPostType\Item::$postType . '_admins', true );
		if ( is_string( $itemAdminIds ) ) {
			if ( strlen( $itemAdminIds ) > 0 ) {
				$itemAdminIds = [ $itemAdminIds ];
			} else {
				$itemAdminIds = [];
			}
		}
		$itemAdminIds[] = get_post_field( 'post_author', $this->ID );

		return array_values(
			array_unique(
				array_map( 'intval', $itemAdminIds )
			)
		);
	}

	/**
	 * Returns all applicable restrictions for this item.
	 *
	 * This function is not used anywhere yet.
	 *
	 * @deprecated since 2.11, removal in 2.12.
	 * @return array
	 * @throws Exception
	 */
	public function getRestrictions(): array {
		return \CommonsBooking\Repository\Restriction::get(
			[],
			[ $this->ID ],
			null,
			true
		);
	}

	/**
	 * Will get a rotating ID by which the vehicle can be identified.
	 * The ID rotates after every trip and can be used to publish a vehicle ID to the GBFS API.
	 *
	 * The GBFS spec demands, that a vehicle ID must be rotated after each trip so that the users cannot be profiled.
	 * This needs to be deterministic and reversible.
	 *
	 * The ID should be reversible, so that we can get a deep link to the item that you found in the feed.
	 *
	 * Inspired by: https://tier.engineering/How-we-anonymize-user-trips-on-public-APIs
	 * Difference: We don't care about anonymity as much, because we don't offer A -> B trips.
	 *
	 * This function uses wp_hash to get a hashed identifier, this uses md5 hashing internally.
	 *
	 * @return string - a rotating ID for a vehicle
	 */
	public function getCloakedId(): string {
		$closestBooking = $this->getClosestBooking();
		$secondHash     = $closestBooking->post_name ?? $this->post_name; // If there is a booking, hash that. Otherwise, get the post name of the item as second hash item

		return wp_hash( $this->ID . $secondHash );
	}

	/**
	 * Gets the permalink for an item as a cloaked URL to be published in the API.
	 *
	 * @return string
	 */
	public function getCloakedURL(): string {
		return add_query_arg(
			array(
				\CommonsBooking\Repository\Item::QUERY_VEHICLE_ID => $this->getCloakedId(),
				\CommonsBooking\Repository\Item::URL_SLUG => true,
			),
			get_site_url() . '/'
		);
	}

	/**
	 * Gets the closest Booking for a given Item.
	 *
	 * @return ?Booking the booking that is past and closest to the current time, null if no booking is present
	 */
	public function getClosestBooking(): ?Booking {

		$location = $this->getLocation();
		if ( $location === null ) {
			return null;
		}

		$allBookings = \CommonsBooking\Repository\Booking::get(
			[ $location->ID ],
			[ $this->ID ],
			null,
			true,
			null,
			[ 'confirmed' ]
		);

		$allBookings = array_filter( $allBookings, fn( $b ) => $b->isPast() );

		if ( empty( $allBookings ) ) {
			return null;
		}

		usort(
			$allBookings,
			function ( $a, $b ) {
				$aStartDate = $a->getStartDate();
				$bStartDate = $b->getStartDate();

				if ( $aStartDate == $bStartDate ) {
					$aStartTimeDT = $a->getStartTimeDateTime();
					$bStartTimeDT = $b->getStartTimeDateTime();

					return $aStartTimeDT <=> $bStartTimeDT;
				}

				return $aStartDate <=> $bStartDate;
			}
		);

		return array_pop( $allBookings );
	}

	/**
	 * Will get the location that the item is currently stationed at and bookable.
	 * Will take into account the current time, the item can have timeframes
	 * at multiple locations but can only be at one location at a time.
	 *
	 * @return ?Location will return null when no Location was found
	 */
	public function getLocation(): ?Location {
		return $this->getClosestBookableTimeframe()?->getLocation();// why I used a deprecated method here: https://github.com/wielebenwir/commonsbooking/issues/507#issuecomment-4235848408
	}

	/**
	 * Will get the timeframe that is currently applicable for this item.
	 * When there are multiple timeframes, it wil select the closest.
	 *
	 * @return \CommonsBooking\Model\Timeframe|null
	 */
	public function getClosestBookableTimeframe(): ?\CommonsBooking\Model\Timeframe {
		$locations = \CommonsBooking\Repository\Location::getByItem( $this->ID, true );

		if ( empty( $locations ) ) {
			return null;
		}

		$timeframes = [];
		foreach ( $locations as $location ) {
			$timeframes = array_merge(
				$timeframes,
				$location->getBookableTimeframesByItem( $this->ID, true )
			);
		}
		return \CommonsBooking\View\Calendar::getClosestBookableTimeFrameForToday( $timeframes );
	}

	/**
	 * Determines whether this item is currently free at a given location.
	 * Free is the opposite of rented. Checks against live availability slots, so it excludes items with Holiday
	 * Timeframes or overbooking-only availability, although they are technically not rented during these periods.
	 *
	 * This method will include items that may only be booked in advance (\CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS),
	 * because they are technically available at this location. Just not for pickup right now.
	 *
	 * @param int  $locationId
	 * @param bool $ignoreStartDayOffset
	 * @param bool $ignoreRestrictions
	 * @return bool true if the item is free right now, false otherwise
	 * @throws Exception
	 */
	public function isCurrentlyFreeAtLocation( int $locationId, bool $ignoreStartDayOffset = false, bool $ignoreRestrictions = false ): bool {
		$nowDT        = Wordpress::getUTCDateTimeByTimestamp( current_time( 'timestamp' ) );
		$itemCalendar = new Calendar(
			new Day( date( 'Y-m-d', strtotime( '-1 day' ) ) ),
			new Day( date( 'Y-m-d', strtotime( '+1 day' ) ) ),
			[ $locationId ],
			[ $this->ID ]
		);
		$itemCalendar->setIgnoreStartDayOffset( $ignoreStartDayOffset );
		$itemCalendar->setIgnoreRestrictions( $ignoreRestrictions );

		foreach ( $itemCalendar->getAvailabilitySlots() as $availabilitySlot ) {
			$startDT = new \DateTime( $availabilitySlot->start );
			$endDT   = new \DateTime( $availabilitySlot->end );
			if ( $nowDT >= $startDT && $nowDT <= $endDT ) {
				return true;
			}
		}

		return false;
	}
}
