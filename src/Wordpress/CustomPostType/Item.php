<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Settings\Settings;

class Item extends CustomPostType {

	/**
	 * @var string
	 */
	public static $postType = 'cb_item';

	/**
	 * Initiates needed hooks.
	 */
	public function initHooks() {
		add_filter( 'the_content', array( $this, 'getTemplate' ) );
		add_action( 'cmb2_admin_init', array( $this, 'registerMetabox' ) );

		// Listing of locations for item
		add_shortcode( 'cb_locations', array( \CommonsBooking\View\Location::class, 'shortcode' ) );

		// Add filter to backend list view
		add_action( 'restrict_manage_posts', array( self::class, 'addAdminCategoryFilter' ) );

		// Filter only for current user allowed posts
		add_action( 'pre_get_posts', array( $this, 'filterAdminList' ) );

		// Save-handling
		add_action( 'save_post', array( $this, 'savePost' ), 11, 2 );
	}

	/**
	 * Handles the creation and editing of the terms in the taxonomy for the location post type
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 *
	 * @return void
	 */
	public static function termChange( $term_id, $tt_id, $taxonomy ) {
		if ( $taxonomy == self::getTaxonomyName() ) {
			// update all dynamic timeframes
			Timeframe::updateAllTimeframes();
		}
	}

	/**
	 * Handles save-Request for items.
	 */
	public function savePost( $post_id, \WP_Post $post ) {
		if ( $post->post_type == self::$postType && $post_id ) {
			// update all dynamic timeframes
			Timeframe::updateAllTimeframes();
		}
	}



