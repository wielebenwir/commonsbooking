<?php


namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Settings\Settings;
use WP_Post;

abstract class CustomPostType {

	/**
	 * @var string
	 */
	public static $postType;

	/**
	 * @var
	 */
	protected $menuPosition;

	/**
	 * @return mixed
	 */
	public static function getWPNonceField() {
		return wp_nonce_field( static::getWPAction(), static::getWPNonceId(), false, true );
	}

	/**
	 * @return string
	 */
	public static function getWPAction(): string {
		return static::getPostType() . "-custom-fields";
	}

	/**
	 * @return string
	 */
	public static function getPostType(): string {
		return static::$postType;
	}

	/**
	 * @return string
	 */
	public static function getWPNonceId(): string {
		return static::getPostType() . "-custom-fields" . '_wpnonce';
	}

	/**
	 * Replaces WP_Posts by their title for options array.
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function sanitizeOptions( $data ) {
		$options = [];
		if ( $data ) {
			foreach ( $data as $key => $item ) {
				if ( $item instanceof WP_Post ) {
					$key   = $item->ID;
					$label = $item->post_title;
				} else {
					$label = $item;
				}
				$options[ $key ] = $label;
			}
		}

		return $options;
	}

	/**
	 * retrieve Custom Meta Data from CommonsBooking Options and convert them to cmb2 fields array
	 *
	 * @param mixed $type (item or location)
	 *
	 * @return array
	 */
	public static function getCMB2FieldsArrayFromCustomMetadata( $type ): ?array {

		$metaDataRaw    = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_metadata', 'metadata' );
		$metaDataLines  = explode( "\r\n", $metaDataRaw );
		$metaDataFields = array();

		foreach ( $metaDataLines as $metaDataLine ) {
			$metaDataArray = explode( ';', $metaDataLine );

			if ( count( $metaDataArray ) == 5 ) // $metaDataArray[0] = Type
			{
				$metaDataFields[ $metaDataArray[0] ][] = array(
					'id'   => $metaDataArray[1],
					'name' => $metaDataArray[2],
					'type' => $metaDataArray[3],
					'desc' => commonsbooking_sanitizeHTML( __( $metaDataArray[4], 'commonsbooking' ) ),
				);
			}
		}

		if ( array_key_exists( $type, $metaDataFields ) ) {
			return $metaDataFields[ $type ];
		} else {
			return null;
		}
	}

	/**
	 * Modifies Row Actions (like quick edit, trash etc) in CPT listings
	 *
	 * @param mixed $actions
	 *
	 * @return void
	 */
	public static function modifyRowActions( $actions, $post ) {

		// remove quick edit for timeframes
		if ( $post->post_type == Timeframe::getPostType() ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * @return mixed
	 */
	abstract public static function getView();

	/**
	 * Returns param for backend menu.
	 * @return array
	 */
	public function getMenuParams() {
		return [
			'cb-dashboard',
			$this->getArgs()['labels']['name'],
			$this->getArgs()['labels']['name'],
			'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
			'edit.php?post_type=' . static::getPostType(),
			'',
			$this->menuPosition ?: null
		];
	}

	/**
	 * @return mixed
	 */
	abstract public function getArgs();

	/**
	 * Manages custom columns for list view.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function setCustomColumns( $columns ) {
		if ( isset( $this->listColumns ) ) {
			foreach ( $this->listColumns as $key => $label ) {
				$columns[ $key ] = $label;
			}
		}

		return $columns;
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function setSortableColumns( $columns ) {
		if ( isset( $this->listColumns ) ) {
			foreach ( $this->listColumns as $key => $label ) {
				$columns[ $key ] = $key;
			}
		}

		return $columns;
	}

	/**
	 * Removes title column from backend listing.
	 */
	public function removeListTitleColumn() {
		add_filter( 'manage_' . static::getPostType() . '_posts_columns', function ( $columns ) {
			unset( $columns['title'] );

			return $columns;
		} );
	}

	/**
	 * Removes date column from backend listing.
	 */
	public function removeListDateColumn() {
		add_filter( 'manage_' . static::getPostType() . '_posts_columns', function ( $columns ) {
			unset( $columns['date'] );
			unset ( $columns['author'] ); // = 'Nutzer*in';

			return $columns;
		} );
	}

	/**
	 * Initiates needed hooks.
	 */
	abstract public function initHooks();

	/**
	 * Configures list-view
	 */
	public function initListView() {
		// List-View
		add_filter( 'manage_' . static::getPostType() . '_posts_columns', array( $this, 'setCustomColumns' ) );
		add_action( 'manage_' . static::getPostType() . '_posts_custom_column', array(
			$this,
			'setCustomColumnsData'
		), 10,
			2 );
		add_filter( 'manage_edit-' . static::getPostType() . '_sortable_columns', array(
			$this,
			'setSortableColumns'
		) );
		if ( isset( $this->listColumns ) ) {
			add_action( 'pre_get_posts', function ( $query ) {
				if ( ! is_admin() ) {
					return;
				}

				$orderby = $query->get( 'orderby' );
				if (
					strpos( $orderby, 'post_' ) === false &&
					in_array( $orderby, array_keys( $this->listColumns ) )
				) {
					$query->set( 'meta_key', $orderby );
					$query->set( 'orderby', 'meta_value' );
				}
			} );
		}
	}

	/**
	 * Adds data to custom columns
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function setCustomColumnsData( $column, $post_id ) {

		if ( $value = get_post_meta( $post_id, $column, true ) ) {
			echo $value;
		} else {
			if ( property_exists( $post = get_post( $post_id ), $column ) ) {
				echo $post->{$column};
			} else {
				echo '-';
			}
		}
	}

	/**
	 * Checks if method has been called before in current request.
	 * @param $methodName
	 *
	 * @return bool
	 */
	protected function hasRunBefore($methodName): bool {
		if(array_key_exists($methodName, $_REQUEST)) {
			return true;
		}
		$_REQUEST[$methodName] = true;
		return false;
	}

}