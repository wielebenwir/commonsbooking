<?php


namespace CommonsBooking\API;

use Exception;
use RuntimeException;

use CommonsBooking\Repository\ApiShares;
use CommonsBooking\Settings\Settings;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Basic functionality for the different api routes.
 *
 * If you extend from this class, you need to implement the following public methods:
 *  - get_items
 *  - get_item
 *  - get_public_item_schema
 *
 * This class relies on WP rest-api.php implementations and another assumption is
 * that commonsbooking json schema files are in place.
 */
class BaseRoute extends WP_REST_Controller {

	const API_KEY_PARAM = 'apikey';

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
	 * Validates data against defined schema.
	 *
	 * @param $data
	 */
	public function validateData( $data ) {
		$validator = new Validator();

		try {
			$result = $validator->schemaValidation( $data, $this->getSchemaObject() );
			if ( $result->hasErrors() ) {
				if ( WP_DEBUG ) {
					var_dump( $result->getErrors() );
					die;
				}
			}
		} catch ( Exception $e ) {
			if ( WP_DEBUG ) {
				error_log( 'Problem while trying to access wp rest endpoint url for schema ' . $this->schemaUrl );
				error_log( $e );
				die;
			}
		}
	}

	/**
	 * Returns schema-object for current route.
	 *
	 * @return Schema
	 */
	protected function getSchemaObject(): Schema {
		$schemaObject = json_decode( $this->getSchemaJson() );
		unset( $schemaObject->{'$schema'} );
		unset( $schemaObject->{'$id'} );

		return Schema::fromJsonString( wp_json_encode( $schemaObject ) );
	}

	/**
	 * Returns schema json for current route.
	 *
	 * @return array|WP_Error
	 */
	protected function getSchemaJson() {
		$schemaArray = wp_remote_get( $this->schemaUrl );
		if ( is_array( $schemaArray ) && ! is_wp_error( $schemaArray )) {
			return $schemaArray;
		} else {
			throw new RuntimeException("Could not retrieve schema json from " . $this->schemaUrl );
		}
	}

	/**
	 * Adds schema-fields for output to current route.
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function add_additional_fields_schema( $schema ): array {
		$schemaArray = json_decode( $this->getSchemaJson(), true ); // TODO verify that this works and doesn't expects ['body'] from wp_remote_get?

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
	 * Returns true if current request is allowed.
	 *
	 * @return bool
	 */
	public static function hasPermission() : bool {
		$isApiActive            = Settings::getOption( 'commonsbooking_options_api', 'api-activated' );
		$anonymousAccessAllowed = Settings::getOption( 'commonsbooking_options_api', 'apikey_not_required' );
		$apiKey                 = array_key_exists( self::API_KEY_PARAM, $_REQUEST ) ? sanitize_text_field( $_REQUEST[ self::API_KEY_PARAM ] ) : false;
		$apiShare               = ApiShares::getByKey( $apiKey );

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