	/**
	 * Filters admin list by type (e.g. bookable, repair etc. )
	 *
	 * @param \WP_Query $query for admin list objects
	 *
	 * @return void
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
				$items = \CommonsBooking\Repository\Item::getByCurrentUser();
				array_walk(
					$items,
					function ( &$item, $key ) {
						$item = $item->ID;
					}
				);
				$query->query_vars['post__in'] = $items;
			}

			if (
				isset( $_GET['admin_filter_post_category'] ) &&
				$_GET['admin_filter_post_category'] != ''
			) {
				$query->query_vars['tax_query'] = array(
					array(
						'taxonomy'  => self::getTaxonomyName(),
						'field'     => 'term_id',
						'terms'     => $_GET['admin_filter_post_category'],
					),
				);
			}
		}
	}

	public static function getView() {
		return new \CommonsBooking\View\Item();
	}

	/**
	 * Returns CPT args.
	 *
	 * @return array
	 */
	public function getArgs(): array {
		$labels = array(
			'name'                  => esc_html__( 'Items', 'commonsbooking' ),
			'singular_name'         => esc_html__( 'Item', 'commonsbooking' ),
			'add_new'               => esc_html__( 'Add new', 'commonsbooking' ),
			'add_new_item'          => esc_html__( 'Add new item', 'commonsbooking' ),
			'edit_item'             => esc_html__( 'Edit item', 'commonsbooking' ),
			'new_item'              => esc_html__( 'Add new item', 'commonsbooking' ),
			'view_item'             => esc_html__( 'Show item', 'commonsbooking' ),
			'view_items'            => esc_html__( 'Show items', 'commonsbooking' ),
			'search_items'          => esc_html__( 'Search items', 'commonsbooking' ),
			'not_found'             => esc_html__( 'items not found', 'commonsbooking' ),
			'not_found_in_trash'    => esc_html__( 'No items found in trash', 'commonsbooking' ),
			'parent_item_colon'     => esc_html__( 'Parent items:', 'commonsbooking' ),
			'all_items'             => esc_html__( 'All items', 'commonsbooking' ),
			'archives'              => esc_html__( 'Item archive', 'commonsbooking' ),
			'attributes'            => esc_html__( 'Item attributes', 'commonsbooking' ),
			'insert_into_item'      => esc_html__( 'Add to item', 'commonsbooking' ),
			'uploaded_to_this_item' => esc_html__( 'Added to item', 'commonsbooking' ),
			'featured_image'        => esc_html__( 'Item image', 'commonsbooking' ),
			'set_featured_image'    => esc_html__( 'set item image', 'commonsbooking' ),
			'remove_featured_image' => esc_html__( 'remove item image', 'commonsbooking' ),
			'use_featured_image'    => esc_html__( 'use as item image', 'commonsbooking' ),
			'menu_name'             => esc_html__( 'Items', 'commonsbooking' ),

		);

		$slug = Settings::getOption( 'commonsbooking_options_general', 'posttypes_items-slug' );

		// args for the new post_type
		return array(
			'labels'            => $labels,

			// Sichtbarkeit des Post Types
			'public'            => true,

			// Standard Ansicht im Backend aktivieren (Wie Artikel / Seiten)
			'show_ui'           => true,

			// Soll es im Backend Menu sichtbar sein?
			'show_in_menu'      => false,

			// Position im Menu
			'menu_position'     => 3,

			// Post Type in der oberen Admin-Bar anzeigen?
			'show_in_admin_bar' => true,

			// in den Navigations Menüs sichtbar machen?
			'show_in_nav_menus' => true,

			// Hier können Berechtigungen in einem Array gesetzt werden
			// oder die Standard Werte post und page in Form eines Strings gesetzt werden
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
				'author',
			),

			// Soll der Post Type Kategorien haben?
			'taxonomies'          => array( self::getTaxonomyName() ),

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
	 * @param string $content value of content parameter of `the_content` filter
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getTemplate( $content ) {
		$cb_content   = '';
		$errormessage = '';
		if ( ! post_password_required() &&
					is_singular( self::getPostType() ) && is_main_query() && get_post_type() === self::getPostType() ) {
			ob_start();
			global $post;

			$item         = \CommonsBooking\Repository\Item::getPostById( $post );
			$errormessage = \CommonsBooking\View\Booking::getError();
			set_query_var( 'item', $item );
			commonsbooking_get_template_part( 'item', 'single' );
			$cb_content = ob_get_clean();
		}

		return $errormessage . $content . $cb_content;
	}

	/**
	 * Creates MetaBoxes for Custom Post Type Location using CMB2
	 * more information on usage: https://cmb2.io/
	 *
	 * @return void
	 */
	public function registerMetabox() {
		// Initiate the metabox Adress
		$cmb = new_cmb2_box(
			array(
				'id'           => COMMONSBOOKING_METABOX_PREFIX . 'item_info',
				'title'        => esc_html__( 'Item Info', 'commonsbooking' ),
				'object_types' => array( self::$postType ), // Post type
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true, // Show field names on the left
			)
		);

		// Show selection only to admins
		if ( commonsbooking_isCurrentUserAdmin() || commonsbooking_isCurrentUserCBManager() ) {
			$users       = UserRepository::getSelectableCBManagers();
			$userOptions = [];
			foreach ( $users as $user ) {
				$userOptions[ $user->ID ] = $user->get( 'user_nicename' ) . ' (' . $user->first_name . ' ' . $user->last_name . ')';
			}

			// Item Administrators
			$cmb->add_field(
				array(
					'name'       => esc_html__( 'Item Admin(s)', 'commonsbooking' ),
					'desc'       => esc_html__( 'choose one or more users to give them the permisssion to edit and manage this specific item. Only users with the role cb_manager can be selected here', 'commonsbooking' ),
					'id'         => COMMONSBOOKING_METABOX_PREFIX . 'item_admins',
					'type'       => 'pw_multiselect',
					'options'    => $userOptions,
					'attributes' => array(
						'placeholder' => esc_html__( 'Select item admins.', 'commonsbooking' ),
					),
				)
			);

			// item maintainer(s) emails
			$cmb->add_field(
				array(
					'name'       => esc_html__( 'Item maintainer email', 'commonsbooking' ),
					'desc'       => esc_html__(
						'Email addresses to which notifications about a change of item status (restriction, breakdown) shall be sent. You can enter multiple addresses separated by commas.',
						'commonsbooking'
					),
					'id'         => COMMONSBOOKING_METABOX_PREFIX . 'item_maintainer_email',
					'type'       => 'text',
					'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
				)
			);
		}

		// Check if custom meta fields are set in CB Options and generate MetaData-Box and fields
		if ( is_array( self::getCMB2FieldsArrayFromCustomMetadata( 'item' ) ) ) {
			$customMetaData = self::getCMB2FieldsArrayFromCustomMetadata( 'item' );

			// Initiate the metabox Adress
			$cmb = new_cmb2_box(
				array(
					'id'           => COMMONSBOOKING_METABOX_PREFIX . 'item_custom_meta',
					'title'        => esc_html__( 'Item Meta-Data', 'commonsbooking' ),
					'object_types' => array( self::$postType ), // Post type
					'context'      => 'normal',
					'priority'     => 'high',
					'show_names'   => true, // Show field names on the left
				)
			);

			// Add Custom Meta Fields defined in CommonsBooking Options (Tab MetaData)
			foreach ( $customMetaData as $customMetaDataField ) {
				$cmb->add_field( $customMetaDataField );
			}
		}

		// we store registered metaboxes to options table to be able to retrieve it in export function
		$metabox_fields = [];
		foreach ( $cmb->prop( 'fields' ) as $metabox_field ) {
			$metabox_fields[ $metabox_field['id'] ] = $metabox_field['name'];
		}
		Settings::updateOption( 'commonsbooking_settings_metaboxfields', static::getPostType(), $metabox_fields );
	}

	public static function registerPostTypeTaxonomy() {
		parent::registerPostTypeTaxonomy();

		// hook this for later, if we run it now, it would fail
		add_action( 'cmb2_admin_init', array( self::class, 'registerTaxonomyMetaboxes' ) );
	}

	protected static function getTaxonomyArgs() {
		return array(
			'label'             => esc_html__( 'Item Category', 'commonsbooking' ),
			'rewrite'           => array( 'slug' => static::getPostType() . '-cat' ),
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'public'            => true,
			'show_admin_column' => true,
		);
	}

	/**
	 * Add custom label for item categories that will be displayed in the map filter groups.
	 *
	 * @return void
	 */
	public static function registerTaxonomyMetaboxes() {
		$taxonomy = self::getTaxonomyName();

		$cmb_taxonomy = new_cmb2_box(
			array(
				'id'           => COMMONSBOOKING_METABOX_PREFIX . 'edit',
				'title'        => esc_html__( 'Item Category', 'commonsbooking' ),
				'object_types' => array( 'term' ),
				'taxonomies'   => array( 'category', $taxonomy ),
				'context'      => 'side',
			)
		);

		$cmb_taxonomy->add_field(
			array(
				'name' => __( 'Add custom title for filter', 'commonsbooking' ),
				'id'   => COMMONSBOOKING_METABOX_PREFIX . 'markup',
				'type' => 'textarea_small',
				'desc' => __( 'Define name that should be used for the category if it is displayed in the map as a filter group. You can also use this to add custom HTML to the category name. When left empty, the defined name of the category will be used.', 'commonsbooking' ),
			)
		);
	}
}
