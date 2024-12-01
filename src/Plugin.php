<?php


namespace CommonsBooking;

use CommonsBooking\CB\CB1UserFields;
use CommonsBooking\Exception\BookingDeniedException;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Map\LocationMapAdmin;
use CommonsBooking\Map\SearchShortcode;
use CommonsBooking\Model\Booking;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Service\BookingRuleApplied;
use CommonsBooking\Service\Cache;
use CommonsBooking\Service\Scheduler;
use CommonsBooking\Service\iCalendar;
use CommonsBooking\Service\Upgrade;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\View\Dashboard;
use CommonsBooking\View\MassOperations;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Wordpress\CustomPostType\Restriction;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Wordpress\Options\AdminOptions;
use CommonsBooking\Wordpress\Options\OptionsTab;
use CommonsBooking\Wordpress\PostStatus\PostStatus;

class Plugin {

	use Cache;

	/**
	 * CB-Manager id.
     *
	 * @var string
	 */
	public static $CB_MANAGER_ID = 'cb_manager';

	/**
	 * Plugin activation tasks.
	 */
	public static function activation() {
		// Register custom user roles (e.g. cb_manager)
		self::addCustomUserRoles();

		// add role caps for custom post types
		self::addCPTRoleCaps();

		// Init booking codes table
		BookingCodes::initBookingCodesTable();

		self::clearCache();
	}

	/**
	 * Plugin deactivation tasks.
	 */
	public static function deactivation() {
		do_action( Scheduler::UNSCHEDULER_HOOK );
	}

	/**
	 * Adds capabilities for custom post types.
	 * This function runs after plugin activation and upon plugin update.
	 *
	 * NOTE: Before the change, this function did not work because it was dependent
	 * on the role of the user upon activation. This did not make sense, as this is always
	 * the admin. The function was changed to add the correct capabilities for all roles.
	 *
	 * This usually only happens, when the plugin is activated through wp-cli (because no user is logged in there)
	 * The way this function has worked before is instead to assign the CB-Manager the capabilites of the admin.
	 *
	 * When we change the behaviour to what was probably intended, we break the functionality for the CB Manager to edit posts (like bookings or items).
	 * This is why we now assign the CB-Manager the capabilities of the admin, the supposedly correct behaviour is commented out below.
	 *
	 * Therefore, this function does not work differently, it just has the same behaviour when plugin is activated through wp-cli or through the admin interface.
	 * @return void
	 */
	public static function addCPTRoleCaps() {
		//admins are allowed to see all custom post types
		$adminAllowedCPT = self::getCustomPostTypes();
		$CBManagerAllowedCPT = self::getCBManagerCustomPostTypes();
		// Add capabilities for user roles
		foreach ( $adminAllowedCPT as $customPostType ) {
			self::addRoleCaps( $customPostType::$postType, 'administrator' );
			//assign all capabilities of admin to CB-Manager (see comment above)
			//We deliberately don't use the getManagerRoles from the UserRepository here, because the custom roles should be able to define their own permissions
			self::addRoleCaps( $customPostType::$postType, self::$CB_MANAGER_ID );
		}
		/*
		foreach ( $CBManagerAllowedCPT as $customPostType ) {
			self::addRoleCaps( $customPostType::$postType, self::$CB_MANAGER_ID );
		}
		*/
	}

	/**
	 * Returns needed roles and caps for specific roles
     *
	 * @return \bool[][]
	 */
	public static function getRoleCapMapping( $roleName = null) {
		if ( $roleName === null ) {
			return [
				//We deliberately don't use the getManagerRoles from the UserRepository here, because the custom roles should be able to define their own permissions
				self::$CB_MANAGER_ID => [
					'read'                                 => true,
					'manage_' . COMMONSBOOKING_PLUGIN_SLUG => true,
				],
				'administrator'      => [
					'read'                                 => true,
					'edit_posts'                           => true,
					'manage_' . COMMONSBOOKING_PLUGIN_SLUG => true,
				],
			];
		}
		else {
			$roleCapMapping = self::getRoleCapMapping();
			return [
				$roleName => $roleCapMapping[$roleName]
			];
		}
	}

	/**
	 * Adds cb user roles to WordPress.
	 */
	public static function addCustomUserRoles() {
		foreach ( self::getRoleCapMapping() as $roleName => $caps ) {
			$role = get_role( $roleName );
			if ( ! $role ) {
				$role = add_role(
					$roleName,
                    // TODO we should set a translatable role display name - for now its not defined at any place
					$roleName
				);
			}

			foreach ( $caps as $cap => $grant ) {
				$role->remove_cap( $cap );
				$role->add_cap( $cap, $grant );
			}
		}
	}

