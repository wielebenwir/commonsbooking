<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use Geocoder\Exception\Exception;

class Location extends BookablePost {

	/**
	 * getBookableTimeframesByItem
	 *
	 * returns bookable timeframes for a given itemID
	 *
	 * @param mixed $itemId
	 * @param bool $asModel
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getBookableTimeframesByItem( $itemId, bool $asModel = false): array {
		// get bookable timeframes that has min timestamp = now
		return Timeframe::getBookable(
			[ $this->ID ],
			[ $itemId ],
			null,
			$asModel,
			Helper::getLastFullHourTimestamp(),
		);
	}

	/**
	 *
	 *
	 * Returns the location address including location name in multiple lanes with <br> line breaks
	 *
	 * @TODO: turn this into a user-configurable template.
	 * E.g. a textarea "location format" in the backend that gets run through CB::get():
	 * {{location_street}}<br>{{location_postcode}} {{location_city}}
	 *
	 *
	 * @return string
	 */
	public function formattedAddress() {
		$html_after    = '<br>';
		$html_output[] = CB::get( 'location', 'post_title', $this->post->ID ) . $html_after;
		$html_output[] = CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_street',
				$this->post->ID ) . $html_after;
		$html_output[] = CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', $this->post->ID ) . ' ';
		$html_output[] = CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_city',
				$this->post->ID ) . $html_after;

		return implode( ' ', $html_output );
	}

	/**
	 * formattedAddressOneLine
	 *
	 * Returns the formatted Location address in one line, separated by comma
	 *
	 * @TODO: Do not return tags (,) if values are empty. This applies to  formattedAddress(), too
	 *
	 * @return string html
	 */
	public function formattedAddressOneLine() {
		return sprintf(
			'%s, %s %s',
			CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_street', $this->post->ID ),
			CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', $this->post->ID ),
			CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_city', $this->post->ID )
		);
	}

	/**
	 * formattedContactInfo
	 *
	 * Returns formatted location contact info with info text
	 *
	 * @TODO: do not add any text in here, any text should be in the backend email text field!
	 *
	 * @return string
	 */
	public function formattedContactInfo() {
		$contact = array();
		if ( ! empty( CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_contact' ) ) ) {
			$contact[] = "<br>"; // needed for email template
			$contact[] = esc_html__( 'Please contact the contact persons at the location directly if you have any questions regarding collection or return:',
				'commonsbooking' );
			$contact[] = nl2br( CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_contact', $this->post->ID ) );
		}

		return implode( '<br>', $contact );

	}

	/**
	 * formattedContactInfoOneLine
	 *
	 * Returns formatted location contact info
	 *
	 * @return string
	 */
	public function formattedContactInfoOneLine() {
		return commonsbooking_sanitizeHTML( CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_contact', $this->post->ID ) ) . '<br>';
	}

	/**
	 * Return Location pickup instructions
	 *
	 * @return string html
	 * @throws \Exception
	 */
	public function formattedPickupInstructions(): string {
		$html_br = '<br>';

		return $html_br . $html_br . CB::get( 'location',
				COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions', $this->post->ID ) . $html_br;
	}

	/**
	 * Return Location pickup instructions
	 *
	 * @return string html
	 */
	public function formattedPickupInstructionsOneLine() {
		return CB::get( 'location', COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions', $this->post->ID );
	}

	/**
	 * @throws Exception
	 */
	public function updateGeoLocation() {
		$street        = $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_street' );
		$postCode      = $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_postcode' );
		$city          = $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_city' );
		$country       = $this->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_country' );
		$geo_latitude  = $this->getMeta( 'geo_latitude' );
		$geo_longitude = $this->getMeta( 'geo_longitude' );

		// if geo coordinates already exist do not update from geocoder
		if ( ! empty( $geo_latitude ) && ! empty( $geo_longitude ) ) {
			return;
		}

		$addressString = $street . ", " . $postCode . " " . $city . ", " . $country;
		$addressData   = GeoHelper::getAddressData( $addressString );

		if ( $addressData ) {
			$coordinates = $addressData->getCoordinates()->toArray();

			update_post_meta(
				$this->ID,
				'geo_latitude',
				$coordinates[1]
			);
			update_post_meta(
				$this->ID,
				'geo_longitude',
				$coordinates[0]
			);
		}
	}

	/**
	 * @return array|mixed|string[]
	 */
	public function getAdmins() {
		// Get assigned location
		$locationId       = $this->ID;
		$locationAdminIds = get_post_meta( $locationId, '_' . \CommonsBooking\Wordpress\CustomPostType\Location::$postType . '_admins', true );
		if ( is_string( $locationAdminIds ) ) {
			if ( strlen( $locationAdminIds ) > 0 ) {
				$locationAdminIds = [ $locationAdminIds ];
			} else {
				$locationAdminIds = [];
			}
		}
		$locationAdminIds[] = get_post_field( 'post_author', $locationId );

		return $locationAdminIds;
	}

	/**
	 * @return Restriction[]
	 * @throws \Exception
	 */
	public function getRestrictions(): array {
		return \CommonsBooking\Repository\Restriction::get(
			[ $this->ID ],
			[],
			null,
			true
		);
	}

	/**
	 * Returns true if the map shall be shown.
	 * @return mixed
	 */
	public function hasMap() {
		return $this->getMeta( 'loc_showmap' ) === "on";
	}
}
