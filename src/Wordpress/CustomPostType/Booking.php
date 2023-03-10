<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Exception\OverlappingException;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Messages\AdminMessage;
use CommonsBooking\Messages\BookingMessage;
use Exception;
use function wp_verify_nonce;

/**
 * Booking
 */
class Booking extends Timeframe {

	/**
	 * @var string
	 */
	public static $postType = 'cb_booking';

	/**
	 * Position in backend menu.
     *
	 * @var int
	 */
	protected $menuPosition = 4;

	public function __construct() {

        // does not trigger when initiated in initHooks
		add_action( 'post_updated', array( $this, 'postUpdated' ), 1, 3 );

    	// Frontend request
		$this->handleFormRequest();
	}

    /**
     * Removes author field in CPT booking
     * Why: we set the autor dynamically based on admin bookins so we don't want the ability to override this setting by user
     *
     * @return void
     */
    public function removeAuthorField() : void {
        remove_post_type_support( self::$postType, 'author' );
    }

    /**
     * Adds and modifies some booking CPT fields in order to make admin boookings
     * compatible to user made bookings via frontend.
     *
     * @param  mixed $post_id
     * @param  mixed $post
     * @param  mixed $update
     * @return void
     */
    public function saveAdminBookingFields( $post_id, $post = null, $update = null ) {
        global $pagenow;

        // we check if its a new created post
        if ( ! empty( $_REQUEST ) && $pagenow === 'post.php' && commonsbooking_isCurrentUserAdmin() ) {

            // set request variables
            if ( isset( $_REQUEST['post_status'] ) ) {
                $post_status = esc_html( $_REQUEST['post_status'] ) ?? false;
            }
            if ( isset( $_REQUEST['repetition-start']['time'] ) ) {
                $start_time = esc_html( $_REQUEST['repetition-start']['time'] ) ?? false;
            }
            if ( isset( $_REQUEST['repetition-end']['time'] ) ) {
                $end_time = esc_html( $_REQUEST['repetition-end']['time'] ) ?? false;
            }
            if ( isset( $_REQUEST['booking_user'] ) ) {
                $booking_user = esc_html( $_REQUEST['booking_user'] ) ?? false;
            }

            if ( $post_status == 'draft' || ! ( $post_status ) ) {
                $post_status = 'unconfirmed';
            }

            $postarr          = array(
				'post_title'  => esc_html__( 'Admin-Booking', 'commonsbooking' ),
                'post_author' => $booking_user,
                'post_status' => $post_status,
                'meta_input'  => [
                    'admin_booking_id' => get_current_user_id(),
                    'start-time'       => $start_time,
                    'end-time'         => $end_time,
                    'type'             => Timeframe::BOOKING_ID,
                    'grid'             => '',
				],
            );
               $postarr['ID'] = $post_id;

            // unhook this function so it doesn't loop infinitely
            remove_action( 'save_post_' . self::$postType, array( $this, 'saveAdminBookingFields' ) );

            // update this post
            wp_update_post( $postarr, true, true );

            // readd the hook
            add_action( 'save_post_' . self::$postType, array( $this, 'saveAdminBookingFields' ) );
        }
    }

