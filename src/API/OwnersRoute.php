<?php


namespace CommonsBooking\API;


use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use Geocoder\Geocoder;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client;

class OwnersRoute extends BaseRoute
{

    /**
     * The base of this controller's route.
     *
     * @var string
     */
    protected $rest_base = 'owners';

    /**
     * Commons-API schema definition.
     * @var string
     */
    protected $schemaUrl = "https://raw.githubusercontent.com/wielebenwir/commons-api/master/commons-api.owners.schema.json";

    /**
     * Returns raw data collection.
     * @param $request
     *
     * @return \stdClass
     */
    public function getItemData($request)
    {
        $data = new \stdClass();
        $data->owners = [];
        return $data;
    }

    /**
     * Get a collection of items
     */
    public function get_item($request)
    {
        return $this->get_items($request);
    }

    /**
     * Get a collection of items
     *
     * @param \WP_REST_Request $request Full data about the request.
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_items($request)
    {
        $data = $this->getItemData($request);
        return new \WP_REST_Response($data, 200);
    }

    public function prepare_response_for_collection($itemdata)
    {
        return $itemdata;
    }

}
