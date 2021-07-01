<?php


namespace CommonsBooking\API;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Week;
use DateTime;
use DateTimeZone;
use Exception;
use stdClass;
use WP_Error;
use WP_REST_Response;

class AvailabilityRoute extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'availability';

	/**
	 * Commons-API schema definition.
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . "node_modules/commons-api/commons-api.availability.schema.json";

	/**
	 * @throws Exception
	 */
	public function getItemData( $id = false ): array {
		$slots    = [];
		$calendar = new Calendar(
			new Day( date( 'Y-m-d', time() ) ),
			new Day( date( 'Y-m-d', strtotime( '+2 weeks' ) ) ),
			[],
			$id ? [ $id ] : []
		);

		$doneSlots = [];
		/** @var Week $week */
		foreach ( $calendar->getWeeks() as $week ) {
			/** @var Day $day */
			foreach ( $week->getDays() as $day ) {
				foreach ( $day->getGrid() as $slot ) {
					$timeframe     = new Timeframe( $slot['timeframe'] );
					$timeFrameType = get_post_meta( $slot['timeframe']->ID, 'type', true );

					if ( $timeFrameType != \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ) {
						continue;
					}
					$availabilitySlot = new stdClass();

					// Init default timezone
					$timezone = new DateTimeZone( 'Europe/Berlin' );

					// Init DateTime object for start
					$dateTimeStart = new DateTime( 'now', $timezone );
					$dateTimeStart->setTimestamp( $slot['timestampstart'] );
					$availabilitySlot->start = $dateTimeStart->format( 'Y-m-d\TH:i:sP' );

					// Init DateTime object for end
					$dateTimeend = new DateTime( 'now', $timezone );
					$dateTimeend->setTimestamp( $slot['timestampend'] );
					$availabilitySlot->end = $dateTimeend->format( 'Y-m-d\TH:i:sP' );

					$availabilitySlot->locationId = "";
					if ( $timeframe->getLocation() ) {
						$availabilitySlot->locationId = $timeframe->getLocation()->ID . "";
					}

					$availabilitySlot->itemId = "";
					if ( $timeframe->getLocation() ) {
						$availabilitySlot->itemId = $timeframe->getItem()->ID . "";
					}

					$slotId = md5( serialize( $availabilitySlot ) );
					if ( ! in_array( $slotId, $doneSlots ) ) {
						$doneSlots[] = $slotId;
						$slots[]     = $availabilitySlot;
					}
				}
			}
		}

		return $slots;
	}

	/**
	 * Get one item from the collection
	 */
	public function get_item( $request ) {
		//get parameters from request
		$params             = $request->get_params();
		$data               = new stdClass();
		try {
			$data->availability = $this->getItemData( $params['id'] );

			//return a response or error based on some conditional
			if ( count( $data->availability ) ) {
//            if(WP_DEBUG) {
//                $this->validateData($data);
//            }
				return new WP_REST_Response( $data, 200 );
			} else {

			}
		} catch (Exception $e) {
			return new WP_Error( 'code', $e->getMessage() );
		}

	}

	/**
	 * Get a collection of items
	 *
	 * @param $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data               = new stdClass();
		$data->availability = $this->getItemData();

//        if(WP_DEBUG) {
//            $this->validateData($data);
//        }
		return new WP_REST_Response( $data, 200 );
	}

}
