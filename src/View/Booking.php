<?php

namespace CommonsBooking\View;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;
use Exception;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Service\iCalendar;

/**
 * Static functionality to deal with legacy wp actions in includes.
 * Also creates ical feed data.
 */
class Booking extends View {

	/**
	 * Returns template data for frontend.
	 *
	 * @since 2.10.5 wp_json_encode does not contain any bitmap option (before it was true => inferred to JSON_HEX_TAG)
	 *        which is not needed, since it's not embedded into html.
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function getTemplateData(): void {
		header( 'Content-Type: application/json' );
		echo wp_json_encode( self::getBookingListData() );
		wp_die(); // All ajax handlers die when finished
	}

	/**
	 * @param int           $postsPerPage
	 * @param \WP_User|null $user
	 *
	 * @return array|false|mixed
	 * @throws Exception
	 */
	public static function getBookingListData( int $postsPerPage = 6, \WP_User $user = null ) {

		// sets selected user to current user when no specific user is passed
		if ( $user == null ) {
			$user = wp_get_current_user();
		}

		if ( array_key_exists( 'posts_per_page', $_POST ) ) {
			$postsPerPage = sanitize_text_field( $_POST['posts_per_page'] );
		}

		$page = 1;
		if ( array_key_exists( 'page', $_POST ) ) {
			$page = sanitize_text_field( $_POST['page'] );
		}

		$search = false;
		if ( array_key_exists( 'search', $_POST ) ) {
			$search = sanitize_text_field( $_POST['search'] );
		}

		$sort = 'startDate';
		if ( array_key_exists( 'sort', $_POST ) ) {
			$sort = sanitize_text_field( $_POST['sort'] );
		}

		$order = 'asc';
		if ( array_key_exists( 'order', $_POST ) ) {
			$order = sanitize_text_field( $_POST['order'] );
		}

		// Upon initial load, start date is not configured
		$startDateDefined = false;
		if ( array_key_exists( 'startDate', $_POST ) ) {
			$startDateDefined = true;
		}

		$filters = [
			'location'  => false,
			'item'      => false,
			'user'      => false,
			'startDate' => time(),
			'endDate'   => false,
			'status'    => false,
		];

		foreach ( $filters as $key => $value ) {
			if ( array_key_exists( $key, $_POST ) ) {
				$filters[ $key ] = sanitize_text_field( $_POST[ $key ] );
			}
		}

		$customId = md5(
			__CLASS__ . __FUNCTION__ .
			serialize( $_POST ) .
			serialize( is_user_logged_in() ) .
			serialize( $user->ID )
		);

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$bookingDataArray             = [];
			$bookingDataArray['page']     = $page;
			$bookingDataArray['per_page'] = $postsPerPage;
			$bookingDataArray['filters']  = [
				'user'     => [],
				'item'     => [],
				'location' => [],
				'status'   => [],
			];
			$bookingDataArray['data']     = [];

			$posts = \CommonsBooking\Repository\Booking::getForUser(
				$user,
				true,
				$filters['startDate'] ?: null
			);

			if ( ! $posts ) {
				// Because upon initial load the form stays empty when we just have bookings in the past
				// With an empty form, the user can't change the start date so we look for bookings in the past
				if ( ! $startDateDefined ) {
					// Don't fetch all bookings so that admins are not overwhelmed with all bookings of all time
					for ( $year = 1; $year <= 3; $year++ ) {
						$currentTime = strtotime( '-' . $year . ' year' );
						$posts       = \CommonsBooking\Repository\Booking::getForUser(
							$user,
							true,
							$currentTime
						);
						if ( $posts ) {
							$filters['startDate'] = $currentTime;
							break;
						}
					}
					if ( ! $posts ) {
						return false;
					}
				} else {
					return false;
				}
			}

			// Prepare Templatedata and remove invalid posts
			/** @var \CommonsBooking\Model\Booking $booking */
			foreach ( $posts as $booking ) {

				// Get user infos
				$userInfo = get_userdata( $booking->post_author );

				// Decide which edit link to use
				$editLink = get_permalink( $booking->ID );

				$actions = '<a class="cb-button small" href="' . $editLink . '">' .
							commonsbooking_sanitizeHTML( __( 'Details', 'commonsbooking' ) ) .
							'</a>';

				$menuitems = '';

				if ( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'feed_enabled' ) == 'on' ) {
					$menuitems .= '<div id="icallink_text" title="' . commonsbooking_sanitizeHTML( __( 'Use this link to import the data into your own calendar. Usually you just need to provide the URL as an external source and the calendar will figure it out. Do not try to download this file.', 'commonsbooking' ) ) . '">' .
										commonsbooking_sanitizeHTML( __( 'iCalendar Link:', 'commonsbooking' ) ) .
									'</div>' .
									'<input type="text" id="icallink" value="' . iCalendar::getCurrentUserCalendarLink() . '" readonly>';
				}

				$item          = $booking->getItem();
				$itemTitle     = $item ? $item->post_title : commonsbooking_sanitizeHTML( __( 'Not available', 'commonsbooking' ) );
				$location      = $booking->getLocation();
				$locationTitle = $location ? $booking->getLocation()->post_title : commonsbooking_sanitizeHTML( __( 'Not available', 'commonsbooking' ) );

				// Prepare row data
				// FIXME This untyped structure is exposed via the filter commonsbooking_booking_filter below, but the set of keys of the assoc array must not be changed. This is not ideal and should be either replace by a dedicated object type or removed entirely.
				// If not, why not expose this as own type?
				$rowData = [
					'postID'             => $booking->ID,
					'startDate'          => $booking->getStartDate(),
					'endDate'            => $booking->getEndDate(),
					'startDateFormatted' => date( 'd.m.Y H:i', $booking->getStartDate() ),
					'endDateFormatted'   => date( 'd.m.Y H:i', $booking->getEndDate() ),
					'item'               => $itemTitle,
					'location'           => $locationTitle,
					'locationAddr'       => $location ? $location->formattedAddressOneLine() : '',
					'locationLat'        => $location ? $location->getMeta( 'geo_latitude' ) : 0,
					'locationLong'       => $location ? $location->getMeta( 'geo_longitude' ) : 0,
					'bookingDate'        => date( 'd.m.Y H:i', strtotime( $booking->post_date ) ),
					'user'               => $userInfo->user_login,
					'status'             => $booking->post_status,
					'fullDay'            => $booking->getMeta( 'full-day' ),
					'calendarLink'       => $item && $location ? add_query_arg( 'cb-item', $item->ID, get_permalink( $location->ID ) ) : '',
					'content'            => [
						'user'   => [
							'label' => commonsbooking_sanitizeHTML( __( 'User', 'commonsbooking' ) ),
							'value' => '<a href="' . get_author_posts_url( $booking->post_author ) . '">' . $userInfo->first_name . ' ' . $userInfo->last_name . ' (' . $userInfo->user_login . ') </a>',
						],
						'status' => [
							'label' => commonsbooking_sanitizeHTML( __( 'Status', 'commonsbooking' ) ),
							'value' => commonsbooking_sanitizeHTML( __( $booking->post_status, 'commonsbooking' ) ),
						],
					],
				];

				// Add booking code if there is one
				if ( $booking->getBookingCode() ) {
					$rowData['bookingCode'] = [
						'label' => commonsbooking_sanitizeHTML( __( 'Code', 'commonsbooking' ) ),
						'value' => $booking->getBookingCode(),
					];
				}

				$continue = false;
				foreach ( $filters as $key => $value ) {
					if ( $value ) {
						if ( ! in_array( $key, [ 'startDate', 'endDate' ] ) ) {
							if ( $rowData[ $key ] != $value ) {
								$continue = true;
							}
						} elseif (
								( $key == 'startDate' && $value > intval( $booking->getEndDate() ) ) ||
								( $key == 'endDate' && $value < intval( $booking->getStartDate() ) )
							) {
								$continue = true;
						}
					}
				}
				if ( $continue ) {
					continue;
				}

				foreach ( array_keys( $bookingDataArray['filters'] ) as $key ) {
					$bookingDataArray['filters'][ $key ][] = $rowData[ $key ];
				}

				// If search term was submitted, filter for it.
				if ( ! $search || count( preg_grep( '/.*' . $search . '.*/i', $rowData ) ) > 0 ) {
					$rowData['actions'] = $actions;

					/**
					 * Default assoc array of row data and the booking object, which gets added to the booking list data result.
					 *
					 * NOTE: Upon using this filter hook, the schema of associative array keys needs to be adhered to in order to not break the booking list.
					 *
					 * @since 2.7.3
					 *
					 * @param array                         $rowData assoc array of one row booking data
					 * @param \CommonsBooking\Model\Booking $booking booking model of one row booking data
					 */
					$bookingDataArray['data'][] = apply_filters( 'commonsbooking_booking_filter', $rowData, $booking );
				}
			}

