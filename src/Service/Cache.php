<?php

namespace CommonsBooking\Service;

use CommonsBooking\Map\MapShortcode;
use CommonsBooking\View\Calendar;
use CommonsBooking\Settings\Settings;
use Exception;
use CMB2_Field;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Exception\CacheException;

/**
 * Cache service wrapper around Symfony Cache Adapters.
 * Fails silently on exceptions during read or write operations.
 *
 * Use via {@see Cache::getCache()}, {@see Cache::getCacheItem()} and {@see Cache::setCacheItem()}.
 */
trait Cache {

	// TODO: #1842 fix this when issue is closed
	// TODO #1842 fix this when issue is closed
	// TODO: phpunit/phpunit:>3.0
	// TODO: php:>3.0
	// TODO: php:>=6.0
	// TODO: >2.10 has change after in 2.10
	// TODO: <3.0.0 do this when still below 3.0

	/**
	 * TODO: php:>=8.2 Refactor to constant after PHP 8.2
	 *
	 * @var string
	 */
	private static string $clearCacheHook = COMMONSBOOKING_PLUGIN_SLUG . '_clear_cache';

	/**
	 * Returns cache item based on calling class, function and args.
	 *
	 * @param mixed|null $custom_id
	 *
	 * @return mixed
	 */
	public static function getCacheItem( $custom_id = null ) {
		if ( WP_DEBUG ) {
			return false;
		}

		try {
			$cacheKey  = self::getCacheId( $custom_id );
			$cacheItem = self::getCache()->getItem( $cacheKey );
			if ( $cacheItem->isHit() ) {
				return $cacheItem->get();
			}
		} catch ( \Psr\Cache\CacheException $exception ) {
			commonsbooking_write_log( sprintf( 'Could not get cache item (params $custom_id = %s): message: %s, traceback %s', $custom_id, $exception->getMessage(), $exception->getTraceAsString() ) );
		} catch ( Exception $exception ) {
			commonsbooking_write_log( sprintf( 'Could not get cache item (params $custom_id = %s): message: %s, traceback %s', $custom_id, $exception->getMessage(), $exception->getTraceAsString() ) );
		}

		return false;
	}

	/**
	 * Returns cache id, based on calling class, function and args.
	 *
	 * @param mixed|null $custom_id
	 *
	 * @return string
	 * @since 2.7.2 added Plugin_Dir to Namespace to avoid conflicts on multiple instances on same server
	 * @since 2.9.4 added support for multisite caches
	 */
	public static function getCacheId( $custom_id = null ): string {
		$backtrace     = debug_backtrace()[2];
		$backtrace     = self::sanitizeArgsArray( $backtrace );
		$namespace     = COMMONSBOOKING_PLUGIN_DIR; // To account for multiple instances on same server
		$namespace    .= '_' . get_current_blog_id(); // To account for WP Multisite
		$namespace    .= '_' . str_replace( '\\', '_', strtolower( $backtrace['class'] ) );
		$namespace    .= '_' . $backtrace['function'];
		$backtraceArgs = $backtrace['args'];
		$namespace    .= '_' . serialize( $backtraceArgs );
		if ( $custom_id ) {
			$namespace .= $custom_id;
		}

		return md5( $namespace );
	}

	/**
	 * @param $backtrace
	 *
	 * @return mixed
	 */
	private static function sanitizeArgsArray( $backtrace ) {
		if ( array_key_exists( 'args', $backtrace ) &&
			count( $backtrace['args'] ) &&
			is_array( $backtrace['args'][0] )
		) {
			if ( array_key_exists( 'taxonomy', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['taxonomy'] );
			}
			if ( array_key_exists( 'term', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['term'] );
			}
			if ( array_key_exists( 'category_slug', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['category_slug'] );
			}
		}

		return $backtrace;
	}

