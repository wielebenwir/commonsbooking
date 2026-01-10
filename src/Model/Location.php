<?php


namespace CommonsBooking\Model;

use CommonsBooking\CB\CB;
use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Geocoder\Exception\Exception;

/**
 * This is the logical wrapper for the location custom post type.
 *
 * You can get the locations from the database using the @see \CommonsBooking\Repository\Location class.
 *
 * Additionally, all the public functions in this class can be called using Template Tags.
 */
class Location extends BookablePost {
	/**
	 * getBookableTimeframesByItem
	 *
	 * returns bookable timeframes for a given itemID
	 *
	 * @param mixed $itemId
	 * @param bool  $asModel
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getBookableTimeframesByItem( $itemId, bool $asModel = false ): array {
		// get bookable timeframes that has min timestamp = now
		return Timeframe::getBookableForCurrentUser(
			[ $this->ID ],
			[ $itemId ],
			null,
			$asModel,
			Helper::getLastFullHourTimestamp()
		);
	}

	/**
	 *
	 * Returns the location address including location name in multiple lanes with <br> line breaks
	 *
	 * @TODO: - turn this into a user-configurable template.
	 * E.g. a textarea "location format" in the backend that gets run through CB::get():
	 * {{location_street}}<br>{{location_postcode}} {{location_city}}
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function formattedAddress() {
		$html_after      = '<br>';
		$html_output[]   = CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, 'post_title', $this->post ) . $html_after;
		$location_street = CB::get(
			\CommonsBooking\Wordpress\CustomPostType\Location::$postType,
			COMMONSBOOKING_METABOX_PREFIX . 'location_street',
			$this->post
		);
		if ( ! empty( $location_street ) ) {
			$html_output[] = $location_street . $html_after;
		}
		$location_postcode = CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', $this->post );
		if ( ! empty( $location_postcode ) ) {
			$html_output[] = $location_postcode;
		}
		$location_city = CB::get(
			\CommonsBooking\Wordpress\CustomPostType\Location::$postType,
			COMMONSBOOKING_METABOX_PREFIX . 'location_city',
			$this->post
		);
		if ( ! empty( $location_city ) ) {
			$html_output[] = $location_city . $html_after;
		}

		return implode( ' ', $html_output );
	}

	/**
	 * Returns the formatted Location address in one line, separated by comma.
	 * This function is usually called using template tags in the e-mail templates.
	 *
	 * TODO: Fix the uncaught exception.
	 *
	 * @return string html
	 * @throws \Exception
	 */
	public function formattedAddressOneLine(): string {
		$location_street = CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_street', $this->post );

		$location_postcode = CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', $this->post );

		$location_city = CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_city', $this->post );

		if ( empty( $location_street ) && empty( $location_postcode ) && empty( $location_city ) ) {
			return '';
		} elseif ( empty( $location_street ) || empty( $location_postcode ) ) {
			return sprintf(
				'%s %s %s',
				$location_street,
				$location_postcode,
				$location_city
			);
		}
		return sprintf(
			'%s, %s %s',
			$location_street,
			$location_postcode,
			$location_city
		);
	}

	/**
	 * formattedContactInfo
	 *
	 * Returns formatted location contact info with info text.
	 * This function is usually called using template tags in the e-mail templates.
	 *
	 * @TODO: do not add any text in here, any text should be in the backend email text field!
	 * @TODO: This function may throw an uncaught exception.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function formattedContactInfo() {
		$contact = array();
		if ( ! empty( CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_contact', $this->post ) ) ) {
			$contact[] = '<br>'; // needed for email template
			$contact[] = esc_html__(
				'Please contact the contact persons at the location directly if you have any questions regarding collection or return:',
				'commonsbooking'
			);
			$contact[] = nl2br( CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_contact', $this->post ) );
		}

		return implode( '<br>', $contact );
	}

	/**
	 * formattedContactInfoOneLine
	 *
	 * Returns formatted location contact info.
	 * This function is usually called using template tags in the e-mail templates.
	 * It is a shorter version of formattedContactInfo().
	 *
	 * TODO: This function may throw an uncaught exception.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function formattedContactInfoOneLine() {
		return commonsbooking_sanitizeHTML( CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_contact', $this->post ) ) . '<br>';
	}

	/**
	 * Return Location pickup instructions
	 * This function is usually called using template tags in the e-mail templates.
	 *
	 * @return string html
	 * @throws \Exception
	 */
	public function formattedPickupInstructions(): string {
		$html_br = '<br>';

		return $html_br . $html_br . CB::get(
			\CommonsBooking\Wordpress\CustomPostType\Location::$postType,
			COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions',
			$this->post
		) . $html_br;
	}

	/**
	 * Return Location pickup instructions.
	 * This function is usually called using template tags in the e-mail templates.
	 *
	 * TODO: This function may throw an uncaught exception.
	 *
	 * @return string html
	 * @throws \Exception
	 */
	public function formattedPickupInstructionsOneLine() {
		return CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions', $this->post );
	}

	/**
	 * Calls the geocoder to update the geo coordinates of the location.
	 * Caution: Do not call this function without a one-second delay between calls. Do not overload the geocoder.
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

		$addressString = $street . ', ' . $postCode . ' ' . $city . ', ' . $country;
		try {
			$addressData = GeoHelper::getAddressData( $addressString );
		} catch ( Exception $e ) {
			$addressData = null;
		}

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
	 * Will get all the admins of the location.
	 * The admins can be set in the backend.
	 * This will not get the admins of the items in the location. To get both, use the function from the Model/Timeframe class.
	 *
	 *  TODO: This currently includes the author of the location as an admin.
	 *        This does not make sense in all contexts and should be changed.
	 *
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

		return array_unique(
			array_map(
				'intval',
				array_values( $locationAdminIds )
			)
		);
	}

	/**
	 * Will get the currently applicable restrictions for the location.
	 *
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
	 * Returns true if the little map for the location should be shown.
	 * This can usually be seen in the frontend item detail page.
	 * This is set in the backend.
	 *
	 * @return mixed
	 */
	public function hasMap() {
		return $this->getMeta( 'loc_showmap' ) === 'on';
	}
}