			$bookingDataArray['total']       = 0;
			$bookingDataArray['total_pages'] = 0;

			if ( ! empty( $menuitems ) ) {
				$bookingDataArray['menu'] = ' <div class="cb-dropdown" style="float:right;"> <div id="cb-bookingdropbtn" class="cb-dropbtn"></div> <div class="cb-dropdown-content">' . $menuitems . '</div> </div>';
			}

			// TODO remove null values from $bookingDataArray['data'] to not break pagination logic
			if ( count( $bookingDataArray['data'] ) ) {
				$totalCount                      = count( $bookingDataArray['data'] );
				$bookingDataArray['total']       = $totalCount;
				$bookingDataArray['total_pages'] = ceil( $totalCount / $postsPerPage );

				foreach ( $bookingDataArray['filters'] as &$filtervalues ) {
					$filtervalues = array_unique( $filtervalues );
					sort( $filtervalues );
				}

				// Init function to pass sort and order param to sorting callback
				$sorter = function ( $sort, $order ) {
					return function ( $a, $b ) use ( $sort, $order ) {
						if ( $order == 'asc' ) {
							return strcasecmp( $a[ $sort ], $b[ $sort ] );
						} else {
							return strcasecmp( $b[ $sort ], $a[ $sort ] );
						}
					};
				};

				// Sorting
				uasort( $bookingDataArray['data'], $sorter( $sort, $order ) );

				// Apply pagination...
				$index       = 0;
				$pageCounter = 0;

				$offset = ( $page - 1 ) * $postsPerPage;

				foreach ( $bookingDataArray['data'] as $key => $post ) {
					if ( $offset > $index++ ) {
						unset( $bookingDataArray['data'][ $key ] );
						continue;
					}
					if ( $postsPerPage && $postsPerPage <= $pageCounter++ ) {
						unset( $bookingDataArray['data'][ $key ] );
					}
				}
				$bookingDataArray['data'] = array_values( $bookingDataArray['data'] );
			}