	/**
	 * Will get all registered custom post types for this plugin as an instance of the CustomPostType class
	 * All CustomPostType classes extend the CustomPostType class and must be registered in this method.
	 * When defining a CustomPostType, you must also define a model for it, which extends the CustomPost class.
	 * The existence of a model is checked in the @see PluginTest::testGetCustomPostTypes() test.
	 * @return CustomPostType[]
	 */
	public static function getCustomPostTypes(): array {
		return [
			new Item(),
			new Location(),
			new Timeframe(),
			new Map(),
			new \CommonsBooking\Wordpress\CustomPostType\Booking(),
			new Restriction(),
		];
	}

	/**
	 * Tests if a given post belongs to our CPTs
	 * @param $post int|\WP_Post - post id or post object
	 *
	 * @return bool
	 */
	public static function isPostCustomPostType($post): bool {
		if (is_int($post)) {
			$post = get_post($post);
		}

		if ( empty( $post ) ) {
			return false;
		}

		$validPostTypes = self::getCustomPostTypesLabels();
		return in_array($post->post_type,$validPostTypes);
	}

	/**
	 * Returns only custom post types, which are allowed for cb manager
     *
	 * @return array
	 */
	public static function getCBManagerCustomPostTypes(): array {
		return [
			new Item(),
			new Location(),
			new Timeframe(),
			new \CommonsBooking\Wordpress\CustomPostType\Booking(),
			new Restriction(),
		];
	}

	/**
	 * Adds permissions to edit custom post types for specified role.
	 *
	 * @param $postType
	 */
	protected static function addRoleCaps( $postType, $roleName ) {
		// Add the roles you'd like to administer the custom post types
		$roles = array_keys( self::getRoleCapMapping( $roleName ) );

		// Loop through each role and assign capabilities
		foreach ( $roles as $the_role ) {
			$role = get_role( $the_role );
			if ( $role ) {
				$role->add_cap( 'read_' . $postType );
				$role->add_cap( 'manage_' . COMMONSBOOKING_PLUGIN_SLUG . '_' . $postType );

				$role->add_cap( 'edit_' . $postType );
				$role->add_cap( 'edit_' . $postType . 's' ); // show item list
				$role->add_cap( 'edit_private_' . $postType . 's' );
				$role->add_cap( 'edit_published_' . $postType . 's' );

				$role->add_cap( 'publish_' . $postType . 's' );

				$role->add_cap( 'delete_' . $postType );
				$role->add_cap( 'delete_' . $postType . 's' );

				$role->add_cap( 'read_private_' . $postType . 's' );
				$role->add_cap( 'edit_others_' . $postType . 's' );
				$role->add_cap( 'delete_private_' . $postType . 's' );
				$role->add_cap( 'delete_published_' . $postType . 's' ); // delete user post
				$role->add_cap( 'delete_others_' . $postType . 's' );

				$role->add_cap( 'edit_posts' ); // general: create posts -> even wp_post, affects all cpts
				$role->add_cap( 'upload_files' ); // general: change post image

				if ( $the_role == self::$CB_MANAGER_ID ) {
					$role->remove_cap( 'read_private_' . $postType . 's' );
					$role->remove_cap( 'delete_private_' . $postType . 's' );
					$role->remove_cap( 'delete_others_' . $postType . 's' );
				}
			}
		}
	}

	public static function admin_init() {
		// check if we have a new version and run tasks
		Upgrade::runTasksAfterUpdate();

		// Check if we need to run post options updated actions
		if ( get_transient( 'commonsbooking_options_saved' ) == 1 ) {
			AdminOptions::SetOptionsDefaultValues();

			flush_rewrite_rules();

			//checks if all the booking rules are in the correct format, complain if not
			BookingRuleApplied::validateRules();
			set_transient( 'commonsbooking_options_saved', 0 );
		}
	}

