<?php


namespace CommonsBooking\API;

/**
 * Defines an remote api consumer, where data is pushed to.
 * GBFS feed updates can be send via push actions to remote api consumers.
 *
 * @see user documentation for details regarding configuration https://commonsbooking.org/documentation/api/commonsbooking-api
 * @see the openTripPlanner docs for 'push updates' https://docs.opentripplanner.org/en/v2.2.0/UpdaterConfig/#configuring-real-time-updaters
 */
class Share {

	private string $name;

	private bool $enabled;

	private string $pushUrl;

	private string $key;

	/**
	 * @var string
	 */
	private string $owner;

	/**
	 * Shares constructor.
	 *
	 * @param string $name
	 * @param string $enabled 'on' or 'off'
	 * @param string $pushUrl
	 * @param string $key
	 * @param string $owner
	 */
	public function __construct( string $name, string $enabled, string $pushUrl, string $key, string $owner ) {
		$this->name    = $name;
		$this->enabled = $enabled === 'on';
		$this->pushUrl = $pushUrl;
		$this->key     = $key;
		$this->owner   = $owner;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * @return string
	 */
	public function getPushUrl(): string {
		return $this->pushUrl;
	}

	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getOwner(): string {
		return $this->owner;
	}
}