			Plugin::setCacheItem(
				$bookingDataArray,
				Wordpress::getTags( $posts ),
				$customId
			);

			return $bookingDataArray;
		}
	}

	/**
	 * The function that processes the AJAX request to get a corresponding location for an item.
	 *
	 * Test @see \CommonsBooking\Tests\View\BookingTest_AJAX_TEST::testGetLocationForItem_AJAX()
	 *
	 * @return void
	 */
	public static function getLocationForItem_AJAX() {
		// verify nonce
		check_ajax_referer( 'cb_get_bookable_location', 'nonce' );

		$postData = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
		$postData = commonsbooking_sanitizeArrayorString( $postData );
		$itemID   = intval( $postData['itemID'] );

		try {
			$itemModel = new \CommonsBooking\Model\Item( $itemID );
			$location  = \CommonsBooking\Repository\Location::getByItem( $itemID, true );
			// pick the first location, no matter what
			$location  = reset( $location );
			if( !$location ) {
				throw new Exception( 'No location found for this item.' );
			}

			$timeframe = Timeframe::getBookable(
				[ $location->ID ],
				[ $itemID ],
				null,
				true
			);
			/** @var \CommonsBooking\Model\Timeframe $timeframe */
			$timeframe = reset( $timeframe );

			wp_send_json(
				array(
					'success'     => true,
					'locationID'  => $location->ID,
					'fullDay'     => $timeframe->isFullDay(),
				)
			);
		} catch ( Exception $e ) {
			// This won't be displayed anywhere
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * The function that processes the AJAX request to get a valid booking code for
	 *
	 * Test @see \CommonsBooking\Tests\View\BookingTest_AJAX_TEST::testGetBookingCode_AJAX()
	 *
	 * @return void
	 */
	public static function getBookingCode_AJAX() {
		// verify nonce
		check_ajax_referer( 'cb_get_booking_code', 'nonce' );

		$postData   = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
		$postData   = commonsbooking_sanitizeArrayorString( $postData );
		$itemID     = intval( $postData['itemID'] );
		$locationID = intval( $postData['locationID'] );
		$startDate  = $postData['startDate'];

		$bookingCode = '';

		// get the corresponding bookable timeframe if this booking was made
		try {
			$timeframe = Timeframe::getBookable(
				[ $locationID ],
				[ $itemID ],
				date( CB::getInternalDateFormat(), strtotime( $startDate ) ),
				true
			);
			if ( ! $timeframe || count( $timeframe ) != 1 ) {
				// this is immediately caught again
				throw new Exception( 'No bookable timeframe found for this booking.' );
			}
			$timeframe = reset( $timeframe );

			// get the booking code
			$bookingCode = \CommonsBooking\Repository\BookingCodes::getCode( $timeframe, $itemID, $locationID, date( 'Y-m-d', strtotime( $startDate ) ) );
			if ( ! $bookingCode ) {
				// this is immediately caught again
				throw new Exception( 'No booking code found for this booking.' );
			}
			$bookingCode = $bookingCode->getCode();
		} catch ( Exception $e ) {
			// This won't be displayed anywhere
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
		wp_send_json(
			array(
				'success' => true,
				'bookingCode'                 => $bookingCode,
			)
		);
	}

	/**
	 * Bookings shortcode
	 *
	 * A list of items with timeframes.
	 *
	 * @param $atts
	 *
	 * @return false|string
	 * @throws Exception
	 */
	public static function shortcode( $atts ) {
		global $templateData;
		$templateData = [];
		$templateData = self::getBookingListData();

		ob_start();
		commonsbooking_get_template_part( 'shortcode', 'bookings', true, false, false );

		return ob_get_clean();
	}

	/**
	 * Gets the error for frontend notice. We use transients to pass the error message.
	 * It is ensured that only the user where the error occurred can see the error message.
	 *
	 * @since 2.9.0 returns string instead of using printf
	 *
	 * @return string
	 */
	public static function getError(): string {
		$errorTypes = [
			\CommonsBooking\Wordpress\CustomPostType\Booking::ERROR_TYPE . '-' . get_current_user_id(),
		];
		$message    = '';

		foreach ( $errorTypes as $errorType ) {
			if ( $error = get_transient( $errorType ) ) {
				$class   = 'cb-notice error';
				$message = sprintf(
					'<div class="%1$s"><p>%2$s</p></div>',
					esc_attr( $class ),
					nl2br( commonsbooking_sanitizeHTML( $error ) )
				);
				delete_transient( $errorType );
			}
		}
		if ( $message ) {
			return '<div class="cb-wrapper">' . $message . '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Will get the booking list as an iCalendar string for the specified user.
	 * This means, that this will include all the bookings the user has access to (e.g. bookings of his own items) and
	 * bookings for items/locations that CB-Managers have access to.
	 *
	 * This only includes confirmed bookings in the future.
	 *
	 * @param $user
	 *
	 * @return string|false
	 * @throws Exception
	 */
	public static function getBookingListiCal( $user = null ) {
		$eventTitle_unparsed       = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title' );
		$eventDescription_unparsed = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_desc' );

		$user = get_user_by( 'id', $user );

		if ( ! $user ) {
			return false;
		}

		$bookingList = self::getBookingListData( 999, $user );

		// returns false when booking list is empty
		if ( ! $bookingList ) {
			return false;
		}

		$calendar = new iCalendar();

		foreach ( $bookingList['data'] as $booking ) {
			$booking_model = new \CommonsBooking\Model\Booking( $booking['postID'] );
			if ( ! $booking_model->isConfirmed() ) {
				continue;
			}
			$template_objects = [
				'booking'  => $booking_model,
				'item'     => $booking_model->getItem(),
				'location' => $booking_model->getLocation(),
				'user'     => $booking_model->getUserData(),
			];

			$eventTitle       = commonsbooking_sanitizeHTML( commonsbooking_parse_template( $eventTitle_unparsed, $template_objects ) );
			$eventDescription = commonsbooking_sanitizeHTML( strip_tags( commonsbooking_parse_template( $eventDescription_unparsed, $template_objects ) ) );

			$calendar->addBookingEvent( $booking_model, $eventTitle, $eventDescription );
		}

		return $calendar->getCalendarData();
	}

	/**
	 * Callback function to render the button that submits the backend booking.
	 *
	 * @param $field_args
	 * @param $field
	 */
	public static function renderSubmitButton( $field_args, $field ) {
		$id     = $field->args( 'id' );
		$label  = $field->args( 'name' );
		$desc   = $field->args( 'desc' );
		$postId = $field->object_id();

		// don't render button if we are editing an existing booking
		$postStatus = get_post( $postId )->post_status;
		if ( $postId && ! ( $postStatus == 'auto-draft' || $postStatus == 'draft' ) ) {
			return;
		}

		?>
		<div class="cmb-row cmb-type-text">
			<div class="cmb-th">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo commonsbooking_sanitizeHTML( $label ); ?></label>
			</div>
			<div class="cmb-td">
				<input type="submit" name="<?php echo esc_attr( $id ); ?>" id="cb-submit-booking"
						value="<?php echo esc_html__( 'Submit booking', 'commonsbooking' ); ?>"/>
				<?php if ( $desc ) { ?>
					<p class="cmb2-metabox-description">
						<?php echo commonsbooking_sanitizeHTML( $desc ); ?>
					</p>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}