	/**
	 * Adds menu pages.
	 */
	public static function addMenuPages() {
		// Dashboard
		add_menu_page(
			'Commons Booking',
			'Commons Booking',
			'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
			'cb-dashboard',
			array( Dashboard::class, 'index' ),
			'data:image/svg+xml;base64,' . base64_encode( '<?xml version="1.0" encoding="UTF-8" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg width="100%" height="100%" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/"><path fill="black" d="M12.94,5.68l0,-5.158l6.132,1.352l0,5.641c0.856,-0.207 1.787,-0.31 2.792,-0.31c3.233,0 5.731,1.017 7.493,3.05c1.762,2.034 2.643,4.661 2.643,7.88l0,0.458c0,3.232 -0.884,5.862 -2.653,7.89c-1.769,2.027 -4.283,3.04 -7.542,3.04c-1.566,0 -2.965,-0.268 -4.196,-0.806c1.449,-1.329 2.491,-2.998 3.015,-4.546c0.335,0.123 0.729,0.185 1.181,0.185c1.311,0 2.222,-0.51 2.732,-1.53c0.51,-1.021 0.765,-2.432 0.765,-4.233l0,-0.458c0,-1.749 -0.255,-3.146 -0.765,-4.193c-0.51,-1.047 -1.401,-1.57 -2.673,-1.57c-0.527,0 -0.978,0.107 -1.351,0.321c-1.051,-3.59 -4.047,-6.125 -7.573,-7.013Zm6.06,15.774c0.05,0.153 0.042,0.325 0.042,0.338c-0.001,2.138 -0.918,4.209 -2.516,5.584c-0.172,0.148 -0.346,0.288 -0.523,0.42c-0.209,-0.153 -0.411,-0.316 -0.608,-0.489c-1.676,-1.477 -2.487,-3.388 -2.434,-5.733l0.039,-0.12l6,0Zm-6.06,-13.799c3.351,1.058 5.949,3.88 6.092,7.332c0.011,0.254 0.11,0.416 -0.032,0.843l-6,0l-0.036,-0.108l-0.024,0l0,-8.067Z" /><path fill="black" d="M21.805,24.356c-0.901,0 -1.57,-0.245 -2.008,-0.735c-0.437,-0.491 -0.656,-1.213 -0.656,-2.167l-6.141,0l-0.039,0.12c-0.053,2.345 0.758,4.256 2.434,5.733c1.676,1.478 3.813,2.216 6.41,2.216c3.259,0 5.773,-1.013 7.542,-3.04c1.769,-2.028 2.653,-4.658 2.653,-7.89l0,-0.458c0,-3.219 -6.698,-1.749 -6.698,0l0,0.458c0,1.801 -0.255,3.212 -0.765,4.233c-0.51,1.02 -1.421,1.53 -2.732,1.53Z" /><path fill="black" d="M14.244,28.78c-1.195,0.495 -2.545,0.743 -4.049,0.743c-3.259,0 -5.773,-1.013 -7.542,-3.04c-1.769,-2.028 -2.653,-4.658 -2.653,-7.89l0,-0.458c0,-3.219 0.881,-5.846 2.643,-7.88c1.762,-2.033 4.26,-3.05 7.493,-3.05c0.917,0 1.773,0.086 2.566,0.258c1.566,0.34 2.891,1.016 3.972,2.027c1.63,1.524 2.418,3.597 2.365,6.221l-0.039,0.119l-6.141,0c0,-1.02 -0.226,-1.852 -0.676,-2.494c-0.451,-0.643 -1.133,-0.964 -2.047,-0.964c-1.272,0 -2.163,0.523 -2.673,1.57c-0.51,1.047 -0.765,2.444 -0.765,4.193l0,0.458c0,1.801 0.255,3.212 0.765,4.233c0.51,1.02 1.421,1.53 2.732,1.53c0.32,0 0.61,-0.031 0.871,-0.093c0.517,1.648 1.73,3.281 3.178,4.517Zm-1.244,-7.326l6,0l0.039,0.12c0.053,2.345 -0.758,4.256 -2.434,5.733c-0.134,0.118 -0.27,0.231 -0.409,0.339c-1.85,-1.327 -3.122,-3.233 -3.227,-5.424c-0.011,-0.228 -0.105,-0.357 0.031,-0.768Z" /></svg>' )
		);
		add_submenu_page(
			'cb-dashboard',
			'Dashboard',
			'Dashboard',
			'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
			'cb-dashboard',
			array( Dashboard::class, 'index' ),
			0
		);

		// Custom post types
		$customPostTypes = commonsbooking_isCurrentUserAdmin() ? self::getCustomPostTypes() : self::getCBManagerCustomPostTypes();
		foreach ( $customPostTypes as $cbCustomPostType ) {
			$params = $cbCustomPostType->getMenuParams();
			add_submenu_page(
				$params[0],
				$params[1],
				$params[2],
				$params[3] . '_' . $cbCustomPostType::$postType,
				$params[4],
				$params[5],
				$params[6]
			);
		}

		// Show categories only for admins
		if ( commonsbooking_isCurrentUserAdmin() ) {
			// Add menu item for item categories
			add_submenu_page(
				'cb-dashboard',
				esc_html__( 'Item Categories', 'commonsbooking' ),
				esc_html__( 'Item Categories', 'commonsbooking' ),
				'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
				admin_url( 'edit-tags.php' ) . '?taxonomy=' . Item::$postType . 's_category',
				''
			);

			// Add menu item for location categories
			add_submenu_page(
				'cb-dashboard',
				esc_html__( 'Location Categories', 'commonsbooking' ),
				esc_html__( 'Location Categories', 'commonsbooking' ),
				'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
				admin_url( 'edit-tags.php' ) . '?taxonomy=' . Location::$postType . 's_category',
				''
			);

			//Add menu item for mass operations
			add_submenu_page(
				'cb-dashboard',
				esc_html__( 'Mass Operations', 'commonsbooking' ),
				esc_html__( 'Mass Operations', 'commonsbooking' ),
				'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
				'cb-mass-operations',
				array( MassOperations::class, 'index' )
			);
		}
	}

