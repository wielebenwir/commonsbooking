<?php


namespace CommonsBooking\Migration;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\CB1;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;
use WP_Query;

/**
 * The logic for handling the migration from CB1 to CB2.
 * The CB1 fields are fetched from the @see \CommonsBooking\Repository\CB1 repository and migrated using the
 * respective migration functions in this class.
 */
class Migration {

	/**
	 * Fields we don't want/need to migrate.
	 *
	 * @var string[]
	 */
	private static $ignoredMetaFields = [
		'_edit_last',
		'_edit_lock',
	];

	/**
	 * @return void
	 */
	public static function migrateAll() {
		// sanitize
		if ( $_POST['data'] == 'false' ) {
			$post_data = 'false';
		} else {
			$post_data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
			$post_data = commonsbooking_sanitizeArrayorString( $post_data );
		}

		if ( $post_data == 'false' ) {
			$tasks = [
				'locations'    => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'items'        => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'timeframes'   => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'bookings'     => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'bookingCodes' => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'termsUrl'     => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'options'      => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
				'taxonomies'   => [
					'index'    => 0,
					'complete' => 0,
					'failed'   => 0,
				],
			];
		} else {
			$tasks = $post_data;
		}

		$taskIndex = 0;
		$taskLimit = 40;

		$taskFunctions = [
			'locations'    => [
				'repoFunction'      => 'getLocations',
				'migrationFunction' => 'migrateLocation',
			],
			'items'        => [
				'repoFunction'      => 'getItems',
				'migrationFunction' => 'migrateItem',
			],
			'timeframes'   => [
				'repoFunction'      => 'getTimeframes',
				'migrationFunction' => 'migrateTimeframe',
			],
			'bookings'     => [
				'repoFunction'      => 'getBookings',
				'migrationFunction' => 'migrateBooking',
			],
			'bookingCodes' => [
				'repoFunction'      => 'getBookingCodes',
				'migrationFunction' => 'migrateBookingCode',
			],
			'termsUrl'     => [
				'repoFunction'      => false,
				'migrationFunction' => 'migrateUserAgreementUrl',
			],
			'options'      => [
				'repoFunction'      => false,
				'migrationFunction' => 'migrateCB1Options',
			],
			'taxonomies'   => [
				'repoFunction'      => 'getCB1Taxonomies',
				'migrationFunction' => 'migrateTaxonomy',
			],
		];

		foreach ( $tasks as $key => &$task ) {
			if (
				$task['complete'] == 0 &&
				array_key_exists( 'migrationFunction', $taskFunctions[ $key ] ) &&
				$taskFunctions[ $key ]['migrationFunction']
			) {
				if ( $taskIndex >= $taskLimit ) {
					break;
				}

				// Multi migration
				if (
					array_key_exists( 'repoFunction', $taskFunctions[ $key ] ) &&
					$taskFunctions[ $key ]['repoFunction']
				) {
					$items         = CB1::{$taskFunctions[ $key ]['repoFunction']}();
					$task['count'] = count( $items );

					// If there are items to migrate
					if ( count( $items ) ) {
						for ( $index = $task['index']; $index < count( $items ); $index++ ) {
							if ( $taskIndex++ >= $taskLimit ) {
								break;
							}

							$item = $items[ $index ];
							if ( ! self::{$taskFunctions[ $key ]['migrationFunction']}( $item ) ) {
								$task['failed'] += 1;
							}
							$task['index'] += 1;
						}
						if ( $task['index'] == count( $items ) ) {
							$task['complete'] = 1;
						}

						// No items for migration found
					} else {
						if ( $taskIndex++ >= $taskLimit ) {
							break;
						}
						$task['complete'] = 1;
					}

					// Single Migration
				} else {
					if ( $taskIndex++ >= $taskLimit ) {
						break;
					}

					if ( ! self::{$taskFunctions[ $key ]['migrationFunction']}() ) {
						$task['failed'] += 1;
					}
					$task['index']   += 1;
					$task['complete'] = 1;
				}
			}
		}

		wp_send_json( $tasks );
	}

