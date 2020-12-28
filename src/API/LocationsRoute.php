<?php


namespace CommonsBooking\API;


use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use Geocoder\Geocoder;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client;

class LocationsRoute extends BaseRoute
{

    /**
     * The base of this controller's route.
     *
     * @var string
     */
    protected $rest_base = 'locations';

    /**
     * Commons-API schema definition.
     * @var string
     */
    protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR."node_modules/commons-api/commons-api.locations.schema.json";

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var Provider
     */
    protected $provider;

    /**
     * @var Geocoder
     */
    protected $geocoder;

    /**
     * Get one item from the collection
     *
     * @param \WP_REST_Request $request Full data about the request.
     *
     * @return \WP_Error|\WP_REST_Response
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
        $data            = new \stdClass();
        $data->locations = $this->getItemData($request);

        if (WP_DEBUG) {
            $this->validateData($data);
        }

        return new \WP_REST_Response($data, 200);
    }

    public function getItemData($request)
    {
        $data       = new \stdClass();
        $data->type = "FeatureCollection";

        $params = $request->get_params();
        $args   = [];
        if (array_key_exists('id', $params)) {
            $args = [
                'p' => $params['id'],
            ];
        }

        $locations = \CommonsBooking\Repository\Location::get($args);
        $features  = [];

        foreach ($locations as $location) {
            try {
                $itemdata   = $this->prepare_item_for_response($location, $request);
                $features[] = $itemdata;
            } catch (\Exception $exception) {
                if (WP_DEBUG) {
                    error_log($exception->getMessage());
                }
            }
        }

        $data->features = $features;

        return $data;
    }

    /**
     * @param $item Location
     * @param $request
     *
     * @return \stdClass
     * @throws \Geocoder\Exception\Exception
     */
    public function prepare_item_for_response($item, $request)
    {
        $preparedItem             = new \stdClass();
        $preparedItem->type       = 'Feature';
        $preparedItem->properties = new \stdClass();

        $preparedItem->properties->id          = $item->ID."";
        $preparedItem->properties->name        = $item->post_title;
        $preparedItem->properties->description = $this->escapeJsonString($item->post_content);
        $preparedItem->properties->url         = get_permalink($item->ID);
        $preparedItem->properties->address     = $item->formattedAddressOneLine();

        $latitude  = get_post_meta($item->ID, 'geo_latitude', true);
        $longitude = get_post_meta($item->ID, 'geo_longitude', true);

        // If we have latitude and longitude definec, we use them.
        if ($latitude && $longitude) {
            $preparedItem->geometry              = new \stdClass();
            $preparedItem->geometry->type        = "Point";
            $preparedItem->geometry->coordinates = [
                floatval($longitude),
                floatval($latitude)
            ];
        } elseif ($item->formattedAddressOneLine()) {
            $addresses = $this->getGeocoder()->geocodeQuery(GeocodeQuery::create($item->formattedAddressOneLine()));
            if ( ! $addresses->isEmpty()) {
                /** @var NominatimAddress $address */
                $address                             = $addresses->first();
                $preparedItem->geometry              = new \stdClass();
                $preparedItem->geometry->type        = "Point";
                $preparedItem->geometry->coordinates = $address->getCoordinates()->toArray();

                // Save data to items
                update_post_meta(
                    $item->ID,
                    "geo_latitude",
                    $preparedItem->geometry->coordinates[1]
                );
                update_post_meta(
                    $item->ID,
                    "geo_longitude",
                    $preparedItem->geometry->coordinates[0]
                );
            } else {
                throw new \Exception('Location address missing. (ID: '.$item->ID.')');
            }
        }

        return $preparedItem;
    }

    /**
     * @return Geocoder
     */
    public function getGeocoder(): Geocoder
    {
        if ($this->geocoder == null) {
            $this->geocoder = new \Geocoder\StatefulGeocoder($this->getProvider(), 'en');
        }

        return $this->geocoder;
    }

    /**
     * @return Provider
     */
    public function getProvider(): Provider
    {
        if ($this->provider == null) {
            $this->provider = \Geocoder\Provider\Nominatim\Nominatim::withOpenStreetMapServer($this->getHttpClient(),
                $_SERVER['HTTP_USER_AGENT']);;
        }

        return $this->provider;
    }

    /**
     * @return Client
     */
    public function getHttpClient(): Client
    {
        if ($this->httpClient == null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

}
