<?php


namespace CommonsBooking\API;


use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use Geocoder\Geocoder;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client;

class ProjectsRoute extends BaseRoute
{

    /**
     * The base of this controller's route.
     *
     * @var string
     */
    protected $rest_base = 'projects';

    /**
     * Commons-API schema definition.
     * @var string
     */
    protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . "node_modules/commons-api/commons-api.projects.schema.json";

    /**
     * Returns raw data collection.
     * @param $request
     *
     * @return \stdClass
     */
    public function getItemData($request) {
        $data = [
            (object) [
                "id" => "1",
                "name" => get_bloginfo('name'),
                "url" => get_bloginfo('url'),
                "description" => get_bloginfo('description'),
                "language" => get_bloginfo('language'),
            ]
        ];
        return $data;
    }

    /**
     * Get one item from the collection
     */
    public function get_item($request)
    {
        return $this->get_items($request);
    }

    /**
     * Get a collection of projects
     */
    public function get_items($request)
    {
        $data = new \stdClass();
        $data->projects = $this->getItemData($request);

        if(WP_DEBUG) {
            $this->validateData($data);
        }
        return new \WP_REST_Response($data, 200);
    }

}
