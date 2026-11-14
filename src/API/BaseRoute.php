<?php


namespace CommonsBooking\API;

use RuntimeException;

use CommonsBooking\Repository\ApiShares;
use CommonsBooking\Settings\Settings;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Basic functionality for the different api routes.
 *
 * If you extend from this class, you need to implement the following public methods:
 *  - get_items
 *  - get_item
 *  - get_public_item_schema
 *
 * This class relies on WP rest-api.php implementations. Routes that set $schemaUrl
 * serve that JSON schema file on their /schema endpoint; no validation of responses
 * happens at runtime (see tests/php/API for schema assertions).
 */
class BaseRoute extends WP_REST_Controller {

	const API_KEY_PARAM = 'apikey';

	// the location of the .schema.json files locally
	const SCHEMA_PATH = COMMONSBOOKING_PLUGIN_DIR . 'includes/commons-api-json-schema/';

	/**
	 * Optional path to a JSON schema file served by the /schema endpoint.
	 *
	 * @var string|null
	 */
	protected $schemaUrl;

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = COMMONSBOOKING_PLUGIN_SLUG . '/v' . $version;
		register_rest_route(
			$namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'args'                => array(),
					'permission_callback' => function () {
						return self::hasPermission();
					},
				),
			)
		);
		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'args'                => array(
						'context' => array(
							'default' => 'view',
						),
					),
					'permission_callback' => function () {
						return self::hasPermission();
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/schema',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_public_item_schema' ),
				'permission_callback' => function () {
					return self::hasPermission();
				},
			)
		);
	}

	/**
	 * Returns schema json for current route.
	 *
	 * @throws RuntimeException On missing schema files.
	 * @return string
	 */
	private function getSchemaJson(): string {
		$schema = file_get_contents( $this->schemaUrl ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( $schema === false ) {
			throw new RuntimeException( 'Could not retrieve schema json from ' . esc_url( $this->schemaUrl ) );
		}
		return $schema;
	}

	/**
	 * Adds schema-fields for output to current route (needed for /.../schema endpoint)
	 *
	 * @param array $schema Assoc array of schema json object.
	 * @return array
	 */
	public function add_additional_fields_schema( $schema ): array {
		if ( empty( $this->schemaUrl ) ) {
			return $schema;
		}
		$schemaArray = json_decode( $this->getSchemaJson(), true );

		return array_merge( $schema, $schemaArray );
	}

	/**
	 * Escapes JSON String for output.
	 *
	 * @param $string
	 *
	 * @return false|string
	 */
	public function escapeJsonString( $string ) {
		return substr( wp_json_encode( $string ), 1, - 1 ) ? : '';
	}

	/**
	 * Decodes HTML entities in a post title for API output.
	 *
	 * WordPress stores post titles with encoded entities (e.g. "A&amp;B").
	 * Since the API returns plain text values that consumers render as text,
	 * we decode them so they receive "A&B" instead of "A&amp;B".
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	protected function decodeApiTitle( string $title ): string {
		return html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Returns true if current request is allowed.
	 *
	 * @return bool
	 */
	public static function hasPermission(): bool {
		$isApiActive            = Settings::getOption( 'commonsbooking_options_api', 'api-activated' );
		$anonymousAccessAllowed = Settings::getOption( 'commonsbooking_options_api', 'apikey_not_required' );
		$apiKey                 = array_key_exists( self::API_KEY_PARAM, $_REQUEST ) ? sanitize_text_field( $_REQUEST[ self::API_KEY_PARAM ] ) : false;
		if ( ! $apiKey ) {
			// get apikey from headers (#2251)
			if ( function_exists( 'getallheaders' ) ) {
				$allHeaders = array_change_key_case( getallheaders(), CASE_LOWER );
				$apiKey     = array_key_exists( self::API_KEY_PARAM, $allHeaders ) ? sanitize_text_field( $allHeaders[ self::API_KEY_PARAM ] ) : false;
			} else {
				$apiKey = array_key_exists( 'HTTP_' . strtoupper( self::API_KEY_PARAM ), $_SERVER ) ? sanitize_text_field( $_SERVER[ 'HTTP_' . strtoupper( self::API_KEY_PARAM ) ] ) : false;
			}
		}
		$apiShare = ApiShares::getByKey( $apiKey );

		// Only if api is active we return something
		if ( $isApiActive ) {
			// if anonymous access is allowed, api shares are ignored
			if ( $anonymousAccessAllowed ) {
				return true;
			} else {
				// check if there is a valid api key submitted
				if ( $apiKey && $apiShare && $apiShare->isEnabled() ) {
					return true;
				}
			}
		}

		return false;
	}
}
