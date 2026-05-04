<?php

namespace CommonsBooking\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;
use Exception;

/**
 * Weather Service
 *
 * Provides weather forecast data for bookings as template tags in emails.
 *
 * Available template tags (resolved when the booking object is in scope):
 *   - {{booking:weatherForecast}}  - Human-readable summary (min/max/avg)
 *   - {{booking:weatherSummary}}   - Same as weatherForecast (alias)
 *   - {{booking:weatherWarning}}   - Empty unless an extreme-temperature warning applies
 *   - {{booking:weatherMin}}       - Lowest temperature in °C (numeric)
 *   - {{booking:weatherMax}}       - Highest temperature in °C (numeric)
 *   - {{booking:weatherMean}}      - Mean temperature in °C (rounded, numeric)
 *
 * Data source: BrightSky API (https://brightsky.dev) – free, DWD-backed.
 *
 * @since 2.x
 */
class WeatherService {

	public const OPTION_GROUP    = 'commonsbooking_options_advanced-options';
	public const OPTION_ENABLED  = 'weather_enabled';
	public const OPTION_LOW      = 'weather_threshold_low';
	public const OPTION_HIGH     = 'weather_threshold_high';
	public const OPTION_ENDPOINT = 'weather_api_endpoint';

	public const DEFAULT_ENDPOINT  = 'https://api.brightsky.dev/weather';
	public const DEFAULT_LOW       = 4;
	public const DEFAULT_HIGH      = 30;
	public const CACHE_TTL_SECONDS = HOUR_IN_SECONDS;
	public const TRANSIENT_PREFIX  = 'cb_weather_';

	/**
	 * Registers all weather-related template tag filters.
	 *
	 * Called from Plugin::init().
	 */
	public static function initHooks(): void {
		$tags = array(
			'weatherForecast',
			'weatherSummary',
			'weatherWarning',
			'weatherMin',
			'weatherMax',
			'weatherMean',
		);

		foreach ( $tags as $tag ) {
			add_filter(
				"commonsbooking_tag_cb_booking_{$tag}",
				array( self::class, 'resolveTag' ),
				10,
				3
			);
		}
	}

	/**
	 * Filter callback for commonsbooking_tag_cb_booking_<weatherTag>.
	 *
	 * @param mixed  $value    Current value (typically null/empty when no model method matched).
	 * @param mixed  $wpObject The booking object passed by CB::get().
	 * @param mixed  $args     Optional args (unused).
	 *
	 * @return string
	 */
	public static function resolveTag( $value, $wpObject = null, $args = null ): string {
		// If something else already resolved a value, keep it.
		if ( ! empty( $value ) && is_string( $value ) ) {
			return $value;
		}

		if ( ! self::isEnabled() ) {
			return '';
		}

		if ( ! $wpObject instanceof Booking ) {
			// CB::get() may pass a WP_Post; try casting.
			if ( is_object( $wpObject ) && isset( $wpObject->ID ) ) {
				try {
					$wpObject = new Booking( get_post( $wpObject->ID ) );
				} catch ( Exception $e ) {
					return '';
				}
			} else {
				return '';
			}
		}

		// Determine which tag is currently being resolved by looking at current_filter().
		$currentFilter = current_filter();
		$tag           = (string) preg_replace( '/^commonsbooking_tag_cb_booking_/', '', $currentFilter );

		try {
			$summary = self::getSummaryForBooking( $wpObject );
		} catch ( Exception $e ) {
			self::logError( 'Weather lookup failed: ' . $e->getMessage() );
			return '';
		}

		if ( empty( $summary ) ) {
			return '';
		}

		return self::formatTag( $tag, $summary );
	}

	/**
	 * Returns true when the feature is enabled in admin settings.
	 */
	public static function isEnabled(): bool {
		$value = Settings::getOption( self::OPTION_GROUP, self::OPTION_ENABLED );
		return $value === 'on' || $value === '1' || $value === true;
	}

	/**
	 * Builds the analyzed weather summary array for a booking.
	 *
	 * @return array{min:float,max:float,mean:float,median:float,warning:string}|null
	 * @throws Exception
	 */
	public static function getSummaryForBooking( Booking $booking ): ?array {
		$location = $booking->getLocation();
		if ( ! $location ) {
			return null;
		}

		$lat = $location->getMeta( 'geo_latitude' );
		$lon = $location->getMeta( 'geo_longitude' );

		if ( $lat === '' || $lon === '' || $lat === null || $lon === null ) {
			return null;
		}

		$lat = (float) $lat;
		$lon = (float) $lon;

		$startTs = (int) $booking->getStartDate();
		if ( ! $startTs ) {
			return null;
		}

		$date = wp_date( 'Y-m-d', $startTs );

		$weather = self::fetchWeather( $lat, $lon, $date );
		if ( empty( $weather ) || empty( $weather['weather'] ) ) {
			return null;
		}

		return self::analyzeTemperatures( $weather['weather'] );
	}