	/**
	 * Handles frontend save-Request for timeframe.
     *
	 * @throws Exception
	 */
	public function handleFormRequest() {

        if (
			function_exists( 'wp_verify_nonce' ) &&
			isset( $_REQUEST[ static::getWPNonceId() ] ) &&
			wp_verify_nonce( $_REQUEST[ static::getWPNonceId() ], static::getWPAction() )
		) {
			$itemId         = isset( $_REQUEST['item-id'] ) && $_REQUEST['item-id'] != '' ? sanitize_text_field( $_REQUEST['item-id'] ) : null;
			$locationId     = isset( $_REQUEST['location-id'] ) && $_REQUEST['location-id'] != '' ? sanitize_text_field( $_REQUEST['location-id'] ) : null;
			$comment        = isset( $_REQUEST['comment'] ) && $_REQUEST['comment'] != '' ? sanitize_text_field( $_REQUEST['comment'] ) : null;
            $post_status    = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] != '' ? sanitize_text_field( $_REQUEST['post_status'] ) : null;
            $booking_author = isset( $_REQUEST['author'] ) && $_REQUEST['author'] != '' ? sanitize_text_field( $_REQUEST['author'] ) : get_current_user_id();

 			if ( ! get_post( $itemId ) ) {
				throw new Exception( 'Item does not exist. (' . $itemId . ')' );
			}
			if ( ! get_post( $locationId ) ) {
				throw new Exception( 'Location does not exist. (' . $locationId . ')' );
			}

			$startDate = null;
			if ( isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] ) &&
             $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] != ''
			) {
				$startDate = sanitize_text_field( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] );
			}

			$endDate = null;
			if (
            isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) &&
            $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] != ''
			) {
				$endDate = sanitize_text_field( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] );
			}

			if ( $startDate == null || $endDate == null ) {
				throw new Exception( 'Start- and/or enddate missing.' );
			}

			// Validate booking -> check if there are no existing bookings in timerange.
			if (
            $existingBookings =
                \CommonsBooking\Repository\Booking::getByTimerange(
                    $startDate,
                    $endDate,
                    $locationId,
                    $itemId,
                    [],
                    [ 'confirmed' ]
                )
			) {
				if ( count( $existingBookings ) > 0 ) {
					$requestedPostname = array_key_exists( 'cb_booking', $_REQUEST ) ? $_REQUEST['cb_booking'] : '';

					// checks if it's an edit, but ignores exact start/end time
					$isEdit = count( $existingBookings ) === 1 &&
						array_values( $existingBookings )[0]->getPost()->post_name === $requestedPostname &&
						array_values( $existingBookings )[0]->getPost()->post_author == get_current_user_id();

					if ( ( ! $isEdit || count( $existingBookings ) > 1 ) && $post_status != 'canceled' ) {
						throw new Exception( 'There is already a booking in this timerange.' );
					}
				}
			}

			/** @var \CommonsBooking\Model\Booking $booking */
			$booking = \CommonsBooking\Repository\Booking::getByDate(
                $startDate,
                $endDate,
                $locationId,
                $itemId
            );

            $postarr = array(
				'type'        => sanitize_text_field( $_REQUEST['type'] ),
				'post_status' => sanitize_text_field( $_REQUEST['post_status'] ),
				'post_type'   => self::getPostType(),
				'post_title'  => esc_html__( 'Booking', 'commonsbooking' ),
				'post_author' => $booking_author,
				'meta_input'  => [
					'comment' => $comment,
				],
            );

            // New booking
            if ( empty( $booking ) ) {
                $postarr['post_name']  = Helper::generateRandomString();
                $postarr['meta_input'] = [
					\CommonsBooking\Model\Timeframe::META_LOCATION_ID => $locationId,
					\CommonsBooking\Model\Timeframe::META_ITEM_ID => $itemId,
					\CommonsBooking\Model\Timeframe::REPETITION_START => $startDate,
					\CommonsBooking\Model\Timeframe::REPETITION_END => $endDate,
					'type' => Timeframe::BOOKING_ID,
                ];

                $postId = wp_insert_post( $postarr, true );

                // Existing booking
            } else {
                $postarr['ID'] = $booking->ID;
                $postId        = wp_update_post( $postarr );

            }

            $this->saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate );

            $bookingModel = new \CommonsBooking\Model\Booking( $postId );
            // we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
            $bookingModel->assignBookableTimeframeFields();

            // get slug as parameter
            $post_slug = get_post( $postId )->post_name;

            wp_redirect( add_query_arg( self::getPostType(), $post_slug, home_url() ) );
            exit;
		}
	}

	/**
	 * Multi grid size
	 * We need to save the grid size for timeframes with full slot grid.
	 *
	 * @param $postId
	 * @param $locationId
	 * @param $itemId
	 * @param $startDate
	 * @param $endDate
	 */
	private function saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate ): void {
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && $endTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE,
				$endTimeFrame->getGridSize()
			);
		}
	}

	/**
	 * Check if booking overlaps before its been saved.
	 *
	 * @param $postId
	 * @param $data
	 *
	 * @return void
	 */
	public static function preSavePost( $postId, $data ) {
        global $pagenow;

		if ( 
                static::$postType !== $data['post_type'] ||
                $pagenow === 'post-new.php' ||
                ! isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::META_ITEM_ID ] ) ||
                ! isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::META_LOCATION_ID ] ) ||
                ! isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] ) ||
                ! isset( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) 
            ) {
			return;
		}

        // prepare needed params
            $itemId          = commonsbooking_sanitizeArrayorString( $_REQUEST[ \CommonsBooking\Model\Timeframe::META_ITEM_ID ] );
            $locationId      = commonsbooking_sanitizeArrayorString( $_REQUEST[ \CommonsBooking\Model\Timeframe::META_LOCATION_ID ] );
            $repetitionStart = commonsbooking_sanitizeArrayorString( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_START ] );
            $repetitionEnd   = commonsbooking_sanitizeArrayorString( $_REQUEST[ \CommonsBooking\Model\Timeframe::REPETITION_END ] );

        if ( is_array( $repetitionStart ) ) {
            $repetitionStart = strtotime( $repetitionStart['date'] . ' ' . $repetitionStart['time'] );
        } else {
            $repetitionStart = intval( $repetitionStart );
        }

        if ( is_array( $repetitionEnd ) ) {
            $repetitionEnd = strtotime( $repetitionEnd['date'] . ' ' . $repetitionEnd['time'] );
        } else {
            $repetitionEnd = intval( $repetitionEnd );
        }

        $overlappingBookings = \CommonsBooking\Repository\Booking::getExistingBookings (
            $itemId,
            $locationId,
            $repetitionStart,
            $repetitionEnd,
            $postId
        );

        if ( $overlappingBookings && count( $overlappingBookings ) >= 1 ) {

            foreach ( $overlappingBookings as $overlappingBooking ) {
                    $overlappingBookingLinks[] = $overlappingBooking->getFormattedEditLink();
            }

            $formattedOverlappingLinks = implode( '<br>', $overlappingBookingLinks );

			set_transient(
                 'commonsbooking_booking_validation_failed_' . $postId,
                sprintf(
                    '<div style="background-color:#e6aeae; padding:20px; border:5px solid red"><h2>' . commonsbooking_sanitizeHTML(
                        __(
                            'Warning: There are one ore more overlapping bookings within the choosen timerange',
                            'commonsbooking'
                        )
                    ) . '</h2><p>' .

                    commonsbooking_sanitizeHTML(
                        __(
                            'Please adjust the startdate or enddate.<br>Changes on this booking have not been saved.<br>
                                <strong>Affected Bookings:</strong><br>
                                %1$s',
                            'commonsbooking'
                        ) . '</p></div>'
                    ),
                    commonsbooking_sanitizeHTML( $formattedOverlappingLinks )
                ),
                120
            );
             $targetUrl = esc_url_raw( $_REQUEST['_wp_http_referer'] );
             header( 'Location: ' . $targetUrl );
             exit();
		} else {
            delete_transient( 'commonsbooking_booking_validation_failed_' . $postId );
        }
	}




	/**
	 * @inheritDoc
	 */
	public static function getView() {
		return new \CommonsBooking\View\Booking();
	}

	public function initListView() {
		if ( array_key_exists( 'post_type', $_GET ) && static::$postType !== $_GET['post_type'] ) {
			return;
		}

		// List settings
		$this->removeListDateColumn();

		// Backend listing columns.
		$this->listColumns = [
			'timeframe-author' => esc_html__( 'User', 'commonsbooking' ),
			'item-id'          => esc_html__( 'Item', 'commonsbooking' ),
			'location-id'      => esc_html__( 'Location', 'commonsbooking' ),
			'post_date'        => esc_html__( 'Bookingdate', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_START => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Timeframe::REPETITION_END => esc_html__( 'End Date', 'commonsbooking' ),
			'post_status'      => esc_html__( 'Booking Status', 'commonsbooking' ),
			'comment'          => esc_html__( 'Comment', 'commonsbooking' ),
		];

		parent::initListView(); // TODO: Change the autogenerated stub
	}

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		add_action( 'pre_post_update', array( $this, 'preSavePost' ), 1, 2 );

        // we need to add some additional fields and modify the autor if admin booking is made
        add_action( 'save_post_' . self::$postType, array( $this, 'saveAdminBookingFields' ), 10 );

		// Set Tepmlates
		add_filter( 'the_content', array( $this, 'getTemplate' ) );

        // remove author metabox because we set author in the booking user field
        add_action( 'init', array( $this, 'removeAuthorField' ), 99 );

		// Listing of bookings for current user
		add_shortcode( 'cb_bookings', array( \CommonsBooking\View\Booking::class, 'shortcode' ) );

		// Add type filter to backend list view
		// add_action( 'restrict_manage_posts', array( static::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminDateFilter' ) );
		add_action( 'restrict_manage_posts', array( static::class, 'addAdminStatusFilter' ) );
		add_action( 'pre_get_posts', array( static::class, 'filterAdminList' ) );

		// show permanent admin notice
		add_action( 'admin_notices', array( $this, 'BookingsAdminListNotice' ) );
        add_action( 'edit_form_top', array( $this, 'displayOverlappingBookingNotice' ), 99 );
	}

	/**
	 * loads template according and returns content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function getTemplate( $content ) {
		$cb_content = '';
		if ( is_singular( self::getPostType() ) && is_main_query() ) {
			ob_start();
			global $post;

			if ( commonsbooking_isCurrentUserAllowedToEdit( $post ) ) {
				commonsbooking_get_template_part( 'booking', 'single' );
			} else {
				commonsbooking_get_template_part( 'booking', 'single-notallowed' );
			}
			$cb_content = ob_get_clean();
		} // if archive...

		return $content . $cb_content;
	}

	/**
	 * Is triggered when post gets updated. Currently used to send notifications regarding bookings.
	 *
	 * @param $post_ID
	 * @param $post_after
	 * @param $post_before
	 */
	public function postUpdated( $post_ID, $post_after, $post_before ) {

        if ( ! $this->hasRunBefore( __FUNCTION__ ) ) {
			$isBooking = get_post_meta( $post_ID, 'type', true ) == Timeframe::BOOKING_ID;
    		if ( $isBooking ) {

    				// Trigger Mail, only send mail if status has changed
				if ( $post_before->post_status != $post_after->post_status and
				     ! (
					     $post_before->post_status === 'unconfirmed' and
					     $post_after->post_status === 'canceled'
				     )
				) {
					if ( $post_after->post_status == 'canceled' ) {
						$booking = new \CommonsBooking\Model\Booking( $post_ID );
						$booking->cancel();
					} else {
						$booking_msg = new BookingMessage( $post_ID, $post_after->post_status );
						$booking_msg->triggerMail();
					}
				}
            }
		}
	}

	/**
	 * Returns CPT arguments.
     *
	 * @return array
	 */
	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Bookings', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Booking', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new booking', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit booking', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new booking', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show booking', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show bookings', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search bookings', 'commonsbooking' ),
			'not_found'             => esc_html__( 'Timeframes not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No bookings found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent bookings:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All bookings', 'commonsbooking' ),
			'archives'              => esc_html__( 'Timeframe archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Timeframe attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to booking', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to booking', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Timeframe image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set booking image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove booking image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as booking image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Timeframes', 'commonsbooking' ),
		);

		// args for the new post_type
		return array(
			'labels'              => $labels,

			// Sichtbarkeit des Post Types
			'public'              => false,

			// Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'             => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'        => false,

			// Position im Menu
			'menu_position'       => 2,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar'   => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus'   => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die standart Werte post und page in form eines Strings gesetzt werden
			'capability_type'     => array( self::$postType, self::$postType . 's' ),

			'capabilities'        => array(
				'create_posts' => true,
			),

			'map_meta_cap'        => true,

			// Soll es im Frontend abrufbar sein?
			'publicly_queryable'  => true,

			// Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
			'exclude_from_search' => true,

			// Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
			'supports'            => array( 'title', 'author', 'revisions' ),

			// Soll der Post Type Archiv-Seiten haben?
			'has_archive'         => false,

			// Soll man den Post Type exportieren können?
			'can_export'          => false,

			// Slug unseres Post Types für die redirects
			// dieser Wert wird später in der URL stehen
			'rewrite'             => array( 'slug' => self::getPostType() ),

			'show_in_rest'        => true,
		);
	}

	/**
	 * Adds data to custom columns
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function setCustomColumnsData( $column, $post_id ) {
        global $pagenow;

        if ( $pagenow !== 'edit.php' || empty( esc_html( $_GET['post_type'] ) ) || esc_html( $_GET['post_type'] ) !== $this::$postType ) {
            return;
        }

		// we alter the  author column data and link the username to the user profile
		if ( $column == 'timeframe-author' ) {
			$post           = get_post( $post_id );
			$timeframe_user = get_user_by( 'id', $post->post_author );
			echo '<a href="' . get_edit_user_link( $timeframe_user->ID ) . '">' . commonsbooking_sanitizeHTML( $timeframe_user->user_login ) . '</a>';
		}

		if ( $value = get_post_meta( $post_id, $column, true ) ) {
			switch ( $column ) {
				case 'location-id':
				case 'item-id':
					if ( $post = get_post( $value ) ) {
						if ( get_post_type( $post ) == Location::getPostType() ||
						     get_post_type( $post ) == Item::getPostType()
						) {
							echo commonsbooking_sanitizeHTML( $post->post_title );
							break;
						}
					}
					echo '-';
					break;
				case 'type':
					$output = '-';

					foreach ( $this->getCustomFields() as $customField ) {
						if ( $customField['id'] == 'type' ) {
							foreach ( $customField['options'] as $key => $label ) {
								if ( $value == $key ) {
									$output = $label;
								}
							}
						}
					}
					echo commonsbooking_sanitizeHTML( $output );
					break;
				case \CommonsBooking\Model\Timeframe::REPETITION_START:
				case \CommonsBooking\Model\Timeframe::REPETITION_END:
					echo date( 'd.m.Y H:i', $value );
					break;
				default:
					echo commonsbooking_sanitizeHTML( $value );
					break;
			}
		} else {
			$bookingColumns = [
				// removed the following colums to fix an issue where booking status was not
				// shown in booking list when added via backend editor.
				// 'post_date',
				// 'post_status',
			];

			if (
				property_exists( $post = get_post( $post_id ), $column ) && (
					! in_array( $column, $bookingColumns ) ||
					get_post_meta( $post_id, 'type', true ) == Timeframe::BOOKING_ID
				)
			) {
				echo commonsbooking_sanitizeHTML( $post->{$column} );
			}
		}
	}

	/**
	 * Registers metaboxes for cpt.
	 */
	public function registerMetabox() {
		$cmb = new_cmb2_box(
			[
				'id'           => static::getPostType() . '-custom-fields',
				'title'        => esc_html__( 'Booking', 'commonsbooking' ),
				'object_types' => array( static::getPostType() ),
			]
		);

		foreach ( $this->getCustomFields() as $customField ) {
			$cmb->add_field( $customField );
		}
	}

	/**
	 * Returns custom (meta) fields for Costum Post Type Timeframe.
     *
	 * @return array
	 */
	protected function getCustomFields() {
		// We need static types, because german month names dont't work for datepicker
		$dateFormat = 'd/m/Y';
		if ( strpos( get_locale(), 'de_' ) !== false ) {
			$dateFormat = 'd.m.Y';
		}

		if ( strpos( get_locale(), 'en_' ) !== false ) {
			$dateFormat = 'm/d/Y';
		}

        // Generate user list for admin bookings
		if ( commonsbooking_isCurrentUserAdmin() || commonsbooking_isCurrentUserCBManager() ) {
			$users       = get_users();
			$userOptions = [];
			foreach ( $users as $user ) {
				$userOptions[ $user->ID ] = $user->get( 'user_nicename' ) . ' (' . $user->first_name . ' ' . $user->last_name . ')';
			}
		}

		return array(
			array(
				'name' => esc_html__( 'Edit booking', 'commonsbooking' ),
				'desc' => '<div class="notice notice-error" style="background-color:#efe05c"><p>' . commonsbooking_sanitizeHTML(
                    __(
                        '<h1>Notice</h1><p>In this view, you as an admin can create or modify existing bookings. Please use it with caution. <br>
				<ul>
                    <li>Click on the <strong>preview button on the right panel</strong> to view more booking details and to cancel the booking via the cancel button.</li>
                    <li>The booking is initially <strong>saved as <i>unconfirmed</i></strong>. Please change the booking status (confirmed, unconfirmed, canceled) using the status dropdown in publish panel.</li>
                    <li>When the booking is saved with status <i>confirmed</i>, the booking user will receive a booking confirmation mail</li>
                    <li>Unconfirmed bookings will be <strong>deleted automatically after a few minutes</strong></li>
                </ul>
				<strong>Please note</strong>: There are only basic checks agains existing bookings. Please check if there are no conflicting bookings.
                </p> 
				',
                        'commonsbooking'
                    ) . '</p></div>'
                ),
				'id'   => 'title-booking-hint',
				'type' => 'title',
			),
			array(
				'name' => esc_html__( 'Comment', 'commonsbooking' ),
				'desc' => esc_html__( 'This comment is internal for timeframes like bookable, repair, holiday. If timeframe is a booking this comment can be set by users during the booking confirmation process.', 'commonsbooking' ),
				'id'   => 'comment',
				'type' => 'textarea_small',
			),
			array(
				'name'    => esc_html__( 'Location', 'commonsbooking' ),
				'id'      => 'location-id',
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'    => esc_html__( 'Item', 'commonsbooking' ),
				'id'      => 'item-id',
				'type'    => 'select',
				'options' => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => '<br>' . esc_html__( 'Set the start date. You must set the time to 00:00 if you want to book the full day ', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_START,
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
                'default'     => '00:00',
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				),
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => '<br>' . esc_html__( 'Set the end date. You must set time to 23:59 if you want to book the full day', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Timeframe::REPETITION_END,
				'type'        => 'text_datetime_timestamp',
				'time_format' => get_option( 'time_format' ),
				'date_format' => $dateFormat,
                'default'     => '23:59',
				'attributes'  => array(
					'data-timepicker' => wp_json_encode(
						array(
							'timeFormat' => 'HH:mm',
							'stepMinute' => 1,
						)
					),
				),
			),
			array(
				'name' => esc_html__( 'Booking Code', 'commonsbooking' ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'bookingcode',
				'type' => 'text',
			),
            array(
				'name'             => esc_html__( 'Booking User', 'commonsbooking' ),
				'id'               => 'booking_user',
				'type'             => 'pw_select',
                'show_option_none' => true,
                'options'          => $userOptions,
                'default'          => array( self::class, 'getFrontendBookingAuthor' ),
                'desc'             => commonsbooking_sanitizeHTML(
                    __(
                        'Here you must select the user for whom the booking is made.<br>
                        If the booking was was made by a user via frontend booking process, the user will be shown in this field.
                        <br><strong>Notice:</strong>The user will receive a booking confirmation as soon as the booking has been saved with the status confirmed.',
                        'commonsbooking'
                    )
                ),
			),
            array(
				'name'             => esc_html__( 'Admin Booking User', 'commonsbooking' ),
				'id'               => 'admin_booking_id',
				'type'             => 'select',
                'default'          => get_current_user_id(),
                'show_option_none' => true,
                'options'          => $userOptions,
                'attributes'       => array(
                    'readonly' => true,
                ),
                'desc'             => commonsbooking_sanitizeHTML(
                    __(
                        'This is the admin user who created or modified this booking.',
                        'commonsbooking'
                    )
                ),
			),
			array(
				'type'    => 'hidden',
				'id'      => 'prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) ),
			),
		);
	}

	/**
	 * Display permanent Admin notice on admin edit listing page for post type booking
	 *
	 * @return void
	 */
	public function BookingsAdminListNotice() {
		global $pagenow;

		$notice = commonsbooking_sanitizeHTML(
            __(
                'Bookings should be created via frontend booking calendar. <br>
		As an admin you can create bookings via this admin interface. Please be aware that admin bookings are not validated
		and checked. Use this function with care.<br>
		Click on preview to show booking details in frontend<br>
		To search and filter bookings please integrate the frontend booking list via shortcode. 
		See here <a target="_blank" href="https://commonsbooking.org/?p=1433">How to display the booking list</a>',
                'commonsbooking'
            )
        );

		if ( ( $pagenow == 'edit.php' ) && isset( $_GET['post_type'] ) ) {
			if ( sanitize_text_field( $_GET['post_type'] ) == self::getPostType() ) {
				echo '<div class="notice notice-info"><p>' . commonsbooking_sanitizeHTML( $notice ) . '</p></div>';
			}
		}
	}

    
    /**
     * Displays a permanent admin-notice if booking overlaps
     *
     * @return void
     */
    public function displayOverlappingBookingNotice($post) {
      
        if (get_transient( 'commonsbooking_booking_validation_failed_' . $post->ID )) {
           echo get_transient( 'commonsbooking_booking_validation_failed_' . $post->ID, 'warning');
        }
    }

    /**
     * Returns the booking author if booking exists, otherwise returns current user
     * This is helper function
     *
     * @return void
     */
    public static function getFrontendBookingAuthor() {
        global $post;
        if ( $post ) {
            $authorID = $post->post_author;
        } else {
            $authorID = get_current_user_id();
        }
        return $authorID;
    }
}
