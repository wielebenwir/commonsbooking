<?php

namespace CommonsBooking\Model;

use Exception;
use ReflectionMethod;
use WP_Post;

/**
 * Class CustomPost
 * Pseudo extends WP_Post class.
 * @package CommonsBooking\Model
 */
class CustomPost {
	/**
	 * @var WP_Post|WP_Post
	 */
	protected $post;

	/**
	 * @var
	 */
	protected $date;

	/**
	 * CustomPost constructor.
	 *
	 * @param int|WP_Post $post
	 */
	public function __construct( $post ) {
		if ( $post instanceof WP_Post ) {
			$this->post = $post;
		} elseif ( is_int( $post ) ) {
			$this->post = get_post( $post );
		} else {
			throw new Exception( "Invalid post param. Needed WP_Post or ID (int)" );
		}
	}

	/**
	 * Returns field value, even if it's a meta field.
	 *
	 * @param $fieldName
	 *
	 * @return mixed
	 */
	public function getFieldValue( $fieldName ) {
		$fieldName  = trim( $fieldName );
		$fieldValue = $this->{$fieldName};

		if ( ! $fieldValue ) {
			return $this->getMeta( $fieldName );
		}

		return $fieldValue;
	}

	/**
	 * Returns meta-field value.
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function getMeta( $field ) {
		return get_post_meta( $this->post->ID, $field, true );
	}

	public function __get( $name ) {
		if ( property_exists( $this->post, $name ) ) {
			return $this->post->$name;
		}
	}

	public function __call( $name, $arguments ) {
		if ( method_exists( $this->post, $name ) ) {
			$reflectionMethod = new ReflectionMethod( $this->post, $name );

			return $reflectionMethod->invokeArgs( $this->post, $arguments );
		}
		if ( property_exists( $this->post, $name ) ) {
			return $this->post->$name;
		}
	}

	/**
	 * @return WP_Post|WP_Post
	 */
	public function getPost() {
		return $this->post;
	}

	/**
	 * Return Excerpt
	 *
	 * @return string html
	 */
	public function excerpt() {
		$excerpt = '';
		if ( has_excerpt( $this->ID ) ) {
			$excerpt .= wp_strip_all_tags( get_the_excerpt( $this->ID ) );
		}

		return $excerpt;
	}

	/**
	 * Return Title with permalink
	 *
	 * @return string html
	 */
	public function titleLink() {
		return sprintf( '<a href="%s" class="cb-title cb-title-link">%s</a>', get_the_permalink( $this->ID ), $this->post_title );
	}

	/**
	 * Return Title
	 *
	 * @return html
	 */
	public function title() {
		return sprintf( '<span class="cb-title">%s</span>', $this->post_title );
	}

	/**
	 * Return Thumbnail
	 *
	 * @param string $size
	 *
	 * @return string html
	 */
	public function thumbnail( $size = 'post-thumbnail' ) {
		if ( has_post_thumbnail( $this->ID ) ) {
			return '<div class="cb-thumbnail">' . get_the_post_thumbnail( $this->ID, $size,
					array( 'class' => 'alignleft cb-image' ) ) . '</div>';
		}

		return '';
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * Returns user data.
	 * @return mixed
	 */
	public function getUserData() {
		return get_userdata( $this->post_author );
	}

	/**
	 * @param string|null $date Date-String
	 *
	 * @return CustomPost
	 */
	public function setDate( string $date = null ) {
		$this->date = $date;

		return $this;
	}

}
