<?php

namespace CommonsBooking\Map;

/**
 * Map shortcode base implementation.
 * Derive from this class to create custom map shortcode.
 * Examples of how to implement the abstract methods called in `execute` are {@see MapShortcode} and {@see SearchShortcode}.
 */
abstract class BaseShortcode {

	final public function __construct() { }

	/**
	 * The shortcode handler - load all the needed assets and render the map container
	 *
	 * @param array  $atts attributes for parametrization.
	 * @param string $content content to display, if shortcode implementation allows to.
	 **/
	public static function execute( array $atts, string $content = '' ): string {
		$instance = new static();
		$attrs    = $instance->parse_attributes( $atts );
		$options  = array_filter( $atts, 'is_int', ARRAY_FILTER_USE_KEY );

		if ( ! (int) $attrs['id'] ) {
			if ( ! $instance->allows_missing_map( $attrs ) ) {
				return '<div>' . esc_html__( 'no valid map id provided', 'commonsbooking' ) . '</div>';
			}

			$cb_map_id = 0;
			$instance->inject_script( $cb_map_id );
			return $instance->create_container( $cb_map_id, $attrs, $options, $content );
		}

		$post = get_post( $attrs['id'] );

		if ( ! ( $post && $post->post_type == 'cb_map' ) ) {
			return '<div>' . esc_html__( 'no valid map id provided', 'commonsbooking' ) . '</div>';
		}

		if ( $post->post_status != 'publish' ) {
			return '<div>' . esc_html__( 'map is not published', 'commonsbooking' ) . '</div>';
		}

		$cb_map_id = $post->ID;
		$instance->inject_script( $cb_map_id );
		return $instance->create_container( $cb_map_id, $attrs, $options, $content );
	}

	/**
	 * Whether the shortcode can operate with default settings and no map post.
	 */
	protected function allows_missing_map( array $attrs ): bool {
		return false;
	}

	abstract protected function parse_attributes( $atts );
	abstract protected function inject_script( $cb_map_id );
	abstract protected function create_container( $cb_map_id, $attrs, $options, $content );
}
