<?php


namespace CommonsBooking\API;

/**
 * Defines an remote api consumer, where data is pushed to.
 * GBFS feed updates can be send via push actions to remote api consumers.
 *
 * @see user documentation for details regarding configuration https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/#api-einstellungen-pro-api-freigabe
 * @see the openTripPlanner docs for 'push updates' https://docs.opentripplanner.org/en/v2.2.0/UpdaterConfig/#configuring-real-time-updaters
 */
class Share {

	private $name;

	private $enabled;

	private $pushUrl;

	private $key;

	private $owner;

	/**
	 * Shares constructor.
	 *
	 * @param $name
	 * @param $enabled
	 * @param $pushUrl
	 * @param $key
	 * @param $owner
	 */
	public function __construct( $name, $enabled, $pushUrl, $key, $owner ) {
		$this->name    = $name;
		$this->enabled = $enabled === 'on';
		$this->pushUrl = $pushUrl;
		$this->key     = $key;
		$this->owner   = $owner;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function isEnabled() {
		return $this->enabled;
	}

	/**
	 * @return mixed
	 */
	public function getPushUrl() {
		return $this->pushUrl;
	}

	/**
	 * @return mixed
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return mixed
	 */
	public function getOwner() {
		return $this->owner;
	}
}
