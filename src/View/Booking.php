<?php

namespace CommonsBooking\View;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use Exception;

class Booking extends View {

	/**
	 * Returns template data for frontend.
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function getTemplateData(): void {
		header( 'Content-Type: application/json' );
		echo wp_json_encode( self::getBookingListData(), true );
		wp_die(); // All ajax handlers die when finished
	}

	/**
	 * @return array|false|mixed
	 * @throws Exception
	 */
	public static function getBookingListData() {
		$postsPerPage = 6;
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

		$filters = [
			'location'  => false,
			'item'      => false,
			'user'      => false,
			'startDate' => time(),
			'endDate'   => false,
			'status'    => false
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
			serialize( wp_get_current_user()->ID )
		);

		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			$bookingDataArray             = [];
			$bookingDataArray['page']     = $page;
			$bookingDataArray['per_page'] = $postsPerPage;
			$bookingDataArray['filters']  = [
				'user'     => [],
				'item'     => [],
				'location' => [],
				'status'   => []
			];

			$posts = \CommonsBooking\Repository\Booking::getForCurrentUser(
				true,
				$filters['startDate'] ?: null
			);

			if ( ! $posts ) {
				return false;
			}

			// Prepare Templatedata and remove invalid posts
			foreach ( $posts as $booking ) {

				// Get user infos
				$userInfo = get_userdata( $booking->post_author );

				// Decide which edit link to use
				$editLink = get_permalink( $booking->ID );

				$actions = '<a class="cb-button small" href="' . $editLink . '">' .
				           commonsbooking_sanitizeHTML( __( 'Details', 'commonsbooking' ) ) .
				           '</a>';

				$item          = $booking->getItem();
				$itemTitle     = $item ? $item->post_title : commonsbooking_sanitizeHTML( __( 'Not available', 'commonsbooking' ) );
				$location      = $booking->getLocation();
				$locationTitle = $location ? $booking->getLocation()->post_title : commonsbooking_sanitizeHTML( __( 'Not available', 'commonsbooking' ) );

				// Prepare row data
				$rowData = [
					"startDate"          => $booking->getStartDate(),
					"endDate"            => $booking->getEndDate(),
					"startDateFormatted" => date( 'd.m.Y H:i', $booking->getStartDate() ),
					"endDateFormatted"   => date( 'd.m.Y H:i', $booking->getEndDate() ),
					"item"               => $itemTitle,
					"location"           => $locationTitle,
					"bookingDate"        => date( 'd.m.Y H:i', strtotime( $booking->post_date ) ),
					"user"               => $userInfo->user_login,
					"status"             => $booking->post_status,
					"calendarLink"       => $item && $location ? add_query_arg( 'item', $item->ID, get_permalink( $location->ID ) ) : '',
					"content"            => [
						'user'   => [
							'label' => commonsbooking_sanitizeHTML( __( 'User', 'commonsbooking' ) ),
							'value' => $userInfo->first_name . ' ' . $userInfo->last_name . ' (' . $userInfo->user_login . ')',
						],
						'status' => [
							'label' => commonsbooking_sanitizeHTML( __( 'Status', 'commonsbooking' ) ),
							'value' => commonsbooking_sanitizeHTML( __( $booking->post_status, 'commonsbooking' ) ),
						]
					]
				];

				// Add booking code if there is one
				if ( $booking->getBookingCode() ) {
					$rowData['bookingCode'] = [
						'label' => commonsbooking_sanitizeHTML( __( 'Code', 'commonsbooking' ) ),
						'value' => $booking->getBookingCode()
					];
				}

				$continue = false;
				foreach ( $filters as $key => $value ) {
					if ( $value ) {
						if ( ! in_array( $key, [ 'startDate', 'endDate' ] ) ) {
							if ( $rowData[ $key ] != $value ) {
								$continue = true;
							}
						} else {
							if (
								( $key == 'startDate' && $value > intval( $booking->getEndDate() ) ) ||
								( $key == 'endDate' && $value < intval( $booking->getStartDate() ) )
							) {
								$continue = true;
							}
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
					$rowData['actions']         = $actions;
					$bookingDataArray['data'][] = apply_filters('commonsbooking_booking_filter', $rowData, $booking);
				}
			}

			$bookingDataArray['total']       = 0;
			$bookingDataArray['total_pages'] = 0;

			if ( array_key_exists( 'data', $bookingDataArray ) && count( $bookingDataArray['data'] ) ) {
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
					if ( $offset > $index ++ ) {
						unset( $bookingDataArray['data'][ $key ] );
						continue;
					}
					if ( $postsPerPage && $postsPerPage <= $pageCounter ++ ) {
						unset( $bookingDataArray['data'][ $key ] );
					}
				}
				$bookingDataArray['data'] = array_values( $bookingDataArray['data'] );
			}

			Plugin::setCacheItem(
				$bookingDataArray,
				Wordpress::getTags($posts),
				$customId
			);

			return $bookingDataArray;
		}
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
}