	/**
	 * Fetches weather data from BrightSky (with transient caching).
	 *
	 * @return array<string,mixed>|null Decoded JSON response, or null on failure.
	 */
	protected static function fetchWeather( float $lat, float $lon, string $date ): ?array {
		$cacheKey = self::TRANSIENT_PREFIX . md5( $lat . '|' . $lon . '|' . $date );
		$cached   = get_transient( $cacheKey );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$endpoint = Settings::getOption( self::OPTION_GROUP, self::OPTION_ENDPOINT );
		if ( empty( $endpoint ) || ! is_string( $endpoint ) ) {
			$endpoint = self::DEFAULT_ENDPOINT;
		}

		$url = add_query_arg(
			array(
				'lat'  => $lat,
				'lon'  => $lon,
				'date' => $date,
			),
			$endpoint
		);

		/**
		 * Filter: commonsbooking_weather_request_args
		 *
		 * Allows customizing the wp_remote_get() args for the weather API.
		 */
		$args = apply_filters(
			'commonsbooking_weather_request_args',
			array(
				'timeout'    => 5,
				'user-agent' => 'CommonsBooking/' . ( defined( 'COMMONSBOOKING_VERSION' ) ? COMMONSBOOKING_VERSION : 'dev' ),
			)
		);

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			self::logError( 'Weather API request failed: ' . $response->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			self::logError( 'Weather API returned HTTP ' . $code );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			self::logError( 'Weather API returned invalid JSON' );
			return null;
		}

		set_transient( $cacheKey, $data, self::CACHE_TTL_SECONDS );

		return $data;
	}

	/**
	 * Analyzes a list of weather entries and returns aggregated stats.
	 *
	 * Mirrors the logic of test_weather.php::analyze_temperatures().
	 *
	 * @param array<int,array<string,mixed>> $entries
	 * @return array{min:float,max:float,mean:float,median:float,warning:string}|null
	 */
	protected static function analyzeTemperatures( array $entries ): ?array {
		$temperatures = array();
		foreach ( $entries as $entry ) {
			if ( isset( $entry['temperature'] ) && is_numeric( $entry['temperature'] ) ) {
				$temperatures[] = (float) $entry['temperature'];
			}
		}

		if ( empty( $temperatures ) ) {
			return null;
		}

		sort( $temperatures );
		$count  = count( $temperatures );
		$min    = $temperatures[0];
		$max    = $temperatures[ $count - 1 ];
		$mean   = array_sum( $temperatures ) / $count;
		$median = $temperatures[ (int) floor( $count / 2 ) ];

		$low  = self::getLowThreshold();
		$high = self::getHighThreshold();

		$warning = '';
		if ( $min < $low ) {
			$warning = sprintf(
				/* translators: %1$s: lowest temperature, %2$s: low threshold */
				__( 'Warning: Temperatures below %2$s°C expected (lowest: %1$s°C).', 'commonsbooking' ),
				self::fmt( $min ),
				self::fmt( $low )
			);
		} elseif ( $max > $high ) {
			$warning = sprintf(
				/* translators: %1$s: highest temperature, %2$s: high threshold */
				__( 'Warning: Temperatures above %2$s°C expected (highest: %1$s°C).', 'commonsbooking' ),
				self::fmt( $max ),
				self::fmt( $high )
			);
		}

		return array(
			'min'     => $min,
			'max'     => $max,
			'mean'    => $mean,
			'median'  => $median,
			'warning' => $warning,
		);
	}

	/**
	 * Formats the resolved summary array for a particular tag.
	 *
	 * @param array<string,mixed> $summary
	 */
	protected static function formatTag( string $tag, array $summary ): string {
		switch ( $tag ) {
			case 'weatherMin':
				return self::fmt( $summary['min'] );
			case 'weatherMax':
				return self::fmt( $summary['max'] );
			case 'weatherMean':
				return self::fmt( $summary['mean'] );
			case 'weatherWarning':
				return (string) $summary['warning'];
			case 'weatherForecast':
			case 'weatherSummary':
			default:
				$line = sprintf(
					/* translators: 1: min, 2: max, 3: mean (all °C) */
					__( 'Weather forecast: low %1$s°C, high %2$s°C, average %3$s°C.', 'commonsbooking' ),
					self::fmt( $summary['min'] ),
					self::fmt( $summary['max'] ),
					self::fmt( $summary['mean'] )
				);
				if ( ! empty( $summary['warning'] ) ) {
					$line .= ' ' . $summary['warning'];
				}
				return $line;
		}
	}

	protected static function fmt( float $value ): string {
		return number_format_i18n( $value, 1 );
	}

	protected static function getLowThreshold(): float {
		$v = Settings::getOption( self::OPTION_GROUP, self::OPTION_LOW );
		return is_numeric( $v ) ? (float) $v : (float) self::DEFAULT_LOW;
	}

	protected static function getHighThreshold(): float {
		$v = Settings::getOption( self::OPTION_GROUP, self::OPTION_HIGH );
		return is_numeric( $v ) ? (float) $v : (float) self::DEFAULT_HIGH;
	}

	protected static function logError( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[CommonsBooking WeatherService] ' . $message );
		}
	}
}