	/**
	 * Handles the validation of booking forms. We customize the transient so that only the user that is supposed to see the transient will
	 * actually see it.
	 * @return void
	 */
	public static function handleBookingForms(): void {
		try {
			\CommonsBooking\Wordpress\CustomPostType\Booking::handleFormRequest();
		}
		catch ( BookingDeniedException $e ) {
			set_transient(
				\CommonsBooking\Wordpress\CustomPostType\Booking::ERROR_TYPE . '-' . get_current_user_id(),
				$e->getMessage(),
				30 //Expires very quickly, so that outdated messsages will not be shown to the user
			);
			$targetUrl = $e->getRedirectUrl();
			if ( $targetUrl) {
				header( 'Location: ' . $targetUrl );
				exit();
			}
		}
	}

	/**
	 * Filters the CSS classes for the body tag in the admin.
	 *
	 * @param string $classes
	 * @return string
	 */
	public static function filterAdminBodyClass( $classes ) {
		global $current_screen, $plugin_page;

		$cssClass = 'cb-admin';

		if ( $plugin_page === 'cb-dashboard' ) {
			return $classes . ' ' . $cssClass;
		}

		switch ( $current_screen->post_type ) {
			case \CommonsBooking\Wordpress\CustomPostType\Booking::$postType:
			case Item::$postType:
			case Location::$postType:
			case Map::$postType:
			case Restriction::$postType:
			case Timeframe::$postType:
				return $classes . ' ' . $cssClass;
		}

		switch ( $current_screen->taxonomy ) {
			case 'cb_items_category':
			case 'cb_locations_category':
				return $classes . ' ' . $cssClass;
		}

		return $classes;
	}

