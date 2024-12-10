<?php

namespace CommonsBooking\Map;

/**
 * Short code for a multi-widget with map, search and table capabilities.
 */
class SearchShortcode extends BaseShortcode {
	protected $processed_map_ids = array();

	protected function parse_attributes( $atts ) {
		return shortcode_atts(
			array(
				'id' => null,
				'layouts' => 'Filter,MapWithAutoSidebar',
			),
			$atts
		);
	}

	protected function inject_script( $cb_map_id ) {
		wp_enqueue_style( 'cb-commons-search' );
		wp_enqueue_script( 'cb-commons-search' );
	}

	protected function create_container( $cb_map_id, $attrs, $options, $content ) {
		// Ensure that the api and config object are only created once per page and per map
		if ( ! in_array( $cb_map_id, $this->processed_map_ids ) ) {
			$settings                  = MapData::get_settings( $cb_map_id );
			$admin_ajax_url            = wp_json_encode( pop_key( $settings, 'data_url' ) );
			$nonce                     = wp_json_encode( pop_key( $settings, 'nonce' ) );
			$data_loader               = trim(
				'
				const config = CommonsSearch.parseLegacyConfig(' . wp_json_encode( $settings ) . ");
				const api = CommonsSearch.createAdminAjaxAPI({
	                url: $admin_ajax_url,
	                nonce: $nonce,
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