	/**
	 * @param WP_Post $location CB1 Location
	 *
	 * @throws Exception|\CommonsBooking\Geocoder\Exception\Exception
	 */
	public static function migrateLocation( WP_Post $location ): bool {
		// Collect post data
		$postData = array_merge(
			$location->to_array(),
			[
				'post_type' => Location::$postType,
			]
		);

		// Remove existing post id
		unset( $postData['ID'] );

		// Exctract e-mails from CB1 contactinfo field so we can migrate it into new cb2 field _cb_location_email
		$cb1_location_emails = self::fetchEmails(
			get_post_meta(
				$location->ID,
				'commons-booking_location_contactinfo_text',
				true
			)
		);

		if ( $cb1_location_emails ) {
			$cb1_location_email_string = implode( ',', $cb1_location_emails );
		} else {
			$cb1_location_email_string = '';
		}

		// Allow overbooking of locked days where no timeframes are defined
		$allowClosed = Settings::getOption(
			'commons-booking-settings-bookings',
			'commons-booking_bookingsettings_allowclosed'
		) == 'on' ? 'on' : 'off';

		$cbMetaMappings = [
			COMMONSBOOKING_METABOX_PREFIX . 'location_street' => 'commons-booking_location_adress_street',
			COMMONSBOOKING_METABOX_PREFIX . 'location_city' => 'commons-booking_location_adress_city',
			COMMONSBOOKING_METABOX_PREFIX . 'location_postcode' => 'commons-booking_location_adress_zip',
			COMMONSBOOKING_METABOX_PREFIX . 'location_country' => 'commons-booking_location_adress_country',
			COMMONSBOOKING_METABOX_PREFIX . 'location_contact' => 'commons-booking_location_contactinfo_text',
			COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions' => 'commons-booking_location_openinghours',
			'_thumbnail_id' => '_thumbnail_id',
		];

		// Get all post meta;
		$postMeta = self::getFlatPostMeta( get_post_meta( $location->ID ) );

		// Remove no needed fields
		$postMeta = self::removeArrayItemsByKeys( $postMeta, self::$ignoredMetaFields );
		$postMeta = self::removeArrayItemsByKeys( $postMeta, array_values( $cbMetaMappings ) );

		// Map CB2 <-> CB1 field combinations
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'location_email' ]          = $cb1_location_email_string;
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID' ]        = $location->ID;
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range' ] = $allowClosed;
		foreach ( $cbMetaMappings as $cb2Field => $cb1Field ) {
			$postMeta[ $cb2Field ] = get_post_meta( $location->ID, $cb1Field, true );
		}

		$existingPost = self::getExistingPost( $location->ID, Location::$postType );

