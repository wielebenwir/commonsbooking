<?php


namespace CommonsBooking\API;

use WP_User;

/**
 * Defines an remote api consumer, where data is pushed to.
 * GBFS feed updates can be send via push actions to remote api consumers.
 *
 * @see user documentation for details regarding configuration https://commonsbooking.org/docs/schnittstellen-api/commonsbooking-api/#api-einstellungen-pro-api-freigabe
 * @see the openTripPlanner docs for 'push updates' https://docs.opentripplanner.org/en/v2.2.0/UpdaterConfig/#configuring-real-time-updaters
 */
class Share {

	private string $name;

	private bool $enabled;

	private string $pushUrl;

	private string $key;

	/**
	 * @var WP_User|null
	 */
	private $owner;

	/**
	 * Shares constructor.
	 *
	 * @param string       $name
	 * @param string       $enabled 'on' or 'off'
	 * @param string       $pushUrl
	 * @param string       $key
	 * @param null|WP_User $owner
	 */
	public function __construct( $name, $enabled, $pushUrl, $key, $owner ) {
		$this->name    = $name;
		$this->enabled = $enabled === 'on';
		$this->pushUrl = $pushUrl;
		$this->key     = $key;
		$this->owner   = $owner;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function isEnabled() {
		return $this->enabled;
	}

	/**
	 * @return string
	 */
	public function getPushUrl() {
		return $this->pushUrl;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return null|WP_User
	 */
	public function getOwner() {
		return $this->owner;
	}
}
