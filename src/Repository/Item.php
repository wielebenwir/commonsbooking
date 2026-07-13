<?php

namespace CommonsBooking\Repository;

use Exception;

class Item extends BookablePost {
	public const URL_SLUG         = COMMONSBOOKING_PLUGIN_SLUG . '_get_vehicle';
	public const QUERY_VEHICLE_ID = COMMONSBOOKING_PLUGIN_SLUG . '_vehicle_cloaked_id';

	/**
	 * Returns array with items at location based on bookable timeframes.
	 *
	 * @param $locationId
	 *
	 * @param bool $bookable
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getByLocation( $locationId, bool $bookable = false ): array {
		return self::getByRelatedPost( $locationId, 'location', 'item', $bookable );
	}

	/**
	 * Allows resolving cloaked vehicle IDs published by the GBFS API back to the corresponding items
	 *
	 * @return void
	 */
	public static function initRewrite() {
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

				if (
					! array_key_exists( self::URL_SLUG, $wp->query_vars ) ||
					! isset( $_GET[ self::QUERY_VEHICLE_ID ] ) ||
					empty( $_GET[ self::QUERY_VEHICLE_ID ] )
				) {
					return;
				}
				$cloakedId = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VEHICLE_ID ] ) );
				$item      = Item::getByCloakedId( $cloakedId );
				if ( $item === null ) {
					die( 'invalid vehicle id' );
				}
				$url = get_permalink( $item->ID );
				wp_redirect( $url );
				exit;
			}
		);
	}

	/**
	 * @return string
	 */
	protected static function getPostType(): string {
		return \CommonsBooking\Wordpress\CustomPostType\Item::getPostType();
	}

	/**
	 * Gets an individual item from a cloaked ID. The GBFS API uses this to provide
	 * deep-links with rotating vehicle IDs.
	 *
	 * @param string $cloakedId
	 * @return \CommonsBooking\Model\Item|null
	 */
	public static function getByCloakedId( string $cloakedId ): ?\CommonsBooking\Model\Item {
		foreach ( self::get() as $item ) {
			if ( $item->getCloakedId() == $cloakedId ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * This is the model class that belongs to the post type.
	 * With the model class, you are able to perform additional functions on the post type.
	 *
	 * @return string
	 */
	protected static function getModelClass(): string {
		return \CommonsBooking\Model\Item::class;
	}

	/**
	 * @return string
	 */
	protected static function getTaxonomyName() {
		return \CommonsBooking\Wordpress\CustomPostType\Item::getTaxonomyName();
	}
}