		return self::savePostData( $existingPost, $postData, $postMeta );
	}

	/**
	 * fetchEmails
	 * extract mails from a given string and return an array with email addresses
	 *
	 * @param mixed $text
	 *
	 * @return array
	 */
	public static function fetchEmails( $text ): array {
		$words = str_word_count( $text, 1, '.@-_' );

		return array_filter(
			$words,
			function ( $word ) {
				return filter_var( $word, FILTER_VALIDATE_EMAIL );
			}
		);
	}

	/**
	 * @param $meta
	 *
	 * @return array
	 */
	private static function getFlatPostMeta( $meta ): array {
		return array_map(
			function ( $item ) {
				return $item[0];
			},
			$meta
		);
	}

	private static function removeArrayItemsByKeys( $array, $keys ) {
		foreach ( $keys as $ignoredMetaField ) {
			if ( array_key_exists( $ignoredMetaField, $array ) ) {
				unset( $array[ $ignoredMetaField ] );
			}
		}

		return $array;
	}

	/**
	 * @param $id
	 * @param $type
	 *
	 * @param null $timeframe_type
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function getExistingPost( $id, $type, $timeframe_type = null ) {
		$args = array(
			'meta_key'     => COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID',
			'meta_value'   => $id,
			'meta_compare' => '=',
			'post_type'    => $type,
			'post_status'  => 'any',
			'nopaging'     => true,
		);

		// If we're searching for a timeframe, we need the type
		if ( $timeframe_type ) {
			$args = array(
				'post_type'   => $type,
				'post_status' => 'any',
				'nopaging'    => true,
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID',
						'value'   => $id,
						'compare' => '=',
					),
					array(
						'key'   => 'type',
						'value' => '' . $timeframe_type,
					),
				),
			);
		}

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			if ( count( $posts ) > 1 ) {
				throw new Exception( 'Migration duplicates found.' );
			}
			if ( count( $posts ) == 1 ) {
				return $posts[0];
			}
		}

		return null;
	}

	/**
	 * @param $existingPost
	 * @param $postData array Post data
	 * @param $postMeta array Post meta
	 *
	 * @return bool
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 */
	protected static function savePostData( $existingPost, array $postData, array $postMeta ): bool {

		$includeGeoData = array_key_exists( 'geodata', $_POST ) && sanitize_text_field( $_POST['geodata'] ) == 'true';

		if ( $existingPost instanceof WP_Post ) {
			$updatedPost = array_merge( $existingPost->to_array(), $postData );
			$postId      = wp_update_post( $updatedPost );
		} else {
			$postId = wp_insert_post( $postData );
		}
		if ( $postId ) {
			foreach ( $postMeta as $key => $value ) {
				update_post_meta(
					$postId,
					$key,
					$value
				);
			}

			if ( get_post_type( $postId ) == Location::$postType && $includeGeoData ) {
				$location = new \CommonsBooking\Model\Location( $postId );
				$location->updateGeoLocation();
				sleep( 1 );
			}

			// if elementor is active, we clone the elementor meta-keys
			if ( is_plugin_active( 'elementor/elementor.php' ) ) {
				self::migrateElementorMetaKeys( $existingPost->ID, $postId );
			}

			return true;
		}

		return false;
	}

	/**
	 * Copies elementor meta keys and values from existing CB1 to the new CB2 post
	 *
	 * @param mixed $cb1_id
	 * @param mixed $cb2_id
	 *
	 * @return void
	 */
	public static function migrateElementorMetaKeys( $cb1_id, $cb2_id ) {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';

		$sql       = $wpdb->prepare(
			"SELECT meta_key, meta_value FROM $table_postmeta WHERE meta_key LIKE '%%_elementor%%' AND post_id = %d",
			$cb1_id
		);
		$post_meta = $wpdb->get_results( $sql );
		if ( ! empty( $post_meta ) && is_array( $post_meta ) ) {
			$duplicate_insert_query = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES ";
			$value_cells            = array();

			foreach ( $post_meta as $meta_info ) {
				$meta_key      = sanitize_text_field( $meta_info->meta_key );
				$meta_value    = wp_slash( $meta_info->meta_value );
				$value_cells[] = "($cb2_id, '$meta_key', '$meta_value')";
			}

			$duplicate_insert_query .= implode( ', ', $value_cells ) . ';';
			$wpdb->query( $duplicate_insert_query );
		}
	}

	/**
	 * @param WP_Post $item
	 *
	 * @return bool
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 */
	public static function migrateItem( WP_Post $item ): bool {
		// Collect post data
		$postData = array_merge(
			$item->to_array(),
			[
				'post_type'    => Item::$postType,
				'post_excerpt' => get_post_meta(
					$item->ID,
					'commons-booking_item_descr',
					true
				),
			]
		);

		// Remove existing post id
		unset( $postData['ID'] );

		// Get all post meta;
		$postMeta = self::getFlatPostMeta( get_post_meta( $item->ID ) );

		// Remove no needed fields
		$postMeta = self::removeArrayItemsByKeys( $postMeta, self::$ignoredMetaFields );

		// CB2 <-> CB1
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID' ] = $item->ID;
		$postMeta['_thumbnail_id']                                      = get_post_meta( $item->ID, '_thumbnail_id', true );

		$existingPost = self::getExistingPost( $item->ID, Item::$postType );

		return self::savePostData( $existingPost, $postData, $postMeta );
	}

	/**
	 * @param $timeframe
	 *
	 * @return bool
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 */
	public static function migrateTimeframe( $timeframe ): bool {
		$cbItem     = self::getExistingPost( $timeframe['item_id'], Item::$postType );
		$cbLocation = self::getExistingPost( $timeframe['location_id'], Location::$postType );
		$weekdays   = '';

		// get closed days in cb1 timeframe to migrate them into new cb timeframe weekdays (inversion of days)
		$cb1_closeddays = get_post_meta( $timeframe['location_id'], 'commons-booking_location_closeddays', true );
		if ( is_array( $cb1_closeddays ) ) {
			$weekdays             = array( 1, 2, 3, 4, 5, 6, 7 );
			$weekdays             = array_diff( $weekdays, $cb1_closeddays );
			$timeframe_repetition = 'w'; // set repetition do weekly
		} else {
			$timeframe_repetition = 'd'; // set repetition to daily
		}

		// Collect post data
		$postData = [
			'post_title'  => $timeframe['timeframe_title'],
			'post_type'   => Timeframe::$postType,
			'post_name'   => Helper::generateRandomString(),
			'post_status' => 'publish',
		];

		// convert cb1 metadata in cb2 postmeta fields
		// CB2 <-> CB1
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID' ] = $timeframe['id'];
		$postMeta[ \CommonsBooking\Model\Timeframe::REPETITION_START ]  = strtotime( $timeframe['date_start'] );
		$postMeta[ \CommonsBooking\Model\Timeframe::REPETITION_END ]    = strtotime( $timeframe['date_end'] );
		$postMeta[ \CommonsBooking\Model\Timeframe::META_ITEM_ID ]      = $cbItem ? $cbItem->ID : '';
		$postMeta[ \CommonsBooking\Model\Timeframe::META_LOCATION_ID ]  = $cbLocation ? $cbLocation->ID : '';
		$postMeta['type'] = Timeframe::BOOKABLE_ID;
		$postMeta[ \CommonsBooking\Model\Timeframe::META_REPETITION ] = $timeframe_repetition;
		$postMeta['start-time']                                       = '00:00';
		$postMeta['end-time'] = '23:59';
		$postMeta['full-day'] = 'on';
		$postMeta['grid']     = '0';
		$postMeta['weekdays'] = $weekdays;
		$postMeta[ \CommonsBooking\Model\Timeframe::META_SHOW_BOOKING_CODES ]             = 'on';
		$postMeta[ \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS ] = Settings::getOption( 'commons-booking-settings-bookings', 'commons-booking_bookingsettings_daystoshow' );
		$postMeta[ \CommonsBooking\Model\Timeframe::META_MAX_DAYS ]                       = Settings::getOption( 'commons-booking-settings-bookings', 'commons-booking_bookingsettings_maxdays' );

		$existingPost = self::getExistingPost( $timeframe['id'], Timeframe::$postType, Timeframe::BOOKABLE_ID );

		return self::savePostData( $existingPost, $postData, $postMeta );
	}

	/**
	 * @param $booking
	 *
	 * @return bool
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 * @throws Exception
	 */
	public static function migrateBooking( $booking ): bool {
		$user       = get_user_by( 'id', $booking['user_id'] );
		$cbItem     = self::getExistingPost( $booking['item_id'], Item::$postType );
		$cbLocation = self::getExistingPost( $booking['location_id'], Location::$postType );

		// Collect post data
		$userName = 'unknown user';
		if ( $user ) {
			$userName = $user->get( 'user_nicename' );
		}

		$postData = [
			'post_title'  => 'Buchung CB1-Import ' . $userName . ' - ' . $booking['date_start'],
			'post_type'   => \CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
			'post_name'   => Helper::generateRandomString(),
			'post_status' => $booking['status'],
			'post_date'   => $booking['booking_time'],
			'post_author' => $booking['user_id'],

		];

		// CB2 <-> CB1
		$postMeta = [
			COMMONSBOOKING_METABOX_PREFIX . 'cb1_post_post_ID' => $booking['id'],
			\CommonsBooking\Model\Timeframe::REPETITION_START  => strtotime( $booking['date_start'] ),
			\CommonsBooking\Model\Timeframe::REPETITION_END    => strtotime( $booking['date_end'] ),
			\CommonsBooking\Model\Timeframe::META_ITEM_ID      => $cbItem ? $cbItem->ID : '',
			\CommonsBooking\Model\Timeframe::META_LOCATION_ID  => $cbLocation ? $cbLocation->ID : '',
			'type'                                             => Timeframe::BOOKING_ID,
			\CommonsBooking\Model\Timeframe::META_REPETITION   => 'norep',
			'start-time'                                       => '00:00',
			'end-time'                                         => '23:59',
			'full-day'                                         => 'on',
			'grid'                                             => '0',
			COMMONSBOOKING_METABOX_PREFIX . 'bookingcode'      => CB1::getBookingCode( $booking['code_id'] ),
		];

		$existingPost = self::getExistingPost( $booking['id'], \CommonsBooking\Wordpress\CustomPostType\Booking::$postType );

		return self::savePostData( $existingPost, $postData, $postMeta );
	}

	/**
	 * Migrates CB1 Booking Code to CB2.
	 *
	 * @param $bookingCode
	 *
	 * @return mixed
	 */
	public static function migrateBookingCode( $bookingCode ) {
		$cb2ItemId = CB1::getCB2ItemId( $bookingCode['item_id'] );
		$date      = $bookingCode['booking_date'];
		$code      = $bookingCode['bookingcode'];

		$bookingCode = new BookingCode(
			$date,
			$cb2ItemId,
			$code
		);

		return BookingCodes::persist( $bookingCode );
	}

	/**
	 * Migrates CB1 user agreement url option to CB2.
	 * Only relevant for legacy user profile.
	 *
	 * @return bool
	 */
	public static function migrateUserAgreementUrl() {
		$cb1_url = Settings::getOption( 'commons-booking-settings-pages', 'commons-booking_termsservices_url' );

		return Settings::updateOption( 'commonsbooking_options_migration', 'cb1-terms-url', $cb1_url );
	}

	/**
	 * Migrates some of the CB1 Options that can be transfered to CB2
	 */
	public static function migrateCB1Options() {
		// migrate Booking-Codes
		$cb1_bookingcodes = Settings::getOption( 'commons-booking-settings-codes', 'commons-booking_codes_pool' );
		Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes', $cb1_bookingcodes );

		// update sender e-mail
		$cb1_sender_email = Settings::getOption( 'commons-booking-settings-mail', 'commons-booking_mail_from' );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-email', $cb1_sender_email );

		// sender name
		$cb1_sender_name = Settings::getOption( 'commons-booking-settings-mail', 'commons-booking_mail_from_name' );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-name', $cb1_sender_name );
	}

	/**
	 * Migrates CB1 taxonomy to CB2 posts.
	 *
	 * @param $cb1Taxonomy
	 *
	 * @return void
	 */
	public static function migrateTaxonomy( $cb1Taxonomy ) {
		if ( $cb2PostId = CB1::getCB2PostIdByCB1Id( $cb1Taxonomy->object_id ) ) {
			$terms = wp_get_object_terms( $cb1Taxonomy->object_id, $cb1Taxonomy->taxonomy );
			$term  = array();
			foreach ( $terms as $t ) {
				$term[] = $t->slug;
			}

			wp_set_object_terms( $cb2PostId, $term, $cb1Taxonomy->taxonomy );
		}
	}
}
