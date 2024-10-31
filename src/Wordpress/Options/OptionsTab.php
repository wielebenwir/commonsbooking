<?php

namespace CommonsBooking\Wordpress\Options;

use CommonsBooking\Plugin;
use CommonsBooking\View\TimeframeExport;
use Exception;

/**
 * This adds the settings pane to the commonsbooking plugin page.
 * It uses CMB2 fields to display settings in a generic way and uses underlying functionality to save them.
 * The structure and contents of all the settings is controlled via the file OptionsArray.
 */
class OptionsTab {

	public $option_key = COMMONSBOOKING_PLUGIN_SLUG . '_options';
	public $id;
	public $tab_title;
	public $content;
	public $groups;

	// Error type for backend error output
	public const ERROR_TYPE = "commonsbooking-options-error";
	/**
	 * @var \CMB2
	 */
	private $metabox;

	public function __construct( string $id, array $content ) {
		$this->id        = $id;
		$this->content   = $content;
		$this->groups    = $content['field_groups'];
		$this->tab_title = $this->content['title'];

		add_action( 'cmb2_admin_init', array( $this, 'register' ) );

		add_action( 'cmb2_save_options-page_fields', array( self::class, 'savePostOptions' ), 10 );

	}

	public function register() {
		$this->registerOptionsTab();
		$this->registerOptionsGroups();
	}

	/**
	 * Register Tab
	 */
	public function registerOptionsTab() {

		$default_args = array(
			'id'           => $this->id,
			'title'        => esc_html__( 'CommonsBooking', 'commonsbooking' ),
			'object_types' => array( 'options-page' ),
			'option_key'   => $this->option_key . '_' . $this->id,
			'tab_group'    => $this->option_key,
			'tab_title'    => $this->tab_title,
			'parent_slug'  => $this->option_key
		);

		$top_level_args = array(
			'option_key'  => $this->option_key,
			'parent_slug' => 'options-general.php'
		);

		/* set first option as top level parent */
		if ( isset ( $this->content['is_top_level'] ) && $this->content['is_top_level'] ) {
			$args = array_merge( $default_args, $top_level_args );
		} else {
			$args = $default_args;
		}

		$this->metabox = new_cmb2_box( $args );
	}

	/**
	 * Register Tab Contents (Groups + Fields)
	 */
	public function registerOptionsGroups() {

		foreach ( $this->groups as $group ) {

			$group = $this->prependTitle( $group ); /* prepend title + description html */

			// Add Fields
			$fields = $group['fields'];
			foreach ( $fields as $field ) {
				$this->metabox->add_field( $field );
			}
		}
	}


	/**
	 * If array contains title or description, create a new row containing this text
	 *
	 * @param array $metabox_group
	 *
	 * @return array $metabox_group with title + description added as row
	 */
	public static function prependTitle( array $metabox_group ): array {

		if ( isset ( $metabox_group['title'] ) or isset ( $metabox_group['desc'] ) ) {

			$title = $metabox_group['title'] ?? '';
			$desc  = $metabox_group['desc'] ?? '';

			$header_html = sprintf(
				'<h4>%s</h4>%s', $title, $desc
			);

			$header_field = array(
				'id'      => $metabox_group['id'] . '_header',
				'desc'    => $header_html,
				'type'    => 'title',
				'classes' => 'cb_form_title',
			);

			array_unshift( $metabox_group['fields'], $header_field );
		}

		return $metabox_group;
	}

	/**
	 * actions to be fired after the options page was saved
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function savePostOptions() {
		if ( array_key_exists( 'action', $_REQUEST ) && $_REQUEST['action'] == "commonsbooking_options_export" ) {
			// Check for export action
			if ( array_key_exists( 'export-filepath', $_REQUEST ) && $_REQUEST['export-filepath'] !== "" ) {

				if ( ! is_dir( $_REQUEST['export-filepath'] ) ) {
					set_transient(
						self::ERROR_TYPE,
						commonsbooking_sanitizeHTML( __( "The export path does not exist or is not readable.", 'commonsbooking' ) ),
						45
					);
				}

				if ( ! is_writable( $_REQUEST['export-filepath'] ) ) {
					set_transient(
						self::ERROR_TYPE,
						commonsbooking_sanitizeHTML( __( "The export path is not writeable.", 'commonsbooking' ) ),
						45 );
				}
			}
		} elseif ( array_key_exists( 'action', $_REQUEST ) && $_REQUEST['action'] == "commonsbooking_options_advanced-options" ) {
			//Check for request to clear cache
			if ( array_key_exists( 'submit-cmb', $_REQUEST ) && $_REQUEST['submit-cmb'] == "clear-cache" ) {
				Plugin::clearCache();
				set_transient(
					self::ERROR_TYPE,
					commonsbooking_sanitizeHTML( __( "Cache cleared.", 'commonsbooking' ) ),
					45
				);
			}
		}


		// we set transient to be able to flush rewrites at an ini hook in Plugin.php to set permalinks properly
		set_transient( 'commonsbooking_options_saved', 1 );
	}
}
