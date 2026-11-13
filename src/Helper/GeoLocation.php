<?php

namespace CommonsBooking\Helper;

/**
 * Minimal value object for a geocoded point.
 */
final class GeoLocation {

	public function __construct(
		private float $latitude,
		private float $longitude,
		private array $raw = []
	) {}

	public function getLatitude(): float {
		return $this->latitude;
	}

	public function getLongitude(): float {
		return $this->longitude;
	}

	/**
	 * The unmapped first result of the geocoding service (e.g. Nominatim), if available.
	 * Contains more fields (like "address") if requested via the commonsbooking_geocode_request filter.
	 */
	public function getRaw(): array {
		return $this->raw;
	}

	/**
	 * @return float[] GeoJSON order: [ longitude, latitude ]
	 */
	public function toArray(): array {
		return [ $this->longitude, $this->latitude ];
	}
}