	/**
	 * Returns an opinionated cache instance based on user settings or defaults.
	 * Falls back to filebased cache with default settings on {@see \Psr\Cache\CacheException}.
	 *
	 * Cache location and cache adapter can be configured via user {@see Settings}.
	 *
	 * @param string $namespace
	 * @param int    $defaultLifetime
	 * @param string $location
	 *
	 * @throws Exception
	 *
	 * @return TagAwareAdapterInterface
	 */
	public static function getCache( string $namespace = '', int $defaultLifetime = 0, string $location = '' ): TagAwareAdapterInterface {

		if ( $location === '' ) {
			$location = commonsbooking_sanitizeArrayorString( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_location' ) );
		}
		$identifier = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_adapter' ) ?: 'filesystem';
		try {
			$adapter = self::getAdapter(
				$identifier,
				$namespace,
				$defaultLifetime,
				$location
			);
		} catch ( \Psr\Cache\CacheException $e ) {
			// fall back to generic filesystem adapter, if it fails
			// TODO: this can throw Exception or CacheException
			$adapter = new FilesystemTagAwareAdapter( $namespace, $defaultLifetime );
			commonsbooking_write_log( $e->getMessage() . '\n' . 'Falling back to Filesystem adapter' );
		}
		return $adapter;
	}

	/**
	 * Will get all the available adapters for the cache.
	 * Adapters need to implement the AdapterInterface.
	 * When we select it from a menu, we only need to pass the labels.
	 *
	 * @param bool $onlyLabels Will get associative array with adapter identifier as key and translated label as value
	 * @return array
	 */
	public static function getAdapters( $onlyLabels = false ): array {
		$adapters = [
			'filesystem' => [
				'label' => __( 'Filesystem', 'commonsbooking' ),
				'factory' => function ( array $config ) {
					$location = $config['cacheLocation'] ?: sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'symfony-cache';
					if ( ! is_writable( $location ) ) {
						throw new CacheException(
							sprintf( commonsbooking_sanitizeHTML( __( 'Directory %s could not be written to.', 'commonsbooking' ) ), $config['cacheLocation'] )
						);
					}
					return new FilesystemTagAwareAdapter(
						$config['namespace'],
						$config['defaultLifetime'],
						$config['cacheLocation']
					);
				},
			],
			'redis' => [
				'label' => __( 'Redis', 'commonsbooking' ),
				'factory' => function ( array $config ) {
					return new RedisTagAwareAdapter(
						RedisTagAwareAdapter::createConnection( $config['cacheLocation'] ),
						$config['namespace'],
						$config['defaultLifetime']
					);
				},
			],
			'disabled' => [
				'label' => __( 'Disabled', 'commonsbooking' ),
				'factory' => fn() => new TagAwareAdapter( new NullAdapter() ),
			],
		];

		if ( $onlyLabels ) {
			$labels = [];
			foreach ( $adapters as $identifier => $label ) {
				$labels[ $identifier ] = $label['label'];
			}
			return $labels;
		} else {
			return $adapters;
		}
	}

	/**
	 * This will get an adapter by the adapter identifier.
	 *
	 * @param $identifier
	 * @param $namespace
	 * @param $defaultLifetime
	 * @param string $cacheLocation
	 * @return TagAwareAdapterInterface
	 * @throws \Psr\Cache\CacheException
	 */
	public static function getAdapter( $identifier, $namespace, $defaultLifetime, $cacheLocation = '' ): TagAwareAdapterInterface {
		$adapters = self::getAdapters();
		if ( ! array_key_exists( $identifier, $adapters ) ) {
			throw new CacheException( sprintf( 'Adapter %s not found', $identifier ) ); // Not translated bc this is a developer error
		}
		try {
			return $adapters[ $identifier ]['factory'](
				[
					'namespace' => $namespace,
					'defaultLifetime' => $defaultLifetime,
					'cacheLocation' => $cacheLocation,
				]
			);
		} catch ( Exception $e ) { // Symfony adapters do not always throw CacheException, for example the REDIS adapter can throw InvalidArgumentException
			throw new CacheException( $e->getMessage() . $e->getTraceAsString() );
		}
	}

	/**
	 * Saves cache item based on calling class, function and args.
	 *
	 * @param $value
	 * @param array       $tags
	 * @param mixed|null  $custom_id
	 * @param string|null $expirationString set expiration as timestamp or string 'midnight' to set expiration to 00:00 next day
	 *
	 * @return bool
	 */
	public static function setCacheItem( $value, array $tags, $custom_id = null, ?string $expirationString = null ): bool {
		try {
			// Set a default expiration to make sure, that we get rid of stale items, if there are some
			// too much space
			$expiration = 604800;

			$tags = array_map( 'strval', $tags );
			$tags = array_filter( $tags );

			if ( ! count( $tags ) ) {
				$tags = [ 'misc' ];
			}

			// if expiration is set to 'midnight' we calculate the duration in seconds until midnight
			if ( $expirationString == 'midnight' ) {
				$datetime   = current_time( 'timestamp' );
				$expiration = strtotime( 'tomorrow', $datetime ) - $datetime;
			}

			$cache     = self::getCache( '', intval( $expiration ) );
			$cacheKey  = self::getCacheId( $custom_id );
			$cacheItem = $cache->getItem( $cacheKey );
			$cacheItem->tag( $tags );
			$cacheItem->set( $value );
			$cacheItem->expiresAfter( intval( $expiration ) );

			return $cache->save( $cacheItem );
		} catch ( \Psr\Cache\CacheException $e ) {
			commonsbooking_write_log( sprintf( 'Could not set cache item (params $val = %s, $tags = %s, $custom_id = %s, $expirationString = %s): message: %s, traceback: %s', $value, implode( ', ', $tags ), $custom_id, $expirationString, $e->getMessage(), $e->getTraceAsString() ) );
		} catch ( Exception $e ) {
			commonsbooking_write_log( sprintf( 'Could not set cache item (params $val = %s, $tags = %s, $custom_id = %s, $expirationString = %s): message: %s, traceback: %s', $value, implode( ', ', $tags ), $custom_id, $expirationString, $e->getMessage(), $e->getTraceAsString() ) );
		}
		return false;
	}

	/**
	 * Deletes cache entries.
	 *
	 * @param array $tags
	 *
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function clearCache( array $tags = [] ) {
		if ( ! count( $tags ) ) {
			self::getCache()->clear();
		} else {
			self::getCache()->invalidateTags( $tags );
		}

		// Delete expired cache items (only for Pruneable Interfaces)
		if ( is_a( self::getCache(), 'Symfony\Component\Cache\PruneableInterface' ) ) {
			self::getCache()->prune();
		}

		set_transient( 'clearCacheHasBeenDone', true, 45 );
	}

	/**
	 * Calls clearCache using WP Cron.
	 * Why? ClearCache can be resource intensive on larger instances and should be offloaded.
	 *
	 * @param array $tags to clear cache for
	 *
	 * @return void
	 */
	public static function scheduleClearCache( array $tags = [] ) {
		$event = wp_schedule_single_event( time(), self::$clearCacheHook, [ $tags ], true );
		// TODO document why only on wp-error, why this can fail, why we don't re-try or do other things, instead of forcing the execution of this resource intensive task?
		if ( is_wp_error( $event ) ) {
			// runs the event right away, when scheduling fails
			self::clearCache( $tags );
		}
	}

	/**
	 * Add js to frontend on cache clear.
	 *
	 * @return void
	 */
	public static function addWarmupAjaxToOutput() {
		if ( get_transient( 'clearCacheHasBeenDone' ) ) {
			delete_transient( 'clearCacheHasBeenDone' );
			wp_register_script( 'cache_warmup', '', array( 'jquery' ), '', true );
			wp_enqueue_script( 'cache_warmup' );
			wp_add_inline_script(
				'cache_warmup',
				'
				jQuery.ajax({
		            url: cb_ajax_cache_warmup.ajax_url,
		            method: "POST",
		            data: {
		                _ajax_nonce: cb_ajax_cache_warmup.nonce,
		                action: "cb_cache_warmup"
		            }
				});'
			);
		}
	}

	public static function warmupCache() {
		try {
			global $wpdb;
			$table_posts = $wpdb->prefix . 'posts';

			// First get all pages with cb shortcodes
			$sql   = "SELECT post_content FROM $table_posts WHERE
			  post_content LIKE '%[cb_%]%' AND
			  post_type = 'page' AND
			  post_status = 'publish'";
			$pages = $wpdb->get_results( $sql );

			$shortcodeNamesToCache = array_keys( self::$cbShortCodeFunctions );

			$regex = get_shortcode_regex( $shortcodeNamesToCache ); // robust shortcode-regex generator from WordPress

			// Now extract shortcode calls including attributes and bodies
			$shortCodeCalls = [];
			foreach ( $pages as $page ) {
				preg_match_all( "/$regex/", $page->post_content, $shortcodeMatches, PREG_SET_ORDER );

				// Process each matched shortcode
				foreach ( $shortcodeMatches as $match ) {
					$shortCode        = $match[2]; // shortcode name e.g., "cb_search"
					$attributesString = isset( $match[3] ) ? $match[3] : ''; // e.g., " id=123"

					$shortCodeCalls[] = [
						'shortcode'  => $shortCode,
						'attributes' => self::getShortcodeAndAttributes( $shortCode . $attributesString )[1],
						'body'       => isset( $match[5] ) ? trim( $match[5] ) : '',
					];
				}
			}

			// Filter duplicate calls
			$shortCodeCalls = array_unique( $shortCodeCalls, SORT_REGULAR );

			self::runShortcodeCalls( $shortCodeCalls );

			wp_send_json( 'cache successfully warmed up' );
		} catch ( \Exception $exception ) {
			wp_send_json( 'something went wrong with cache warm up' );
		}
	}

	/**
	 * Renders connection status information for the cache adapter settings view.
	 *
	 * @param array      $field_args
	 * @param CMB2_Field $field
	 */
	public static function renderCacheStatus( array $field_args, CMB2_Field $field ) {
		$success           = true;
		$errorMessage      = '';
		$adapterIdentifier = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_adapter' );
		try {
			$adapter = self::getAdapter(
				$adapterIdentifier,
				'',
				0,
				commonsbooking_sanitizeArrayorString( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_location' ) )
			);
		} catch ( CacheException $e ) {
			$success      = false;
			$errorMessage = commonsbooking_sanitizeArrayorString( $e->getMessage() );
		}
		?>
		<div class="cmb-row cmb-type-text table-layout">
			<div class="cmb-th">
				<?php echo __( 'Connection status:', 'commonsbooking' ); ?>
			</div>
			<div class="cmb-th">
				<?php
				if ( $adapterIdentifier === 'disabled' ) {
					echo '<div style="color:orange">';
					echo __( 'Cache is disabled.', 'commonsbooking' );
				} elseif ( $success ) {
					echo '<div style="color:green">';
					echo __( 'Cache adapter successfully connected.', 'commonsbooking' );
				} else {
					echo '<div style="color:red">';
					echo $errorMessage;
				}
				echo '</div>';

				?>
			</div>
		</div>
		<?php
	}

	public static function renderClearCacheButton( $field_args, $field ) {
		?>
		<div class="cmb-row cmb-type-text ">
			<div class="cmb-th">
				<label for="clear-cache-button"><?php echo esc_html__( 'Clear all cache items', 'commonsbooking' ); ?></label>
			</div>
			<div class="cmb-td">
				<button type="submit" id="clear-cache-button" class="button button-secondary" name="submit-cmb"
						value="clear-cache">
					<?php echo esc_html__( 'Clear Cache', 'commonsbooking' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Iterates through array and statically executes given functions.
	 *
	 * @param array<array{shortcode: string, attributes: array, body: string}> $shortCodeCalls array of tuples of shortcode name strings and tuples of class + static function.
	 *
	 * @return void
	 */
	private static function runShortcodeCalls( array $shortCodeCalls ): void {
		foreach ( $shortCodeCalls as $shortCodeCall ) {
			$shortcodeFunction = $shortCodeCall['shortcode'];
			$attributes        = $shortCodeCall['attributes'];
			$shortcodeBody     = $shortCodeCall['body'];

			if ( array_key_exists( $shortcodeFunction, self::$cbShortCodeFunctions ) ) {
				list($class, $function) = self::$cbShortCodeFunctions[ $shortcodeFunction ];

				try {
					$class::$function( $attributes, $shortcodeBody );
				} catch ( Exception $e ) {
					// Writes error to log anyway
					error_log( (string) $e ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}
	}

	/**
	 * Extracts shortcode and attributes from shortcode string.
	 *
	 * @param $shortCode
	 *
	 * @return array
	 */
	private static function getShortcodeAndAttributes( $shortCode ) {
		$shortCodeParts = explode( ' ', $shortCode );
		$shortCodeParts = array_map(
			function ( $part ) {
				$trimmed = trim( $part );
				$trimmed = str_replace( "\xc2\xa0", '', $trimmed );
				return $trimmed;
			},
			$shortCodeParts
		);

		$shortCode = array_shift( $shortCodeParts );

		$args = [];
		foreach ( $shortCodeParts as $part ) {
			$parts = explode( '=', $part );
			$key   = $parts[0];
			$value = '';
			if ( count( $parts ) > 1 ) {
				$value = $parts[1];
				if ( preg_match( '/^".*"$/', $value ) ) {
					$value = substr( $value, 1, -1 );
				}
			}

			$args[ $key ] = $value;
		}

		return [ $shortCode, $args ];
	}

	private static $cbShortCodeFunctions = [
		'cb_items' => array( \CommonsBooking\View\Item::class, 'shortcode' ),
		'cb_bookings' => array( \CommonsBooking\View\Booking::class, 'shortcode' ),
		'cb_locations' => array( \CommonsBooking\View\Location::class, 'shortcode' ),
		'cb_map' => array( MapShortcode::class, 'execute' ),
		'cb_search' => array( \CommonsBooking\Map\SearchShortcode::class, 'execute' ),
		'cb_items_table' => array( Calendar::class, 'renderTable' ),
	];
}
