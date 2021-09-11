<?php


namespace CommonsBooking\API;


use CommonsBooking\Repository\ApiShares;
use CommonsBooking\Settings\Settings;
use Opis\JsonSchema\Exception\SchemaNotFoundException;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

class BaseRoute extends \WP_REST_Controller
{

    const API_KEY_PARAM = 'apikey';

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
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'args' => array(),
                'permission_callback' => function () {
                    return self::hasPermission();
                }
            ),
        ));
        register_rest_route($namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args' => array(
                    'context' => array(
                        'default' => 'view',
                    ),
                ),
                'permission_callback' => function () {
                    return self::hasPermission();
                }
            ),
        ));

        register_rest_route($namespace, '/' . $this->rest_base . '/schema', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_public_item_schema'),
            'permission_callback' => function () {
                return self::hasPermission();
            }
        ));
    }

    /**
     * Validates data against defined schema.
     *
     * @param $data
     */
    public function validateData($data)
    {
        $validator = new Validator();

        try {
            $result = $validator->schemaValidation($data, $this->getSchemaObject());
            if ($result->hasErrors()) {
                if (WP_DEBUG) {
                    var_dump($result->getErrors());
                    die;
                }
            }
        } catch (SchemaNotFoundException $schemaNotFoundException) {
            //TODO: Resolve problem, that schemas cannot resolved.
        }
    }

    /**
     * Returns schema-object for current route.
     * @return Schema
     */
    protected function getSchemaObject()
    {
        $schemaObject = json_decode($this->getSchemaJson());
        unset($schemaObject->{'$schema'});
        $schemaObject = Schema::fromJsonString(json_encode($schemaObject));

        return $schemaObject;
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
     * Adds schema-fields for output to current route.
     *
     * @param array $schema
     *
     * @return array
     */
    public function add_additional_fields_schema($schema)
    {
        $schemaArray = json_decode($this->getSchemaJson(), true);

        return array_merge($schema, $schemaArray);
    }

    /**
     * Escapes JSON String for output.
     *
     * @param $string
     *
     * @return false|string
     */
    public function escapeJsonString($string)
    {
        return substr(json_encode($string), 1, -1) ?: "";
    }

    /**
     * Returns true if current request is allowed.
     * @return bool
     */
    public static function hasPermission()
    {
        $isApiActive = Settings::getOption('commonsbooking_options_api', 'api-activated');
        $anonymousAccessAllowed = Settings::getOption('commonsbooking_options_api', 'apikey_not_required');
        $apiKey = array_key_exists(self::API_KEY_PARAM, $_REQUEST) ? sanitize_text_field($_REQUEST[self::API_KEY_PARAM]) : false;
        $apiShare = ApiShares::getByKey($apiKey);

        // Only if api is active we return something
        if ($isApiActive) {
            // if anonymous access is allowed, api shares are ignored
            if ($anonymousAccessAllowed) {
                return true;
            } else {
                // check if there is a valid api key submitted
                if ($apiKey && $apiShare && $apiShare->isEnabled()) {
                    return true;
                }
            }
        }

        return false;
    }

}
