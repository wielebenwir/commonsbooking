<?php


namespace CommonsBooking\Wordpress\CustomPostType;

use Exception;
use CommonsBooking\View\Admin\Filter;

/**
 * Because we use CMB2 text_datetime_timestamp fields, the meta fields for start and end date are stored in unix
 * timestamp (without timezone offset), when edited from admin backend.
 */
class Restriction extends CustomPostType {

	private const SEND_BUTTON_ID = 'restriction-send';
	/**
	 * @var string
	 */
	public static $postType = 'cb_restriction';

	/**
	 * @inheritDoc
	 */
	public static function getView() {
		return new \CommonsBooking\View\Restriction();
	}

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		// Add Meta Boxes
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminTypeFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminItemFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminLocationFilter' ) );
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminStatusFilter' ) );
		add_action( 'pre_get_posts', array( self::class, 'filterAdminList' ) );

		add_action( 'save_post', array( $this, 'savePost' ), 11, 2 );
	}

	public function __construct() {
		$this->types = self::getTypes();

		$this->listColumns = [
			\CommonsBooking\Model\Restriction::META_TYPE                                          => esc_html__( 'Type', 'commonsbooking' ),
			\CommonsBooking\Model\Restriction::META_ITEM_ID                                       => esc_html__( 'Item', 'commonsbooking' ),
			\CommonsBooking\Model\Restriction::META_LOCATION_ID                                   => esc_html__( 'Location', 'commonsbooking' ),
			\CommonsBooking\Model\Restriction::META_START                              => esc_html__( 'Start Date', 'commonsbooking' ),
			\CommonsBooking\Model\Restriction::META_END                              => esc_html__( 'End Date', 'commonsbooking' ),
			\CommonsBooking\Model\Restriction::META_STATE                                   => esc_html__( 'Restriction Status', 'commonsbooking' ),
		];

		// List settings
		$this->removeListDateColumn();
	}

	/**
	 * Adds filter dropdown // filter by type (Total Breakdown, Notice) in restrictions List
	 *
	 * @return void
	 */
	public static function addAdminTypeFilter() {
		Filter::renderFilter(
			static::$postType,
			esc_html__( 'Filter By Type ', 'commonsbooking' ),
			'filter_type',
			static::getTypes()
		);
	}

	/**
	 * Adds filter dropdown // filter by item in restrictions List
	 */
	public static function addAdminItemFilter() {
		$items = \CommonsBooking\Repository\Item::get(
			[
				'post_status' => 'any',
				'orderby'     => 'post_title',
				'order'       => 'asc',
				'nopaging'    => true,
			]
		);
		if ( $items ) {
			$values = [];
			foreach ( $items as $item ) {
				$values[ $item->ID ] = $item->post_title;
			}

			Filter::renderFilter(
				static::$postType,
				esc_html__( 'Filter By Item ', 'commonsbooking' ),
				'filter_item',
				$values
			);
		}
	}

	/**
	 * Adds filter dropdown // filter by location in restrictions List
	 */
	public static function addAdminLocationFilter() {
		$locations = \CommonsBooking\Repository\Location::get(
			[
				'post_status' => 'any',
				'orderby'     => 'post_title',
				'order'       => 'asc',
				'nopaging'    => true,
			]
		);
		if ( $locations ) {
			$values = [];
			foreach ( $locations as $location ) {
				$values[ $location->ID ] = $location->post_title;
			}

			Filter::renderFilter(
				static::$postType,
				esc_html__( 'Filter By Location ', 'commonsbooking' ),
				'filter_location',
				$values
			);
		}
	}

	/**
	 * Adds filter dropdown // filter by status in restrictions list
	 */
	public static function addAdminStatusFilter() {
		Filter::renderFilter(
			static::$postType,
			esc_html__( 'Filter By Status ', 'commonsbooking' ),
			'filter_state',
			static::getStates()
		);
	}

	/**
	 * Modifies data in custom columns
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function setCustomColumnsData( $column, $post_id ) {

		if ( $value = get_post_meta( $post_id, $column, true ) ) {
			switch ( $column ) {
				case \CommonsBooking\Model\Restriction::META_LOCATION_ID:
				case \CommonsBooking\Model\Restriction::META_ITEM_ID:
					if ( $post = get_post( $value ) ) {
						if ( get_post_type( $post ) == Location::getPostType() || get_post_type(
							$post
						) == Item::getPostType() ) {
							echo commonsbooking_sanitizeHTML( $post->post_title );
							break;
						}
					}
					echo '-';
					break;
				case \CommonsBooking\Model\Restriction::META_TYPE:
					$output = '-';

					foreach ( $this->getCustomFields() as $customField ) {
						if ( $customField['id'] == \CommonsBooking\Model\Restriction::META_TYPE ) {
							foreach ( $customField['options'] as $key => $label ) {
								if ( $value == $key ) {
									$output = $label;
								}
							}
						}
					}
					echo commonsbooking_sanitizeHTML( $output );
					break;
				case \CommonsBooking\Model\Restriction::META_STATE:
					$output = '-';

					foreach ( $this->getCustomFields() as $customField ) {
						if ( $customField['id'] == \CommonsBooking\Model\Restriction::META_STATE ) {
							foreach ( $customField['options'] as $key => $label ) {
								if ( $value == $key ) {
									$output = $label;
								}
							}
						}
					}
					echo commonsbooking_sanitizeHTML( $output );
					break;
				case \CommonsBooking\Model\Restriction::META_START:
				case \CommonsBooking\Model\Restriction::META_END:
					echo date( 'd.m.Y H:i', $value );
					break;
				default:
					echo commonsbooking_sanitizeHTML( $value );
					break;
			}
		} else {
			$bookingColumns = [
				'post_date',
				'post_status',
			];

			if (
				property_exists( $post = get_post( $post_id ), $column ) && (
					! in_array( $column, $bookingColumns ) ||
					get_post_meta( $post_id, \CommonsBooking\Model\Restriction::META_TYPE, true ) == Timeframe::BOOKING_ID
				)
			) {
				echo commonsbooking_sanitizeHTML( $post->{$column} );
			}
		}
	}

	/**
	 * Filters admin list by type, timerange, user
	 *
	 * @param \WP_Query $query for admin list objects
	 *
	 * @return void
	 */
	public static function filterAdminList( $query ) {
		global $pagenow;

		if (
			is_admin() && $query->is_main_query() &&
			isset( $_GET['post_type'] ) && static::$postType == sanitize_text_field( $_GET['post_type'] ) &&
			$pagenow == 'edit.php'
		) {
			// Meta value filtering
			$query->query_vars['meta_query'] = array(
				'relation' => 'AND',
			);
			$meta_filters                    = [
				\CommonsBooking\Model\Restriction::META_TYPE        => 'admin_filter_type',
				\CommonsBooking\Model\Restriction::META_STATE       => 'admin_filter_state',
				\CommonsBooking\Model\Restriction::META_ITEM_ID     => 'admin_filter_item',
				\CommonsBooking\Model\Restriction::META_LOCATION_ID => 'admin_filter_location',
			];

			foreach ( $meta_filters as $key => $filter ) {
				if (
					isset( $_GET[ $filter ] ) &&
					$_GET[ $filter ] != ''
				) {
					$query->query_vars['meta_query'][] = array(
						'key'   => $key,
						'value' => sanitize_text_field( $_GET[ $filter ] ),
					);
				}
			}

			// Post field filtering
			$post_filters = [
				'post_status' => 'admin_filter_post_status',
			];
			foreach ( $post_filters as $key => $filter ) {
				if (
					isset( $_GET[ $filter ] ) &&
					$_GET[ $filter ] != ''
				) {
					$query->query_vars[ $key ] = sanitize_text_field( $_GET[ $filter ] );
				}
			}

			// Check if current user is allowed to see posts
			if ( ! commonsbooking_isCurrentUserAdmin() ) {
				$locations = \CommonsBooking\Repository\Location::getByCurrentUser();
				array_walk(
					$locations,
					function ( &$item, $key ) {
						$item = $item->ID;
					}
				);
				$items = \CommonsBooking\Repository\Item::getByCurrentUser();
				array_walk(
					$items,
					function ( &$item, $key ) {
						$item = $item->ID;
					}
				);

				$query->query_vars['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => \CommonsBooking\Model\Restriction::META_LOCATION_ID,
						'value'   => $locations,
						'compare' => 'IN',
					),
					array(
						'key'     => \CommonsBooking\Model\Restriction::META_ITEM_ID,
						'value'   => $items,
						'compare' => 'IN',
					),
				);
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Restrictions', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Restriction', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new Restriction', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit Restriction', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new Restriction', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show Restriction', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show Restrictions', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search Restrictions', 'commonsbooking' ),
			'not_found'             => esc_html__( 'Restrictions not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No Restrictions found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent Restrictions:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All Restrictions', 'commonsbooking' ),
			'archives'              => esc_html__( 'Restriction archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Restriction attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to Restriction', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to Restriction', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Restriction image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set Restriction image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove Restriction image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as Restriction image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Restrictions', 'commonsbooking' ),
		);

		// args for the new post_type
		return array(
			'labels'            => $labels,

			// Sichtbarkeit des Post Types
			'public'            => true,

			// Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'           => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'      => false,

			// Position im Menu
			'menu_position'     => 8,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar' => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus' => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die standart Werte post und page in form eines Strings gesetzt werden
			'capability_type'   => array( self::$postType, self::$postType . 's' ),

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

			'show_in_rest' => true,
		);
	}

	/**
	 * Registers metaboxes for cpt.
	 */
	public function registerMetabox() {
		$cmb = new_cmb2_box(
			[
				'id'           => static::getPostType() . '-custom-fields',
				'title'        => esc_html__( 'Restriction', 'commonsbooking' ),
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
	protected function getCustomFields(): array {
		// We need static types, because german month names dont't work for datepicker
		$dateFormat = 'd/m/Y';
		if ( str_starts_with( get_locale(), 'de_' ) ) {
			$dateFormat = 'd.m.Y';
		}

		if ( str_starts_with( get_locale(), 'en_' ) ) {
			$dateFormat = 'm/d/Y';
		}

		return array(
			array(
				'name'    => esc_html__( 'Type', 'commonsbooking' ),
				'desc'    => commonsbooking_sanitizeHTML(
					__(
						'Select the type of restriction.<br>
				Select <strong>Notice</strong>, the item can still be used and if e.g. only one part is missing or defective.<br>
				Select <strong>total breakdown</strong> if the defect means that the item can no longer be used. If you select total breakdown
				all affected bookings will be automatically canceled after activating this restriction and after clicking send the information email.
				',
						'commonsbooking'
					)
				),
				'id'      => \CommonsBooking\Model\Restriction::META_TYPE,
				'type'    => 'select',
				'options' => self::getTypes(),
			),
			array(
				'name'             => esc_html__( 'Location', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Restriction::META_LOCATION_ID,
				'type'             => 'select',
				'show_option_none' => commonsbooking_isCurrentUserAdmin() ? esc_html__( 'All', 'commonsbooking' ) : false,
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Location::getByCurrentUser() ),
			),
			array(
				'name'             => esc_html__( 'Item', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Restriction::META_ITEM_ID,
				'type'             => 'select',
				'show_option_none' => esc_html__( 'All', 'commonsbooking' ),
				'options'          => self::sanitizeOptions( \CommonsBooking\Repository\Item::getByCurrentUser() ),
			),
			array(
				'name' => esc_html__( 'Hint', 'commonsbooking' ),
				'id'   => \CommonsBooking\Model\Restriction::META_HINT,
				'desc' => commonsbooking_sanitizeHTML( __( 'Please enter here a short information about the reason and possible effects of the usage restriction. <br>The explanation will be displayed on the article page and in the notification e-mail.', 'commonsbooking' ) ),
				'type' => 'textarea',
			),
			array(
				'name'        => esc_html__( 'Start date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the start date and time', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Restriction::META_START,
				'type'        => 'text_datetime_timestamp',
				// TODO timeformat should be configurable
				'time_format' => 'H:i',
				'date_format' => $dateFormat,
				'default'     => strtotime( 'today' ),
			),
			array(
				'name'        => esc_html__( 'End date', 'commonsbooking' ),
				'desc'        => esc_html__( 'Set the estimated end date and time', 'commonsbooking' ),
				'id'          => \CommonsBooking\Model\Restriction::META_END,
				'type'        => 'text_datetime_timestamp',
				// TODO timeformat should be configurable
				'time_format' => 'H:i',
				'date_format' => $dateFormat,
				'default'     => strtotime( 'today 23:55' ),

			),
			array(
				'type'    => 'hidden',
				'id'      => 'restriction-prevent_delete_meta_movetotrash',
				'default' => wp_create_nonce( plugin_basename( __FILE__ ) ),
			),
			array(
				'name'             => esc_html__( 'Status', 'commonsbooking' ),
				'id'               => \CommonsBooking\Model\Restriction::META_STATE,
				'desc'             => commonsbooking_sanitizeHTML(
					__(
						'Choose status of this restriction. <br>
				Set to <strong>None</strong> if you want to deactivate the restriction.<br>
					Set to <strong>Active</strong> if the restriction is active. <br>
Set to <strong>Problem Solved</strong>, if the restriction is no longer in effect.<br>
Depending on the selected status, affected users will receive corresponding notification emails.
Select the desired status and then click the "Send" button to send the e-mail.<br>',
						'commonsbooking'
					)
				),
				'type'             => 'select',
				'show_option_none' => false,
				'options'          => self::getStates(),
			),
			array(
				'name'          => esc_html__( 'Send notification emails to users', 'commonsbooking' ),
				'desc'          => esc_html__( 'Important: Please save this restriction before clicking the send-button. Dependent of the status of the restriction, the appropriate notifications are sent to all affected users and location admins. You can configure the e-mail templates via Options -> Commonsbooking -> Tab Restrictions', 'commonsbooking' ),
				'id'            => self::SEND_BUTTON_ID,
				'type'          => 'text',
				'render_row_cb' => array( \CommonsBooking\View\Restriction::class, 'renderSendButton' ),
			),
			array(
				'id'   => \CommonsBooking\Model\Restriction::META_SENT,
				'type' => 'hidden',
			),
		);
	}

	/**
	 * @return string[]
	 */
	public static function getTypes() {

		return [
			'repair' => esc_html__( 'Total breakdown', 'commonsbooking' ),
			'hint'   => esc_html__( 'Notice', 'commonsbooking' ),
		];
	}

	/**
	 * @return string[]
	 */
	public static function getStates() {

		return [
			'none' => esc_html__( 'Not active', 'commonsbooking' ),
			'active'   => esc_html__( 'Active', 'commonsbooking' ),
			'solved' => esc_html__( 'Problem solved', 'commonsbooking' ),
		];
	}

	/**
	 * Handles save-Request for location.
	 */
	public function savePost( $post_id, $post ) {
		if ( $post->post_type == self::$postType && $post_id ) {
			if ( $this->hasRunBefore( __METHOD__ ) ) {
				return;
			}

			if ( array_key_exists( self::SEND_BUTTON_ID, $_REQUEST ) ) {
				update_post_meta( $post_id, \CommonsBooking\Model\Restriction::META_SENT, time() );
				try {
					$restriction = new \CommonsBooking\Model\Restriction( $post_id );
					$restriction->apply();
				} catch ( Exception $e ) {
					// nothing to do in this case.
				}
			}
		}
	}
}
