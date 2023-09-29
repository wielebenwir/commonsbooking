<?php


namespace CommonsBooking\Migration;

use CommonsBooking\Exception\TimeframeInvalidException;
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
use \Closure;

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
	private static bool $includeGeoData = false;

	//tells us if migration has been called from the CLI
	private static bool $cliCall = false;
	//will disable check for already existing posts, warning, may cause corrupted data
	private static bool $noPostCheck = false;
	private static array $itemCache = [];
	private static array $locationCache = [];
	private static bool $elementorActive;
	private static array $bookingCodeCache;

	/**
	 * The migration function called from the frontend request. This function is called via ajax.
	 * It is called multiple times until all tasks are done.
	 * @return void
	 */
	public static function ajaxMigrateAll() {
		// sanitize
		if ( $_POST['data'] == 'false' ) {
			$post_data = 'false';
		} else {
			$post_data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
			$post_data = commonsbooking_sanitizeArrayorString( $post_data );
		}

		if ( $post_data == 'false' ) {
			$tasks = self::getDefaultTasks();
		} else {
			$tasks = $post_data;
		}

		if (
			array_key_exists( 'geodata', $_POST )
			&& sanitize_text_field( $_POST['geodata'] ) == 'true' ) {
			self::$includeGeoData = true;
		}

		// Limit the number of tasks to run per ajax request
		$taskLimit = 40;

		$tasks = self::runTasks( $tasks, self::getTaskFunctions(), $taskLimit );

		wp_send_json( $tasks );
	}

	/**
	 * The migration function that is called from the CLI.
	 *
	 * @param   bool  $includeGeoData
	 * @param   bool  $noPostCheck
	 *
	 * @return void
	 */
	public static function cliMigrateAll(bool $includeGeoData, bool $noPostCheck = false){
		//disable geoData fetching for fLotte
		$includeGeoData = false;
		$currentTime = time();
		if ($includeGeoData) {
			\WP_CLI::log( 'CommonsBooking: Including geodata in migration. This may take a while.' );
			self::$includeGeoData = true;
		}
		if ($noPostCheck) {
			\WP_CLI::log( 'CommonsBooking: No post check enabled for booking migration. This might speed up migration times but might also leave you with doubled bookings.' );
			self::$noPostCheck = true;
		}

		self::$cliCall = true;

		\WP_CLI::log( 'CommonsBooking: Starting migration...' );
		self::writeToErrorLog("Migration started at " . date('Y-m-d H:i:s'));
		\WP_CLI::log( 'CommonsBooking: Setting WP_IMPORTING to true.');
		define( 'WP_IMPORTING', true );
		\WP_CLI::log( 'CommonsBooking: Enabling wp_defer_term_counting  && wp_defer_comment_counting to speed up migration. Will be disabled after migration.' );
		wp_defer_term_counting(true);
		wp_defer_comment_counting( true );

		\WP_CLI::log('CommonsBooking: Disabling autocommit to speed up migration. Will be enabled after migration.' );
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );

		$tasks = self::getDefaultTasks();
		while ( ! self::tasksDone( $tasks) ){
			$tasks = self::runTasks( $tasks, self::getTaskFunctions(), 100 );
			foreach ( $tasks as $key => $task) {
				if ( $task['complete'] == 1) {
					continue;
				}
				\WP_CLI::log( 'Migrating ' . $key. ': ' . $task['index'] . '/' . $task['count'] );
				break;
			}
		}
		\WP_CLI::log( 'CommonsBooking: Disabling wp_defer_term_counting && wp_defer_comment_counting.' );
		wp_defer_term_counting(false);
		wp_defer_comment_counting( false );
		wp_suspend_cache_addition(false);
		\WP_CLI::log( 'CommonsBooking: Committing to database' );
		$wpdb->query( 'COMMIT;' );
		\WP_CLI::log( 'CommonsBooking: Enabling autocommit' );
		$wpdb->query( 'SET autocommit = 1;' );
		$doneMessage = sprintf( 'CommonsBooking: Migration done in %s hours %s minutes %s seconds.', floor( ( time() - $currentTime ) / 3600 ), floor( ( ( time() - $currentTime ) / 60 ) % 60 ), floor( ( time() - $currentTime ) % 60 ) );
		\WP_CLI::success( $doneMessage );
		self::writeToErrorLog( $doneMessage );
		self::writeToErrorLog("Migration done at " . date('Y-m-d H:i:s'));
	}

	/**
	 * @param WP_Post $location CB1 Location
	 *
	 * @throws Exception|\Geocoder\Exception\Exception
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
			'geo_latitude'  => 'cb-map_latitude',
			'geo_longitude' => 'cb-map_longitude'
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

		//set all locations to use global settings (just for fLotte)
		$postMeta[ COMMONSBOOKING_METABOX_PREFIX . 'use_global_settings' ] = 'on';

		$existingPost = self::getExistingPost( $location->ID, Location::$postType );

		return self::savePostData( $existingPost, $postData, $postMeta, self::$includeGeoData );
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
				wp_delete_post($posts);
				return null;
			}
			if ( count( $posts ) == 1 ) {
				return $posts[0];
			}
		}

		return null;
	}

	/**
	 * @param         $existingPost
	 * @param         $postData array Post data
	 * @param         $postMeta array Post meta
	 * @param   bool  $includeGeoData
	 *
	 * @return bool
	 * @throws \Geocoder\Exception\Exception
	 */
	protected static function savePostData( $existingPost, array $postData, array $postMeta, bool $includeGeoData = false): bool {

		//append postMeta to postData
		$postData['meta_input'] = $postMeta;

		if ( $existingPost instanceof WP_Post ) {
			$updatedPost = array_merge( $existingPost->to_array(), $postData );
			$postId      = wp_update_post( $updatedPost );
		} else {
			$postId = wp_insert_post( $postData );
		}
		if ( $postId ) {

			if ( $includeGeoData && get_post_type( $postId ) == Location::$postType) {
				$location = new \CommonsBooking\Model\Location( $postId );
				$location->updateGeoLocation();
				sleep( 1 );
			}

			//fLotte Migration: Prüfen ob alle Timeframes die erstellt wurden auch gültig sind (keine Überlappungen)
			if ( get_post_type ( $postId ) == Timeframe::$postType ) {
				$timeframe = new \CommonsBooking\Model\Timeframe( $postId );
				try {
					$timeframe->isValid();
				} catch ( TimeframeInvalidException $e ) {
					\WP_CLI::log( 'Timeframe ' . $postId . ' did not pass the validity check. We are still keeping it.' );
					\WP_CLI::log( 'Reason: ' . $e->getMessage() );
					self::writeToErrorLog( 'Timeframe ' . $postId . ' invalid. Reason: ' . $e->getMessage() );
					return false;
				}
			}

			// if elementor is active, we clone the elementor meta-keys
			if ( self::$elementorActive ) {
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
	 * @throws \Geocoder\Exception\Exception
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
	 * @throws \Geocoder\Exception\Exception
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
		$tfEnd = strtotime( $timeframe['date_end'] );
		//only keep, if it's in the future
		if ($tfEnd < time())
		{
			$postMeta[ \CommonsBooking\Model\Timeframe::REPETITION_END ]    = strtotime( $timeframe['date_end'] );
		}
		$postMeta[ \CommonsBooking\Model\Timeframe::META_ITEM_ID ]      = $cbItem ? $cbItem->ID : '';
		$postMeta[ \CommonsBooking\Model\Timeframe::META_LOCATION_ID ]  = $cbLocation ? $cbLocation->ID : '';
		$postMeta['type']                                               = Timeframe::BOOKABLE_ID;
		$postMeta[ \CommonsBooking\Model\Timeframe::META_REPETITION ]   = $timeframe_repetition;
		$postMeta['start-time']                                         = '00:00';
		$postMeta['end-time']                                           = '23:59';
		$postMeta['full-day']                                           = 'on';
		$postMeta['grid']                                               = '0';
		$postMeta['weekdays']                                           = $weekdays;
		//Enable for fLotte Migration
		$postMeta['create-booking-codes']                               = 'on';
		$postMeta['show-booking-codes']                                 = 'on';
		$postMeta[ \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS] = Settings::getOption( 'commons-booking-settings-bookings', 'commons-booking_bookingsettings_daystoshow' );
		$postMeta[ \CommonsBooking\Model\Timeframe::META_MAX_DAYS ]     = Settings::getOption( 'commons-booking-settings-bookings', 'commons-booking_bookingsettings_maxdays' );

		$existingPost = self::getExistingPost( $timeframe['id'], Timeframe::$postType, Timeframe::BOOKABLE_ID );

		return self::savePostData( $existingPost, $postData, $postMeta );
	}

	/**
	 * @param $booking
	 *
	 * @return bool
	 * @throws \Geocoder\Exception\Exception
	 * @throws Exception
	 */
	public static function migrateBooking( $booking ): bool {
		$user       = get_user_by( 'id', $booking['user_id'] );
		//fLotte Migration: Pending Buchungen nicht übernehmen. Das gibt ein "failed" zurück, was aber nicht schlimm ist.
		if ( $booking['status'] == 'pending' ) {
			return false;
		}
		if ( self::$cliCall) {
			if ( empty(self::$itemCache[$booking['item_id']]) ){
				$existingItem = self::getExistingPost( $booking['item_id'], Item::$postType );
				self::$itemCache[ $booking['item_id'] ] = $existingItem;
				\WP_CLI::log('Wrote item to cache');
			}
			if (empty(self::$locationCache[$booking['location_id']])) {
				$existingLocation = self::getExistingPost( $booking['location_id'], Location::$postType );
				self::$locationCache[ $booking['location_id'] ] = $existingLocation;
				\WP_CLI::log('Wrote location to cache');
			}
			$cbItem     = self::$itemCache[ $booking['item_id'] ];
			$cbLocation = self::$locationCache[ $booking['location_id'] ];
		}
		else {
			$cbItem     = self::getExistingPost( $booking['item_id'], Item::$postType );
			$cbLocation = self::getExistingPost( $booking['location_id'], Location::$postType );
		}

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
		$bookingCode = CB1::getBookingCode( $booking['code_id'] );
		$postMeta    = [
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
			'cancellation_time'                                => $booking['cancellation_time'] ?? '',
			'comment'                                          => $booking['comment'] ?? '',
			COMMONSBOOKING_METABOX_PREFIX . 'bookingcode'      => $bookingCode,
		];

		if ( ! self::$noPostCheck) {
			$existingPost = self::getExistingPost( $booking['id'], \CommonsBooking\Wordpress\CustomPostType\Booking::$postType );
		}
		else {
			$existingPost = null;
		}

		return self::savePostData( $existingPost,
			$postData,
			$postMeta );
	}

	public static function migrateBookingsPreTask() {
		add_filter( 'pre_wp_unique_post_slug',
			fn( $override_slug, $slug, $post_id, $post_status, $post_type, $post_parent ) => Helper::generateRandomString(), 10, 6
		);
		wp_suspend_cache_addition(true);
	}
	
	public static function migrateBookingsPostTask() {
		remove_filter( 'pre_wp_unique_post_slug',
			fn( $override_slug, $slug, $post_id, $post_status, $post_type, $post_parent ) => Helper::generateRandomString()
		);
		wp_suspend_cache_addition(false);
	}

	/**
	 * Migrates CB1 Booking Code to CB2.
	 *
	 * @param $bookingCode
	 *
	 * @return mixed
	 */
	public static function migrateBookingCode( $bookingCode ) {
		$cb2LocationId  = CB1::getCB2LocationId( $bookingCode['location_id'] );
		$cb2ItemId      = CB1::getCB2ItemId( $bookingCode['item_id'] );
		$cb2TimeframeId = CB1::getCB2TimeframeId( $bookingCode['timeframe_id'] );
		$date           = $bookingCode['booking_date'];
		$code           = $bookingCode['bookingcode'];

		$bookingCode = new BookingCode(
			$date,
			$cb2ItemId,
			$cb2LocationId,
			$cb2TimeframeId,
			$code
		);

		return BookingCodes::persist( $bookingCode );
	}

	/**
	 * Migrates CB1 user agreement url option to CB2.
	 * Only relevant for legacy user profile.
	 *
	 * @return true
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
		$migratedBookingCodes = Settings::updateOption( 'commonsbooking_options_bookingcodes', 'bookingcodes', $cb1_bookingcodes );

		// update sender e-mail
		$cb1_sender_email = Settings::getOption( 'commons-booking-settings-mail', 'commons-booking_mail_from' );
		$migratedSenderEmail = Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-email', $cb1_sender_email );

		// sender name
		$cb1_sender_name = Settings::getOption( 'commons-booking-settings-mail', 'commons-booking_mail_from_name' );
		$migratedSenderName = Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-name', $cb1_sender_name );
		return ($migratedBookingCodes && $migratedSenderEmail && $migratedSenderName);
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

			$result = wp_set_object_terms( $cb2PostId, $term, $cb1Taxonomy->taxonomy );
			if ($result instanceof \WP_Error) {
				\WP_CLI::log( sprintf( 'Error migrating taxonomy %s for post %s', $cb1Taxonomy->taxonomy, $cb2PostId ) );
				return false;
			}
		}
		return true;
	}

	/**
	 * Runs the migration tasks, needs to be provided with the tasks and their corresponding functions.
	 * Will return the tasks with their updated status. This function will run a maximum of $taskLimit tasks
	 * in order to prevent timeouts.
	 *
	 * When run from the CLI, the task limit will not cause the function to break but will instead commit to the database.
	 *
	 * @param   array  $tasks  - The array of tasks with their current status
	 * @param   array  $taskFunctions -  The corresponding functions for each task
	 * @param   int    $taskLimit - The number of tasks to run in one go
	 *
	 * @return array
	 */
	private static function runTasks(
		array $tasks,
		array $taskFunctions,
		int $taskLimit = 0
	): array {
		if (self::$cliCall){
			global $wpdb;
		}
		$taskIndex = 0;
		self::$elementorActive = is_plugin_active( 'elementor/elementor.php' );
		foreach ( $tasks as $key => &$task ) {
			if (
				$task['complete'] == 0
				&& array_key_exists( 'migrationFunction',
					$taskFunctions[ $key ] )
				&& $taskFunctions[ $key ]['migrationFunction']
			) {
				if ( $taskIndex >= $taskLimit && $taskLimit <> 0 ) {
					if (self::$cliCall) {
						\WP_CLI::log("Committing to database");
						$wpdb->query('COMMIT');
						$taskIndex = 0;
					}
					else {
						break;
					}
				}

				// Multi migration
				if (
					array_key_exists( 'repoFunction', $taskFunctions[ $key ] )
					&& $taskFunctions[ $key ]['repoFunction']
				) {
					$items         = $taskFunctions[ $key ]['repoFunction']();
					$task['count'] = count( $items );

					if (! empty( $taskFunctions[ $key ]['preTask'] ) ) {
						$taskFunctions[ $key ]['preTask']();
					}

					// If there are items to migrate
					if ( count( $items ) ) {
						if (self::$cliCall) {
							\WP_CLI::log(sprintf("Starting migration of %s posts",count($items)));
						}
						for (
							$index = $task['index']; $index < count( $items );
							$index ++
						) {
							if ( $taskIndex ++ >= $taskLimit  && $taskLimit <> 0) {
								if (self::$cliCall) {
									//\WP_CLI::log("Committing to database");
									$wpdb->query('COMMIT');
									$taskIndex = 0;
								}
								else {
									break;
								}
							}

							$item = $items[ $index ];
							if ( ! $taskFunctions[ $key ]['migrationFunction'] ( $item ) ) {
								if (self::$cliCall) {
									$errorMessage = sprintf( "Migrating %s item %s out of %s FAILED.", $key, $index, $task['count'] );
									\WP_CLI::log( $errorMessage );
									self::writeToErrorLog($errorMessage);
								}
								$task['failed'] += 1;
							}
							elseif (self::$cliCall && $taskIndex >= ($taskLimit - 1)) {
								\WP_CLI::log(sprintf("Migrating %s %s/%s successful",$key,$index,$task['count']));
							}
							$task['index'] += 1;
						}
						if ( $task['index'] == count( $items ) ) {
							$task['complete'] = 1;
						}
						// No items for migration found
					} else {
						if ( $taskIndex ++ >= $taskLimit  && $taskLimit <> 0) {
							if (self::$cliCall) {
								\WP_CLI::log("Committing to database");
								$wpdb->query('COMMIT');
								$taskIndex = 0;
							}
							else {
								break;
							}
						}
						$task['complete'] = 1;
					}

					if (! empty( $taskFunctions[ $key ]['postTask'] ) ) {
						$taskFunctions[ $key ]['postTask']();
					}
					// Single Migration
				} else {
					if ( $taskIndex ++ >= $taskLimit && $taskLimit <> 0 ) {
						if (self::$cliCall) {
							\WP_CLI::log("Committing to database");
							$wpdb->query('COMMIT');
							$taskIndex = 0;
						}
						else {
							break;
						}
					}

					if ( ! $taskFunctions[ $key ]['migrationFunction']() ) {
						$task['failed'] += 1;
					}
					$task['index']    += 1;
					$task['complete'] = 1;
				}
			}
		}

		return $tasks;
	}

	/**
	 * Gets the post types and their associated functions as Closures for the migration tasks.
	 * @return array
	 */
	private static function getTaskFunctions(): array {
		$defaultFunctions = [
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
				'preTask'           => 'migrateBookingsPreTask',
				'repoFunction'      => 'getBookings',
				'migrationFunction' => 'migrateBooking',
				'postTask'          => 'migrateBookingsPostTask',
			],
			'bookingCodes' => [
				'repoFunction'      => 'getBookingCodes',
				'migrationFunction' => 'migrateBookingCode',
			],
			'termsUrl'     => [
				'repoFunction'      => FALSE,
				'migrationFunction' => 'migrateUserAgreementUrl',
			],
			'options'      => [
				'repoFunction'      => FALSE,
				'migrationFunction' => 'migrateCB1Options',
			],
			'taxonomies'   => [
				'repoFunction'      => 'getCB1Taxonomies',
				'migrationFunction' => 'migrateTaxonomy',
			],
		];
		//map the default functions to the correct class
		foreach ($defaultFunctions as $key => $defaultFunction){
			if (! empty( $defaultFunction['repoFunction']) ) {
				$defaultFunctions[ $key ]['repoFunction']
					= $defaultFunction['repoFunction']
					= Closure::fromCallable( [
					CB1::class,
					$defaultFunction['repoFunction']
				] );
			}

			if ( ! empty( $defaultFunction['migrationFunction'] ) ) {
				$defaultFunctions[ $key ]['migrationFunction']
					= $defaultFunction['migrationFunction']
					= Closure::fromCallable( [
					self::class,
					$defaultFunction['migrationFunction']
				] );
			}

			if ( ! empty( $defaultFunction['preTask'] ) ) {
				$defaultFunctions[ $key ]['preTask']
					= $defaultFunction['preTask']
					= Closure::fromCallable( [
					self::class,
					$defaultFunction['preTask']
				] );
			}

			if ( ! empty( $defaultFunction['postTask'] ) ) {
				$defaultFunctions[ $key ]['postTask']
					= $defaultFunction['postTask']
					= Closure::fromCallable( [
					self::class,
					$defaultFunction['postTask']
				] );
			}
		}

		return apply_filters( 'commonsbooking_migration_task_functions', $defaultFunctions );
	}

	/**
	 * Gets the default tasks for migration.
	 * @return array[]
	 */
	private static function getDefaultTasks(): array {
		return apply_filters('commonsbooking_migration_tasks', [
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
		]);
	}

	/**
	 * Checks if all tasks are done from the tasks array.
	 * @param   array  $tasks
	 *
	 * @return bool
	 */
	private static function tasksDone(array $tasks): bool {
		foreach ($tasks as $task) {
			if ($task['complete'] == 0) {
				return false;
			}
		}
		return true;
	}

	private static function writeToErrorLog($msg) {
		$folderName = "migrationErrorLogs";
		if (!file_exists($folderName)) {
			mkdir($folderName,0777,true);
		}
		$logFileName = $folderName . '/log_' . date('d-M-Y') . '.log';
		if (!file_exists($logFileName)) {
			file_put_contents($logFileName, '');
		}
		file_put_contents($logFileName, $msg . "\n", FILE_APPEND);
	}

}
