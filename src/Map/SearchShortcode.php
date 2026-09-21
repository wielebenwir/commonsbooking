<?php

namespace CommonsBooking\Map;

use CommonsBooking\Model\Map;
use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\Location;

/**
 * Short code for a multi-widget with map, search and table capabilities.
 */
class SearchShortcode extends BaseShortcode {
	protected $processed_map_ids = [];

	/**
	 * AJAX handler for the map-independent item source.
	 */
	public static function get_items(): void {
		check_ajax_referer( 'cb_search_items', 'nonce' );

		$results = [];

		foreach ( Item::get() as $item ) {
			$item_terms = wp_get_post_terms(
				$item->ID,
				\CommonsBooking\Wordpress\CustomPostType\Item::getTaxonomyName(),
				[ 'fields' => 'ids' ]
			);
			$item_terms = is_wp_error( $item_terms ) ? [] : array_map( 'intval', $item_terms );

			$thumbnail_id = get_post_thumbnail_id( $item->ID );
			$images       = [
				'thumbnail' => wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' ),
				'medium'    => wp_get_attachment_image_src( $thumbnail_id, 'medium' ),
				'large'     => wp_get_attachment_image_src( $thumbnail_id, 'large' ),
				'full'      => wp_get_attachment_image_src( $thumbnail_id, 'full' ),
			];

			foreach ( Location::getByItem( $item->ID, true ) as $location ) {
				$location_id = $location->ID;
				$timeframes  = [];

				foreach ( $item->getBookableTimeframesByLocation( $location_id, true ) as $timeframe ) {
					$timeframes[] = [
						'date_start' => date( 'Y-m-d', $timeframe->getStartDate() ),
						'date_end'   => $timeframe->getEndDate() ?: '2999-01-01',
					];
				}

				if ( ! isset( $results[ $location_id ] ) ) {
					$closed_days = maybe_unserialize( $location->getMeta( 'commons-booking_location_closeddays' ) );
					$results[ $location_id ] = [
						'lat'           => (float) $location->getMeta( 'geo_latitude' ),
						'lon'           => (float) $location->getMeta( 'geo_longitude' ),
						'location_name' => $location->post_title,
						'location_link' => get_permalink( $location_id ),
						'closed_days'   => is_array( $closed_days ) ? $closed_days : [],
						'address'       => [
							'street' => $location->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_street' ),
							'city'   => $location->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_city' ),
							'zip'    => $location->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'location_postcode' ),
						],
						'items'         => [],
					];
				}

				$results[ $location_id ]['items'][] = [
					'id'         => $item->ID,
					'name'       => $item->post_title,
					'short_desc' => $item->excerpt(),
					'status'     => $item->post_status,
					'terms'      => $item_terms,
					'link'       => add_query_arg( 'cb-location', $location_id, get_permalink( $item->ID ) ),
					'thumbnail'  => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) ?: null,
					'images'     => $images,
					'timeframes' => $timeframes,
				];
			}
		}

		$locations = Map::cleanup_location_data( array_values( $results ), '<br>' );

		wp_send_json( $locations );
	}

	protected function parse_attributes( $atts ) {
		$is_item_source  = isset( $atts['source'] ) && 'items' === $atts['source'];
		$default_layouts = $is_item_source ? 'Filter,List' : 'Filter,MapWithAutoSidebar';

		return shortcode_atts(
			[
				'id' => null,
				'layouts' => $default_layouts,
				'source' => 'map',
			],
			$atts
		);
	}

	protected function allows_missing_map( array $attrs ): bool {
		return 'items' === $attrs['source'];
	}

	protected function inject_script( $cb_map_id ) {
		wp_enqueue_style( 'cb-commons-search' );
		wp_enqueue_script( 'cb-commons-search' );
	}

	protected function create_container( $cb_map_id, $attrs, $options, $content ) {
		$is_item_source = 'items' === $attrs['source'];

		// Ensure that the api and config object are only created once per page and per map
		if ( ! in_array( $cb_map_id, $this->processed_map_ids ) ) {
			$settings                  = MapData::get_settings( $cb_map_id );
			$admin_ajax_url            = wp_json_encode( pop_key( $settings, 'data_url' ) );
			$nonce                     = wp_json_encode( $is_item_source ? wp_create_nonce( 'cb_search_items' ) : pop_key( $settings, 'nonce' ) );
			$action                    = wp_json_encode( $is_item_source ? 'cb_search_items' : 'cb_map_locations' );
			$data_loader               = trim(
				'
				const config = CommonsSearch.parseLegacyConfig(' . wp_json_encode( $settings ) . ");
				const api = CommonsSearch.createAdminAjaxAPI({
	                url: $admin_ajax_url,
	                nonce: $nonce,
	                action: $action,
	                mapId: $cb_map_id,
	            }, config);
	            if (!window.__CB_SEARCH_DATA) window.__CB_SEARCH_DATA = {};
	            window.__CB_SEARCH_DATA[$cb_map_id] = { config, api };
			"
			);
			$this->processed_map_ids[] = $cb_map_id;
		} else {
			$data_loader = "const { config, api } = window.__CB_SEARCH_DATA[$cb_map_id];";
		}

		$content           = trim( strip_tags( $content ) );
		$content_is_config = $content && is_object( json_decode( $content ) ) && json_last_error() == JSON_ERROR_NONE;
		if ( $content_is_config ) {
			$user_config = "const userConfig = $content;";
		} else {
			$user_config = 'const userConfig = {};';
		}

		$layout_config = wp_json_encode(
			array(
				'types' => array_map( 'trim', explode( ',', $attrs['layouts'] ) ),
				'options' => $options,
			)
		);

		$init_script = "(function (el) {
			document.addEventListener('DOMContentLoaded', function() {
	            $data_loader
	            $user_config
	            CommonsSearch.init(el, api, CommonsSearch.mergeConfigs(config, { layout: $layout_config, ...userConfig }));
			});
        })(document.currentScript.parentElement)";

		return "<div><script>{$init_script}</script></div>";
	}
}

function pop_key( &$array, $key ) {
	$value = $array[ $key ];
	unset( $array[ $key ] );
	return $value;
}