	/**
	 * Registers custom post types.
	 */
	public static function registerCustomPostTypes() {
		foreach ( self::getCustomPostTypes() as $customPostType ) {
			$cptArgs = $customPostType->getArgs();
			//make export possible when using WP_DEBUG, this allows us to use the export feature for creating new E2E tests
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$cptArgs['can_export'] = true;
			}
			register_post_type( $customPostType::getPostType(), $cptArgs );
			$customPostType->initListView();
			$customPostType->initHooks();
		}
	}

	/**
	 * Registers additional post statuses.
	 */
	public static function registerPostStates() {
		foreach ( Booking::$bookingStates as $bookingState ) {
			new PostStatus( $bookingState, __( ucfirst( $bookingState ), 'commonsbooking' ) );
		}
	}

    /**
	 * Registers category taxonomy for Custom Post Type Item
     *
     * TODO: This can probably be re-factored to the more generic CustomPostType
     *
	 * @return void
	 */
	public static function registerItemTaxonomy() {
		$customPostType = Item::getPostType();
		$taxonomy = $customPostType . 's_category';

		$result   = register_taxonomy(
			$taxonomy,
			$customPostType,
			array(
				'label'             => esc_html__( 'Item Category', 'commonsbooking' ),
				'rewrite'           => array( 'slug' => $customPostType . '-cat' ),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'public'            => true,
				'show_admin_column' => true
			)
		);

		// If error, yell about it.
		if ( is_wp_error( $result ) ) {
			wp_die( $result->get_error_message() );
		}

		//hook the term updates to the item post type function. This only runs when a term is updated but that is enough. When a term is added, the post is saved and therefore the other hook is triggered which also runs the same function.
		add_action( 'saved_' . $taxonomy, array( 'CommonsBooking\Wordpress\CustomPostType\Item', 'termChange' ), 10, 3 );
		add_action( 'delete_' . $taxonomy, array( 'CommonsBooking\Wordpress\CustomPostType\Item', 'termChange' ), 10, 3 );
	}

	/**
	 * Registers category taxonomy for Custom Post Type Location
     *
	 * @return void
	 */
	public static function registerLocationTaxonomy() {
		$customPostType = Location::getPostType();
		$taxonomy = $customPostType . 's_category';

		$result   = register_taxonomy(
			$taxonomy,
			$customPostType,
			array(
				'label'             => esc_html__( 'Location Category', 'commonsbooking' ),
				'rewrite'           => array( 'slug' => $customPostType . '-cat' ),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true
			)
		);

		// If error, yell about it.
		if ( is_wp_error( $result ) ) {
			wp_die( $result->get_error_message() );
		}

		//hook the term updates to the location post type function. This only runs when a term is updated but that is enough. When a term is added, the post is saved and therefore the other hook is triggered which also runs the same function.
		add_action( 'saved_' . $taxonomy, array( 'CommonsBooking\Wordpress\CustomPostType\Location', 'termChange' ), 10, 3 );
		add_action( 'delete_' . $taxonomy, array( 'CommonsBooking\Wordpress\CustomPostType\Location', 'termChange' ), 10, 3 );
	}

	/**
	 * Renders error for backend_notice.
	 * TODO refactor this using the AdminMessage type
	 */
	public static function renderError() {
		$errorTypes = [
			Model\Timeframe::ERROR_TYPE,
			Model\Timeframe::ORPHANED_TYPE,
			BookingCode::ERROR_TYPE,
			OptionsTab::ERROR_TYPE,
            Model\Booking::ERROR_TYPE,
		];

		foreach ( $errorTypes as $errorType ) {
			if ( $error = get_transient( $errorType ) ) {
				$class = 'notice notice-error';
				printf(
					'<div class="%1$s"><p>%2$s</p></div>',
					esc_attr( $class ),
					commonsbooking_sanitizeHTML( $error )
				);
				delete_transient( $errorType );
			}
		}

		$infoTypes = [
			OptionsTab::INFO_TYPE,
		];

		foreach ( $infoTypes as $info_type ) {
			if ( $message = get_transient( $info_type ) ) {
				$class = 'notice notice-info is-dismissible';
				printf(
					'<div class="%1$s"><p>%2$s</p></div>',
					esc_attr( $class ),
					commonsbooking_sanitizeHTML( $message )
				);
				delete_transient( $info_type );
			}
		}
	}

	/**
	 * Enable Legacy CB1 profile fields.
	 */
	public static function maybeEnableCB1UserFields() {
		$enabled = Settings::getOption( 'commonsbooking_options_migration', 'enable-cb1-user-fields' );
		if ( $enabled == 'on' ) {
			new CB1UserFields();
		}
	}

	/**
	 * run actions after plugin options are saved
	 * TODOD: @markus-mw I think this function is deprecated now. Would you please check this? It is only referenced by an inactive hook
	 */
	public static function saveOptionsActions() {
		// Run actions after options update
		set_transient( 'commonsbooking_options_saved', 1 );
	}

	/**
	 * Register Admin-Options
	 */
	public static function registerAdminOptions() {
		$options_array = include COMMONSBOOKING_PLUGIN_DIR . '/includes/OptionsArray.php';
		foreach ( $options_array as $tab_id => $tab ) {
			new OptionsTab( $tab_id, $tab );
		}
	}


	public static function registerScriptsAndStyles() {
		$base = COMMONSBOOKING_PLUGIN_ASSETS_URL . 'packaged/';

		$version_file_path = COMMONSBOOKING_PLUGIN_DIR . 'assets/packaged/dist.json';
		$version_file_content = file_get_contents($version_file_path);
		$versions = json_decode($version_file_content, true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			trigger_error("Unable to parse commonsbooking asset version file in $version_file_path.");
		}

		// spin.js
		wp_register_script('cb-spin', $base . 'spin-js/spin.min.js', [], $versions['spin.js']);

		// leaflet
		wp_register_script('cb-leaflet', $base . 'leaflet/leaflet.js',[], $versions['leaflet']);
		wp_register_style('cb-leaflet', $base . 'leaflet/leaflet.css', [], $versions['leaflet']);

		// leaflet markercluster
		wp_register_script(
			'cb-leaflet-markercluster',
			$base . 'leaflet-markercluster/leaflet.markercluster.js',
			['cb-leaflet'],
			$versions['leaflet.markercluster']
		);
		wp_register_style(
			'cb-leaflet-markercluster-base',
			$base . 'leaflet-markercluster/MarkerCluster.css',
			[],
			$versions['leaflet.markercluster']
		);
		wp_register_style(
			'cb-leaflet-markercluster',
			$base . 'leaflet-markercluster/MarkerCluster.Default.css',
			['cb-leaflet-markercluster-base'],
			$versions['leaflet.markercluster']
		);

		// leaflet-easybutton
		wp_register_script(
			'cb-leaflet-easybutton',
			$base . 'leaflet-easybutton/easy-button.js',
			['cb-leaflet'],
			$versions['leaflet-easybutton']
		);
		wp_register_style(
			'cb-leaflet-easybutton',
			$base . 'leaflet-easybutton/easy-button.css',
			['cb-leaflet'],
			$versions['leaflet-easybutton']
		);

		// leaflet-spin
		wp_register_script(
			'cb-leaflet-spin',
			$base . 'leaflet-spin/leaflet.spin.min.js',
			['cb-leaflet', 'cb-spin'],
			$versions['leaflet-spin']
		);

		// leaflet-messagebox
		wp_register_script(
			'cb-leaflet-messagebox',
			COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-messagebox/leaflet-messagebox.js',
			['cb-leaflet'],
			'1.1',
		);
		wp_register_style(
			'cb-leaflet-messagebox',
			COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet-messagebox/leaflet-messagebox.css',
			['cb-leaflet'],
			'1.1'
		);

		// jquery overscroll
		wp_register_script(
			'cb-jquery-overscroll',
			COMMONSBOOKING_MAP_ASSETS_URL . 'overscroll/jquery.overscroll.min.js',
			['jquery'],
			'1.7.7'
		);

		//cb_map shortcode
		wp_register_script( 'cb-map-filters',
			COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-filters.js',
			['jquery'],
			COMMONSBOOKING_MAP_PLUGIN_DATA['Version']
		);
		wp_register_script(
			'cb-map-shortcode',
			COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-shortcode.js',
			['jquery', 'cb-jquery-overscroll', 'cb-leaflet', 'cb-leaflet-easybutton', 'cb-leaflet-markercluster', 'cb-leaflet-messagebox', 'cb-leaflet-spin', 'cb-map-filters'],
			COMMONSBOOKING_MAP_PLUGIN_DATA['Version']
		);
		wp_register_style(
			'cb-map-shortcode',
			COMMONSBOOKING_MAP_ASSETS_URL . 'css/cb-map-shortcode.css',
			['dashicons', 'cb-leaflet', 'cb-leaflet-easybutton', 'cb-leaflet-markercluster', 'cb-leaflet-messagebox'],
			COMMONSBOOKING_MAP_PLUGIN_DATA['Version']
		);

		// vue
		wp_register_script('cb-vue', $base . 'vue/vue.runtime.global.prod.js', [], $versions['vue']);

		// commons-search
		wp_register_script(
			'cb-commons-search',
			$base . 'commons-search/commons-search.umd.js',
			['cb-leaflet', 'cb-leaflet-markercluster', 'cb-vue'],
			$versions['@commonsbooking/frontend']
		);
		wp_register_style(
			'cb-commons-search',
			$base . 'commons-search/style.css',
			['cb-leaflet', 'cb-leaflet-markercluster'],
			$versions['@commonsbooking/frontend']
		);
	}

	public function registerShortcodes() {
		add_shortcode( 'cb_search', array( SearchShortcode::class, 'execute' ) );
	}

	/**
 	 * Registers all user data exporters ({@link https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-exporter-to-your-plugin/}).
 	 *
 	 * @param array $exporters
 	 *
 	 * @return mixed
 	 */
	public static function registerUserDataExporters( $exporters ) {
		$exporters[COMMONSBOOKING_PLUGIN_SLUG] = array(
			'exporter_friendly_name' => __( 'CommonsBooking Bookings', 'commonsbooking' ),
			'callback'               => array( \CommonsBooking\Wordpress\CustomPostType\Booking::class, 'exportUserBookingsByEmail' ),
		);
		return $exporters;
	}

	/**
	 * Registers all user data erasers ({@link https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/}).
	 *
	 * @param $erasers
	 *
	 * @return mixed
	 */
	public static function registerUserDataErasers( $erasers ) {
		$erasers[COMMONSBOOKING_PLUGIN_SLUG] = array(
			'eraser_friendly_name' => __( 'CommonsBooking Bookings', 'commonsbooking' ),
			'callback'             => array( \CommonsBooking\Wordpress\CustomPostType\Booking::class, 'removeUserBookingsByEmail'),
		);
		return $erasers;
	}

	/**
	 *  Init hooks.
	 */
	public function init() {
		do_action( 'cmb2_init' );

		// Enable CB1 User Fields (needed in case of migration from cb 0.9.x)
		add_action( 'init', array( self::class, 'maybeEnableCB1UserFields' ) );

		// Register custom post types
		add_action( 'init', array( self::class, 'registerCustomPostTypes' ), 0 );
		add_action( 'init', array( self::class, 'registerPostStates' ), 0 );

		// Register custom post types taxonomy / categories
		add_action( 'init', array( self::class, 'registerItemTaxonomy' ), 30 );

		// Register custom post types taxonomy / categories
		add_action( 'init', array( self::class, 'registerLocationTaxonomy' ), 30 );

		// register admin options page
		add_action('init', array(self::class, 'registerAdminOptions'), 40);

		//loads the Scheduler
		add_action( 'init', array( Scheduler::class, 'initHooks' ) , 40);

		//handle the booking forms, needs to happen after taxonomy registration so that we can access the taxonomy
		add_action('init', array(self::class, 'handleBookingForms'), 50);

		// admin init tasks
		add_action( 'admin_init', array( self::class, 'admin_init' ), 30 );

		// Add menu pages
		add_action( 'admin_menu', array( self::class, 'addMenuPages' ) );

		// Filter body classes of admin pages
		add_filter( 'admin_body_class', array( self::class, 'filterAdminBodyClass' ), 10, 1 );

		// Parent Menu Fix
		add_filter( 'parent_file', array( $this, 'setParentFile' ) );

		// register scripts
		add_action('init', array($this, 'registerScriptsAndStyles'));

		// register shortcodes
		add_action('init', array($this, 'registerShortcodes'));

		// Remove cache items on save.
		add_action( 'wp_insert_post', array( $this, 'savePostActions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( Plugin::class, 'addWarmupAjaxToOutput' ) );
		add_action( 'admin_enqueue_scripts', array( Plugin::class, 'addWarmupAjaxToOutput' ) );

		//Add custom hook to clear cache from cronjob
		add_action( self::$clearCacheHook, array( $this, 'clearCache' ) );

		add_action('plugins_loaded', array($this, 'commonsbooking_load_textdomain'), 20);

		$map_admin = new LocationMapAdmin();
		add_action( 'plugins_loaded', array( $map_admin, 'load_location_map_admin' ) );

		// register User Widget
		add_action( 'widgets_init', array( $this, 'registerUserWidget' ) );

		// remove Row Actions
		add_filter( 'post_row_actions', array( CustomPostType::class, 'modifyRowActions' ), 10, 2 );

		// add custom image sizes
		add_action( 'after_setup_theme', array( $this, 'AddImageSizes' ) );

		// renders custom update notice on plugin listing
		add_action(
            'in_plugin_update_message-' . COMMONSBOOKING_PLUGIN_BASE,
            function ( $plugin_data ) {
				$upgrade = new Upgrade(COMMONSBOOKING_VERSION, $plugin_data['new_version']);
                $upgrade->updateNotice();
            }
        );

        // add ajax search for cmb2 fields (e.g. user search etc.)
        add_filter('cmb2_field_ajax_search_url', function(){
            return (COMMONSBOOKING_PLUGIN_URL . '/vendor/ed-itsolutions/cmb2-field-ajax-search/');
        });

		//hook into WordPress personal data exporter
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'registerUserDataExporters' ) );

		//hook into WordPress personal data eraser
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'registerUserDataErasers' ) );

    	// iCal rewrite
		iCalendar::initRewrite();

	}

	/**
	 * Loads text domain for (from local file or wordpress plugin-dir)
	 *
	 * @return void
	 */
	public function commonsbooking_load_textdomain() {
		/**
		 * We want to ensure that new translations are available directly after update
		 * so we load the local translation first if its available, otherwise we use the load_plugin_textdomain
		 * to load from the global WordPress translation file.
		 */

		$locale                  = get_locale();
		$locale_translation_file = COMMONSBOOKING_PLUGIN_DIR . 'languages/' . COMMONSBOOKING_PLUGIN_SLUG . '-' . $locale . '.mo';

		if ( file_exists( $locale_translation_file ) ) {
			load_textdomain( COMMONSBOOKING_PLUGIN_SLUG, $locale_translation_file );
		} else {
			load_plugin_textdomain( 'commonsbooking', false, COMMONSBOOKING_PLUGIN_DIR . 'languages' );
		}
	}

	/**
	 * Removes cache item in connection to post_type.
     *
	 * @TODO: Add test if cache is cleared correctly.
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public function savePostActions( $post_id, $post, $update ) {
		if ( ! self::isPostCustomPostType( $post ) ) {
			return;
		}

		$ignoredStates = [ 'auto-draft', 'draft' ];
		if ( ! in_array( $post->post_status, $ignoredStates ) || $update ) {
			$tags   = Wordpress::getRelatedPostIds( $post_id );
			$tags[] = 'misc';
			self::scheduleClearCache( $tags );
		}
	}

	/**
	 * @return array
	 */
	public static function getCustomPostTypesLabels(): array {
		return [
			Item::getPostType(),
			Location::getPostType(),
			Timeframe::getPostType(),
			Map::getPostType(),
			Restriction::getPostType(),
			\CommonsBooking\Wordpress\CustomPostType\Booking::getPostType(),
		];
	}

	/**
	 * Function to register our new routes from the controller.
	 */
	/**
	 * Function to register our new routes from the controller.
	 */
	public function initRoutes() {
		// Check if API is activated in settings
		$api_activated = Settings::getOption( 'commonsbooking_options_api', 'api-activated' );
		if ( $api_activated != 'on' ) {
			return false;
		}

		add_action(
			'rest_api_init',
			function () {
				$routes = [
					new \CommonsBooking\API\AvailabilityRoute(),
					new \CommonsBooking\API\ItemsRoute(),
					new \CommonsBooking\API\LocationsRoute(),
					// new \CommonsBooking\API\OwnersRoute(),
					new \CommonsBooking\API\ProjectsRoute(),
					new \CommonsBooking\API\GBFS\Discovery(),
					new \CommonsBooking\API\GBFS\StationInformation(),
					new \CommonsBooking\API\GBFS\StationStatus(),
					new \CommonsBooking\API\GBFS\SystemInformation(),

				];
				foreach ( $routes as $route ) {
					$route->register_routes();
				}
			}
		);
	}

	/**
	 * Adds bookingcode actions.
	 * They:
	 * - Hook appropriate function to button that downloads the booking codes in the backend.
	 *    @see \CommonsBooking\View\BookingCodes::renderTable()
	 * - Hook appropriate function to button that sends out emails with booking codes to the station.
	 *   @see \CommonsBooking\View\BookingCodes::renderDirectEmailRow()
	 */
	public function initBookingcodes() {
		add_action( 'admin_action_cb_download-bookingscodes-csv', array( View\BookingCodes::class, 'renderCSV' ), 10, 0 );
        add_action( 'admin_action_cb_email-bookingcodes', array(View\BookingCodes::class, 'emailCodes'), 10, 0);
	}

	/**
	 * Fixes highlighting issue for cpt views.
	 *
	 * @param $parent_file
	 *
	 * @return string
	 */
	public function setParentFile( $parent_file ): string {
		global $current_screen;

		// Set 'cb-dashboard' as parent for cb post types
		if ( in_array( $current_screen->base, array( 'post', 'edit' ) ) ) {
			foreach ( self::getCustomPostTypes() as $customPostType ) {
				if ( $customPostType::getPostType() === $current_screen->post_type ) {
					return 'cb-dashboard';
				}
			}
		}

		// Set 'cb-dashboard' as parent for cb categories
		if ( in_array( $current_screen->base, array( 'edit-tags' ) ) ) {
			if (
				$current_screen->taxonomy && in_array(
                    $current_screen->taxonomy,
                    [
						Location::$postType . 's_category',
						Item::$postType . 's_category',
                    ]
                )
			) {
				return 'cb-dashboard';
			}
		}

		return $parent_file;
	}

	/**
	 * Appends view data to content.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function getTheContent( $content ): string {
		// Check if we're inside the main loop in a single post page.
		if ( is_single() && in_the_loop() && is_main_query() ) {
			global $post;
			foreach ( self::getCustomPostTypes() as $customPostType ) {
				if ( $customPostType::getPostType() === $post->post_type ) {
					return $content . $customPostType::getView()::content( $post );
				}
			}
		}

		return $content;
	}

	public function registerUserWidget() {
		register_widget( '\CommonsBooking\Wordpress\Widget\UserWidget' );
	}


	function AddImageSizes() {

		$crop = Settings::getOption( 'commonsbooking_options_templates', 'image_listing_crop' ) == 'on' ? true : false;

		// image size for small item and location post images in listings
		add_image_size(
			'cb_listing_small',
			Settings::getOption( 'commonsbooking_options_templates', 'image_listing_small_width' ),
			Settings::getOption( 'commonsbooking_options_templates', 'image_listing_small_height' ),
			$crop
		);

		// image size for medium item and location post images in listings
		add_image_size(
			'cb_listing_medium',
			Settings::getOption( 'commonsbooking_options_templates', 'image_listing_medium_width' ),
			Settings::getOption( 'commonsbooking_options_templates', 'image_listing_medium_height' ),
			$crop
		);
	}
}