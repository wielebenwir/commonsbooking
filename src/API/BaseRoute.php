<?php


namespace CommonsBooking\API;


use Opis\JsonSchema\Exception\SchemaNotFoundException;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

class BaseRoute extends \WP_REST_Controller
{

    protected $schemaUrl;

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $version = '1';
        $namespace = COMMONSBOOKING_PLUGIN_SLUG . '/v' . $version;
        register_rest_route($namespace, '/' . $this->rest_base, array(
            array(
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'args'     => array(),
            )
        ));
        register_rest_route($namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args'     => array(
                    'context' => array(
                        'default' => 'view',
                    ),
                ),
            ),
        ));

        register_rest_route($namespace, '/' . $this->rest_base . '/schema', array(
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_public_item_schema'),
        ));
    }

    /**
     * Returnes schema json for current route.
     * @return false|string
     */
    protected function getSchemaJson()
    {
        return file_get_contents($this->schemaUrl);
    }

    /**
     * Returns schema-object for current route.
     * @return Schema
     */
    protected function getSchemaObject() {
        $schemaObject = json_decode($this->getSchemaJson());
        unset($schemaObject->{'$schema'});
        $schemaObject = Schema::fromJsonString(json_encode($schemaObject));

        return $schemaObject;
    }

    /**
     * Validates data against defined schema.
     * @param $data
     */
    public function validateData($data)
    {
        $validator = new Validator();

        try {
            $result = $validator->schemaValidation($data, $this->getSchemaObject());
            if($result->hasErrors()) {
                if(WP_DEBUG) {
                    var_dump($result->getErrors());
                    die;
                }
            }
        } catch (SchemaNotFoundException $schemaNotFoundException) {
            //TODO: Resolve problem, that schemas cannot resolved.
        }
    }

    /**
     * Adds schema-fields for output to current route.
     * @param array $schema
     *
     * @return array
     */
    public function add_additional_fields_schema($schema)
    {
        $schemaArray = json_decode($this->getSchemaJson(), true);
        return array_merge($schema, $schemaArray);
    }

}
