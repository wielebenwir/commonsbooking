<?php


namespace CommonsBooking\API;

use stdClass;
use WP_REST_Response;

/**
 * Endpoint for information about the lending organisation.
 * Infos like site name, description etc. is retrieved from general WordPress settings @see https://wordpress.com/support/general-settings/
 *
 * Full schema see, @see https://github.com/wielebenwir/commons-api/blob/master/commons-api.projects.schema.json
 */
class ProjectsRoute extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'projects';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/commons-api-json-schema/commons-api.projects.schema.json';

	/**
	 * Get one item from the collection
	 */
	public function get_item( $request ) {
		return $this->get_items( $request );
	}

	/**
	 * Get a collection of projects
	 */
	public function get_items( $request ): WP_REST_Response {
		$data           = new stdClass();
		$data->projects = $this->getItemData();

		if ( WP_DEBUG ) {
			$this->validateData( $data );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Returns raw data collection.
	 *
	 * @return object[]
	 */
	public function getItemData(): array {
		return [
			(object) [
				'id'          => '1',
				'name'        => get_bloginfo( 'name' ),
				'url'         => get_bloginfo( 'url' ),
				'description' => get_bloginfo( 'description' ),
				'language'    => get_bloginfo( 'language' ),
			],
		];
	}
}
