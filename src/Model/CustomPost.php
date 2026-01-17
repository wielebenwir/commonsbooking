<?php

namespace CommonsBooking\Model;

use Exception;
use ReflectionMethod;
use WP_Post;

/**
 * Class CustomPost
 * Pseudo extends WP_Post class.
 *
 * All the public methods are available as template tags.
 * * In using magic methods you can retrieve data from model objects, when the model object class derive from this class. Using identifiers as per https://developer.wordpress.org/reference/classes/wp_post/#Member_Variables_of_WP_Post.
 * * All the public methods are available as template tags.
 *
 * @package CommonsBooking\Model
 *
 * @property int|string $post_author {@see WP_Post::$post_author}
 * @property string $post_status {@see WP_Post::$post_status}
 * @property int $ID {@see WP_Post::$ID}
 * @property string $post_title {@see WP_Post::$post_title}
 * @property string $post_date {@see WP_Post::$post_date}
 * @property string $post_name {@see WP_Post::$post_name}
 */
class CustomPost {
	/**
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * @var string
	 */
	protected $date;

	/**
	 * CustomPost constructor.
	 *
	 * @param int|WP_Post $post uses either int as id reference or the post object
	 *
	 * @throws Exception when $post param does not reference a valid post object
	 */
	public function __construct( $post ) {
		if ( $post instanceof WP_Post ) {
			$this->post = $post;
		} elseif ( is_int( $post ) ) {
			$this->post = get_post( $post );
		} else {
			throw new Exception( 'Invalid post param. Needed WP_Post or ID (int)' );
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
	 * @param string $field key of post_meta field for this post
	 *
	 * @return string|array The value of the meta field. An empty string if the field doesn't exist.
	 */
	public function getMeta( $field ) {
		return get_post_meta( $this->post->ID, $field, true );
	}

	/**
	 * @param string $key of post_meta field for this post
	 *
	 * @return int|null int if meta field yields integer value
	 */
	public function getMetaInt( string $key ): ?int {
		$val = $this->getMeta( $key );

		if ( filter_var( $val, FILTER_VALIDATE_INT ) !== false ) {
			return (int) $val;
		}
		return null;
	}


	/**
	 * When getting a value from a Model Object, we can use this magic method to get the value from the WP_Post object instead.
	 * This, for example, allows us to use $booking->post_title instead of $booking->post->post_title.
	 *
	 * @param $name
	 *
	 * @return array|mixed|void
	 */
	public function __get( $name ) {
		if ( $this->post == null ) {
			return;
		}

		if ( property_exists( $this->post, $name ) ) {
			return $this->post->$name;
		}
	}

	/**
	 * Enables that we can call methods of \CustomPost as template tags.
	 *
	 * @param string $name of the member function
	 * @param array  $arguments given to the template tag.
	 *
	 * @return array|mixed|void
	 * @throws \ReflectionException if called template tag is not a registered method
	 */
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
	 * Get the corresponding WP_Post object
	 *
	 * @return WP_Post
	 */
	public function getPost(): WP_Post {
		return $this->post;
	}

	/**
	 * Return Excerpt
	 *
	 * @return string html
	 */
	public function excerpt(): string {
		$excerpt = '';
		if ( has_excerpt( $this->ID ) ) {
			$excerpt .= wp_strip_all_tags( get_the_excerpt( $this->ID ) );
		}

		return $excerpt;
	}

	/**
	 * Return Title with permalink.
	 * This is mainly used by template tags.
	 *
	 * @return string html
	 */
	public function titleLink(): string {
		return sprintf( '<a href="%s" class="cb-title cb-title-link">%s</a>', esc_url( get_the_permalink( $this->ID ) ), commonsbooking_sanitizeHTML( $this->post_title ) );
	}

	/**
	 * Return Title
	 *
	 * @return string
	 */
	public function title(): string {
		return sprintf( '<span class="cb-title">%s</span>', commonsbooking_sanitizeHTML( $this->post_title ) );
	}

	/**
	 * Return Thumbnail with rendered div class="cb-thumbnail"
	 * uses custom defined image sizes (defined in {@see Plugin::AddImageSizes()})
	 *
	 * @param string|int[] $size Custom sizes are cb_listing_small or cb_listing_medium
	 *
	 * @return string
	 */
	public function thumbnail( $size = 'thumbnail' ): string {
		if ( has_post_thumbnail( $this->ID ) ) {
			return '<div class="cb-thumbnail">' . get_the_post_thumbnail(
				$this->ID,
				$size,
				array( 'class' => 'alignleft cb-image' )
			) . '</div>';
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
	 *
	 * @return false|\WP_User
	 */
	public function getUserData() {
		return get_userdata( $this->post_author );
	}


	/**
	 * Checks if the given user is the author of the current post.
	 *
	 * @param \WP_User $user
	 *
	 * @return bool - true if user is author, false if not.
	 */
	public function isAuthor( \WP_User $user ): bool {
		return $user->ID === intval( $this->post_author );
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
