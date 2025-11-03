<?php


namespace CommonsBooking\API;

use Exception;
use RuntimeException;

use CommonsBooking\Repository\ApiShares;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Composer_Dependencies\Opis\JsonSchema\Schema;
use CommonsBooking\Composer_Dependencies\Opis\JsonSchema\Validator;
use CommonsBooking\Composer_Dependencies\Opis\JsonSchema\Errors\ErrorFormatter;
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
	 * If WP_DEBUG is enabled, prints schema errors or any exceptions that may occur to error_log.
	 *
	 * @param object $data instance of stdclass or object to validate.
	 */
	public function validateData( $data ) {
		$validator = new Validator();

		try {
			$result = $validator->validate( $data, $this->getSchemaObject() );
			if ( $result->hasError() ) {
				if ( WP_DEBUG ) {

					// Get the error
					$error = $result->error();

					// Create an error formatter
					$formatter = new ErrorFormatter();

					// Print helper
					$print = function ( $value ) {
						echo wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
					};

					$print(
						array(
							'errors'    => $formatter->formatOutput( $error, 'basic' ),
							'response'  => $data,
						)
					);

					die;
				}
			}
		} catch ( Exception $e ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
			if ( WP_DEBUG ) {
				error_log( 'Problem while trying to access wp rest endpoint url for schema ' . $this->schemaUrl );
				error_log( $e );
				die;
			}
			// phpcs:enable
		}
	}

	/**
	 * Returns schema-object for current route.
	 *
	 * @return Schema
	 */
	protected function getSchemaObject(): object {
		$schemaObject = json_decode( $this->getSchemaJson() );
		unset( $schemaObject->{'$schema'} );
		unset( $schemaObject->{'$id'} );

		return $schemaObject;
	}

	/**
	 * Returns schema json for current route.
	 *
	 * @throws RuntimeException On missing schema files.
	 * @return string
	 */
	protected function getSchemaJson(): string {
		$schemaArray = file_get_contents( $this->schemaUrl ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( $schemaArray ) {
			return $schemaArray;
		} else {
			throw new RuntimeException( 'Could not retrieve schema json from ' . esc_url( $this->schemaUrl ) );
		}
	}

	/**
	 * Adds schema-fields for output to current route.
	 *
	 * @param array $schema Assoc array of schema json object.
	 * @return array
	 */
	public function add_additional_fields_schema( $schema ): array {
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
	 * Returns true if current request is allowed.
	 *
	 * @return bool
	 */
	public static function hasPermission(): bool {
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
