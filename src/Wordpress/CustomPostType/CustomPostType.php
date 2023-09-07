<?php


namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Exception\PostException;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Settings\Settings;
use CommonsBooking\View\Admin\Filter;
use WP_Post;

/**
 * Abstract wp custom post type for the CommonsBooking domain, implements a base of post functionality.
 */
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

					// add the status label only if post is in draft status
					$statusLabel = '';
					if ( $item->post_status == 'draft' ) {
						$statusLabel = ' [' . get_post_status_object( get_post_status( $item ) )->label . ']';
					}

					$key   = $item->ID;
					$label = $item->post_title . $statusLabel;
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
	 * The content is managed by user via options -> metadata sets 
	 *
	 * @param mixed $type (item or location)
	 *
	 * @return array
	 */
	public static function getCMB2FieldsArrayFromCustomMetadata( $type ): ?array {

		$metaDataRaw    = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'metadata' );
		$metaDataLines  = explode( "\r\n", $metaDataRaw );
		$metaDataFields = array();

		foreach ( $metaDataLines as $metaDataLine ) {
			$metaDataArray = explode( ';', $metaDataLine );

			if ( count( $metaDataArray ) == 5 ) 
			{
				// $metaDataArray[0] = Type
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

		// remove quick edit for timeframes, restrictions and bookings
		if ( $post->post_type == Timeframe::getPostType() 
			OR $post->post_type == Restriction::getPostType() 
			OR $post->post_type == Booking::getPostType()) {
			unset( $actions['inline hide-if-no-js'] );
		}

		// remove preview for timeframes and restrictions
		if ( $post->post_type == Timeframe::getPostType() OR $post->post_type == Restriction::getPostType() ) {
			unset( $actions['view'] );
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
		if ( array_key_exists('post_type', $_GET) && static::$postType !== $_GET['post_type'] ) {
			return;
		}

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

				//TODO: This does correctly sort the items by meta value, since WP 6.3 the meta value is not passed to the query anymore. Maybe the filter needs to be changed?
				$orderby = $query->get( 'orderby' );
				//Prior to WP 6.3, this was not an associative array (see #1309) but a string
				if (is_array($orderby)) {
					$orderKeys = array_keys( $orderby );
				}
				else {
					$orderKeys = array($orderby);
				}
				//we only want to sort by meta value if there is no sort by post_* value
				$orderKeys = array_filter($orderKeys, function($key) {
					return strpos($key, 'post_') !== false;
				});
				if (
					empty($orderKeys) &&
					in_array( $orderKeys, array_keys( $this->listColumns ) )
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
			echo commonsbooking_sanitizeHTML($value);
		} else {
			if ( property_exists( $post = get_post( $post_id ), $column ) ) {
				echo commonsbooking_sanitizeHTML($post->{$column});
			} else {
				echo '-';
			}
		}
	}

	/**
	 * Adds Category filter to backend list view
	 * 
	 */
	public static function addAdminCategoryFilter() {
		$values = [];
		$terms = get_terms(array(
			'taxonomy'	=> static::$postType . 's_category'
		));
		foreach ( $terms as $term ) {
			$values[ $term->term_id ] = $term->name;
		}
		Filter::renderFilter(
			static::$postType,
			esc_html__( 'Filter By Category ', 'commonsbooking' ),
			'filter_post_category',
			$values
		);
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

	/**
	 * Returns Model for CPT.
	 *
	 * @param int|WP_Post|CustomPost $post - Post ID or Post Object
	 *
	 * @return \CommonsBooking\Model\Booking|\CommonsBooking\Model\Item|\CommonsBooking\Model\Location|\CommonsBooking\Model\Restriction|\CommonsBooking\Model\Timeframe|\CommonsBooking\Model\Map
	 * @throws PostException
	 */
	public static function getModel( $post ) {
		if ( $post instanceof CustomPost ) {
			return $post;
		}
		if (is_int($post)) {
			$post = get_post($post);
		}
		if (! $post instanceof WP_Post) {
			throw new PostException('No suitable post object.');
		}
		switch($post->post_type) {
			case Booking::$postType:
				return new \CommonsBooking\Model\Booking($post);
			case Item::$postType:
				return new \CommonsBooking\Model\Item($post);
			case Location::$postType:
				return new \CommonsBooking\Model\Location($post);
			case Restriction::$postType:
				return new \CommonsBooking\Model\Restriction($post);
			case Timeframe::$postType:
				return new \CommonsBooking\Model\Timeframe($post);
			case Map::$postType:
				return new \CommonsBooking\Model\Map($post);
		}
		throw new PostException('No suitable model found for ' . $post->post_type);
	}

}