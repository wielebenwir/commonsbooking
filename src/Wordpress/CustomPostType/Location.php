<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\View\Map;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Repository\UserRepository;

class Location extends CustomPostType {

	public static $postType = 'cb_location';

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		add_filter( 'the_content', array( $this, 'getTemplate' ) );
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		// Listing of items for location
		add_shortcode( 'cb_items', array( \CommonsBooking\View\Item::class, 'shortcode' ) );

		//Add filter to backend list view
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminCategoryFilter' ) );

		// Filter only for current user allowed posts
		add_action( 'pre_get_posts', array( $this, 'filterAdminList' ) );

		// Save-handling
		//just skip check for experiment/queries-test Branch
		if (! class_exists("WP_CLI") ) {
			add_action( 'save_post', array( $this, 'savePost' ), 11, 2 );
		}
	}

	/**
	 * Handles save-Request for location.
	 */
	public function savePost($post_id, \WP_Post $post) {
		if ( $post->post_type == self::$postType && $post_id ) {
			$location = new \CommonsBooking\Model\Location( intval( $post_id ) );
			$location->updateGeoLocation();

			//update all dynamic timeframes
			Timeframe::updateAllTimeframes();
		}
	}

	/**
	 * Handles the creation and editing of the terms in the taxonomy for the location post type
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 *
	 * @return void
	 */
	public static function termChange($term_id, $tt_id, $taxonomy) {
		if ( $taxonomy == self::$postType . 's_category' ) {
			//update all dynamic timeframes
			Timeframe::updateAllTimeframes();
		}
	}

	/**
	 * Filters admin list by type (e.g. bookable, repair etc. )
	 *
	 * @param  (wp_query object) $query
	 *
	 * @return Void
	 */
	public static function filterAdminList( $query ) {
		global $pagenow;

		if (
			is_admin() && $query->is_main_query() &&
			isset( $_GET['post_type'] ) && self::$postType == sanitize_text_field( $_GET['post_type'] ) &&
			$pagenow == 'edit.php'
		) {
			// Check if current user is allowed to see posts
			if ( ! commonsbooking_isCurrentUserAdmin() ) {
				$locations = \CommonsBooking\Repository\Location::getByCurrentUser();
				array_walk(
					$locations,
					function ( &$item, $key ) {
						$item = $item->ID;
					}
				);

				$query->query_vars['post__in'] = $locations;
			}

			if (
				isset( $_GET['admin_filter_post_category'] ) &&
				$_GET['admin_filter_post_category'] != ''
			) {
				$query->query_vars['tax_query'] = array(
						array(
						'taxonomy'	=>	self::$postType . 's_category',
						'field'		=>	'term_id',
						'terms'		=>	$_GET['admin_filter_post_category']
						)
				);
			}
		}
	}

	public static function getView() {
		return new \CommonsBooking\View\Location();
	}

	public function getTemplate( $content ) {
		$cb_content = '';
		if ( is_singular( self::getPostType() ) && is_main_query() ) {
			ob_start();
			commonsbooking_get_template_part( 'location', 'single' );
			$cb_content = ob_get_clean();
		} // if archive...

		return $content . $cb_content;
	}

	public function getArgs() {
		$labels = array(
			'name'                  => esc_html__( 'Locations', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Location', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new location', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit location', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new location', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show location', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show locations', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search locations', 'commonsbooking' ),
			'not_found'             => esc_html__( 'location not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No locations found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent location:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All locations', 'commonsbooking' ),
			'archives'              => esc_html__( 'Location archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Location attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to location', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to location', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Location image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set location image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove location image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as location image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Locations', 'commonsbooking' ),
		);

		$slug = Settings::getOption( 'commonsbooking_options_general', 'posttypes_locations-slug' );

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
			'menu_position'     => 4,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar' => true,

			// in den Navigationsmenüs sichtbar machen?
			'show_in_nav_menus' => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die standard Werte post und page in Form eines Strings gesetzt werden
			'capability_type'   => array( self::$postType, self::$postType . 's' ),

			'map_meta_cap'        => true,

			// Soll es im Frontend abrufbar sein?
			'publicly_queryable'  => true,

			// Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
			'exclude_from_search' => false,

			// Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
			'supports'            => array(
				'title',
				'editor',
				'thumbnail',
				'custom-fields',
				'revisions',
				'excerpt',
				'author'
			),

			// Soll der Post Type Kategien haben?
			'taxonomies'          => array( self::$postType . 's_category' ),

			// Soll der Post Type Archiv-Seiten haben?
			'has_archive'         => false,

			// Soll man den Post Type exportieren können?
			'can_export'          => false,

			// Slug unseres Post Types für die redirects
			// dieser Wert wird später in der URL stehen
			'rewrite'             => array( 'slug' => $slug ),

			'show_in_rest' => true,
		);
	}

	/**
	 * Creates MetaBoxes for Custom Post Type Location using CMB2
	 * more information on usage: https://cmb2.io/
	 *
	 * @return void
	 */
	public function registerMetabox() {
		// Initiate the metabox address
		$cmb = new_cmb2_box( array(
			'id'           => COMMONSBOOKING_METABOX_PREFIX . 'location_adress',
			'title'        => esc_html__( 'Address', 'commonsbooking' ),
			'object_types' => array( self::$postType ), // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		) );

		// Adress
		$cmb->add_field( array(
			'name'       => esc_html__( 'Street / No.', 'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_street',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			'attributes' => array(
				'required' => 'required',
			),
		) );

		// Postcode
		$cmb->add_field( array(
			'name'       => esc_html__( 'Postcode', 'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_postcode',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			'attributes' => array(
				'required' => 'required',
			),
		) );

		// City
		$cmb->add_field( array(
			'name'       => esc_html__( 'City', 'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_city',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			'attributes' => array(
				'required' => 'required',
			),
		) );

		// Country
		$cmb->add_field( array(
			'name'       => esc_html__( 'Country', 'commonsbooking' ),
			//'desc'       => esc_html__('field description (optional)', 'commonsbooking'),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_country',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Generate Geo-Coordinates
		$cmb->add_field( array(
			'name'       => esc_html__( 'Set / Update GPS and map', 'commonsbooking' ),
			//'desc'       => esc_html__('field description (optional)', 'commonsbooking'),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'get_gps',
			'type'       => 'text',
            'render_row_cb' => array( Map::class, 'renderGeoRefreshButton' ),
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );


		// Latitude
		$cmb->add_field( array(
			'name'       => esc_html__( 'Latitude', 'commonsbooking' ),
			'desc'       => commonsbooking_sanitizeHTML( __('The latitude is calculated automatically when you click the "set / update GPS" button after entering the street, postal code and city.', 'commonsbooking') ),
			'id'         => 'geo_latitude',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Longitude
		$cmb->add_field( array(
			'name'       => esc_html__( 'Longitude', 'commonsbooking' ),
			'desc'       => commonsbooking_sanitizeHTML( __('The longitude is calculated automatically when you click the "set / update GPS" button after entering the street, postal code and city.', 'commonsbooking') ),
			'id'         => 'geo_longitude',
			'type'       => 'text',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Map
		$cmb->add_field( array(
			'name'       => esc_html__( 'Position', 'commonsbooking' ),
			//'desc'       => esc_html__('field description (optional)', 'commonsbooking'),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . '_map_position',
			'type'       => 'cb_map',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Show map on item view checkbox
		$cmb->add_field( array(
			'name'       => esc_html__( 'Show location map on item view', 'commonsbooking' ),
			'desc'       => esc_html__('If enabled, a map showing the location will be displayed on the location details page.', 'commonsbooking'),
			'id'         => 'loc_showmap',
			'type'       => 'checkbox',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Initiate the metabox Information
		$cmb = new_cmb2_box( array(
			'id'           => COMMONSBOOKING_METABOX_PREFIX . 'location_info',
			'title'        => esc_html__( 'General Location information', 'commonsbooking' ),
			'object_types' => array( self::$postType ), // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		) );

		// location email
		$cmb->add_field( array(
			'name'       => esc_html__( 'Location email', 'commonsbooking' ),
			'desc'       => esc_html__( 'Email addresses of the owner of the station. Can be reminded about bookings / cancellations and will receive the booking codes (when configured in the timeframe). You can enter multiple addresses separated by commas.',
				'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_email',
			'type'       => 'text',
			'attributes' => array(
				'class' => "regular-text cmb2-oembed",
			),
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			// 'repeatable'      => true,
		) );

		// checkbox BCC bookings / cancellations to location email
		$cmb->add_field( array(
			'name'       => esc_html__( 'Send copy of bookings / cancellations to location email', 'commonsbooking' ),
			'desc'       => esc_html__( 'If enabled, the location email will receive a copy of all booking and cancellation notifications.', 'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc',
			'type'       => 'checkbox',
			'default_cb' => 'cmb2_set_checkbox_default_for_new_post'
		) );

		// pickup description
		$cmb->add_field( array(
			'name'       => esc_html__( 'Pickup instructions', 'commonsbooking' ),
			'desc'       => esc_html__( 'Type in information about the pickup process (e.g. detailed route description, opening hours, etc.). This will be shown to user in booking process and booking confirmation mail',
				'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_pickupinstructions',
			'type'       => 'textarea_small',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			// 'repeatable'      => true,
		) );

		// location contact
		$cmb->add_field( array(
			'name'       => esc_html__( 'Location contact information', 'commonsbooking' ),
			'desc'       => esc_html__( 'information about how to contact the location (e.g. contact person, phone number, e-mail etc.). This will be shown to user in booking process and booking confirmation mail',
				'commonsbooking' ),
			'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_contact',
			'type'       => 'textarea_small',
			'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
		) );

		// Show selection only to admins
		if ( commonsbooking_isCurrentUserAdmin() || commonsbooking_isCurrentUserCBManager() ) {
			// Location admin selection
			$users       = UserRepository::getSelectableCBManagers();
			$userOptions = [];
			foreach ( $users as $user ) {
				$userOptions[ $user->ID ] = $user->get( 'user_nicename' ) . " (" . $user->first_name . " " . $user->last_name . ")";
			}
			$cmb->add_field( array(
				'name'       => esc_html__( 'Location Admin(s)', 'commonsbooking' ),
				'desc'       => esc_html__( 'choose one or more users to give them the permisssion to edit and manage this specific location. Only users with the role CommonsBooking Manager can be selected here.',
					'commonsbooking' ),
				'id'         => COMMONSBOOKING_METABOX_PREFIX . 'location_admins',
				'type'       => 'pw_multiselect',
				'options'    => $userOptions,
				'attributes' => array(
					'placeholder' => esc_html__( 'Select location admins.', 'commonsbooking' )
				),
			) );
		}

		$cmb->add_field( array (
			'name' => esc_html__( 'Use global location settings', 'commonsbooking' ),
			'desc' => esc_html__( 'If selected, the global location settings (under the "General" tab) will be used for this location. If not selected, the settings below will be used.', 'commonsbooking' ),
			'id'   => COMMONSBOOKING_METABOX_PREFIX . 'use_global_settings',
			'type' => 'checkbox',
			'default_cb' => 'cmb2_set_checkbox_default_for_new_post',
		) );

		foreach ( self::getOverbookingSettingsMetaboxes() as $metabox ) {
			$cmb->add_field( $metabox );
		}

		$cmb->add_field( array(
			'name' => esc_html__( 'Receive booking start reminder', 'commonsbooking' ),
			'desc' => commonsbooking_sanitizeHTML( __( 'If selected, this location receives reminder emails of bookings starting soon. The notifications are sent to all addresses specified in the location email list (first as receiver, all following as BCC). This type of reminder needs to be activated in the <a href="admin.php?page=commonsbooking_options_reminder"> general CommonsBooking settings</a>.', 'commonsbooking' ) ),
			'id'   => COMMONSBOOKING_METABOX_PREFIX . 'receive_booking_start_reminder',
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
			'name' => esc_html__( 'Receive booking end reminder', 'commonsbooking' ),
			'desc' => commonsbooking_sanitizeHTML( __( 'If selected, this location receives reminder emails of bookings ending soon. The notifications are sent to all addresses specified in the location email list (first as receiver, all following as BCC). This type of reminder needs to be activated in the <a href="admin.php?page=commonsbooking_options_reminder"> general CommonsBooking settings</a>.', 'commonsbooking' ) ),
			'id'   => COMMONSBOOKING_METABOX_PREFIX . 'receive_booking_end_reminder',
			'type' => 'checkbox',
		) );

		// Check if custom meta fields are set in CB Options and generate MetaData-Box and fields
		if ( is_array( self::getCMB2FieldsArrayFromCustomMetadata( 'location' ) ) ) {
			$customMetaData = self::getCMB2FieldsArrayFromCustomMetadata( 'location' );

			// Initiate the metabox Adress
			$cmb = new_cmb2_box( array(
				'id'           => COMMONSBOOKING_METABOX_PREFIX . 'location_custom_meta',
				'title'        => esc_html__( 'Location Meta-Data', 'commonsbooking' ),
				'object_types' => array( self::$postType ), // Post type
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true, // Show field names on the left
			) );

			// Add Custom Meta Fields defined in CommonsBooking Options (Tab MetaData)
			foreach ( $customMetaData as $customMetaDataField ) {
				$cmb->add_field( $customMetaDataField );
			}

		}

		// we store registered metaboxes to options table to be able to retrieve it in export function
		foreach ($cmb->meta_box['fields'] as $metabox_field) {
			$metabox_fields[$metabox_field['id']] = $metabox_field['name'];
		}
		Settings::updateOption('commonsbooking_settings_metaboxfields', $this->getPostType(), $metabox_fields);
	}

	/**
	 * Will get the metaboxes for the location settings that can also be overwritten by the global location settings.
	 * We put them in a function here, so they can be retrieved by the OptionsArray.php as well.
	 *
	 * @return array[]
	 */
	public static function getOverbookingSettingsMetaboxes() {
		return [
			array(
				'name' => esc_html__( 'Allow locked day overbooking', 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML( __( 'If selected, all not selected days in any bookable timeframe that is connected to this location can be overbooked. Read the documentation <a target="_blank" href="https://commonsbooking.org/?p=435">Create Locations</a> for more information.', 'commonsbooking' ) ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range',
				'type' => 'checkbox',
			),
			array(
				'name' => esc_html__( 'Count locked days when overbooking', 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML( __( 'If selected, days that are overbooked will be counted towards the maximum number of bookable days. If this option is disabled, locked days that are overbooked will allow for bookings that are longer than the maximum number of bookable days configured for the timeframe.', 'commonsbooking' ) ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range',
				'type' => 'checkbox',
			),
			array(
				'name' => esc_html__( 'Count connected locked days as one', 'commonsbooking' ),
				'desc' => commonsbooking_sanitizeHTML( __( 'Here you can specify, if a connected span of locked days should be counted individually or just use up x amount of the maximum quota the user is allowed to book. If you set this field to 0, every day will be counted individually. If you set this field to 1, all overbooked days, no matter how many, will always count for 1 day. If you set this to 2, they will count a maximum of two days and so on.', 'commonsbooking' ) ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum',
				'default' => '0',
				'type' => 'text_small',
			)
		];

	}

}
