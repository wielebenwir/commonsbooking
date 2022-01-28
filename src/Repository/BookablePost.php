<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Plugin;
use WP_Query;

abstract class BookablePost extends PostRepository {
	/**
	 * Get all Locations or Items current user is allowed to see/edit
	 *
	 * @param bool $publishedOnly
	 *
	 * @return array
	 */
	public static function getByCurrentUser( bool $publishedOnly = false ): array {
		$current_user = wp_get_current_user();
		$items        = [];

		$customId = md5( $current_user->ID . static::getPostType() );

		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			// Get all Locations where current user is author
			$args = array(
				'post_type' => static::getPostType(),
				'author'    => $current_user->ID,
				'nopaging'  => true
			);
			if ( $publishedOnly ) {
				$args['post_status'] = 'publish';
			}

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$items = array_merge( $items, $query->get_posts() );
			}

			if ( commonsbooking_isCurrentUserAdmin() ) {
				// if user has admin-role get all available items
				$args = array(
					'post_type' => static::getPostType(),
					'nopaging'  => true
				);
			} else {
				// get all items where current user is assigned as admin
				$args = array(
					'post_type'  => static::getPostType(),
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => '_' . static::getPostType() . '_admins',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_' . static::getPostType() . '_admins',
							'value'   => '"' . $current_user->ID . '"',
							'compare' => 'like',
						)
					),
					'nopaging'   => true
				);
			}

			if ( $publishedOnly ) {
				$args['post_status'] = 'publish';
			}

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$items = array_merge( $items, $query->get_posts() );
				usort($items, function ($a, $b) {
					$comparison = strcmp(strtolower($a->post_title), strtolower($b->post_title));

					if($comparison < 0) return -1;
					if($comparison > 0) return 1;
					return $comparison;
				});

			}

			Plugin::setCacheItem( $items, $customId );
			return $items;
		}
	}

	/**
	 * @return string
	 */
	abstract protected static function getPostType();

	/**
	 * Returns cb-posts for a user (respects author and assigned admins).
	 *
	 * @param $userId
	 * @param false $asModel
	 *
	 * @return array
	 */
	public static function getByUserId( $userId, bool $asModel = false ): array {
		$cbPosts = [];

		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			// Get all Locations where current user is author
			$args  = array(
				'post_type' => static::getPostType(),
				'author'    => $userId,
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$cbPosts = array_merge( $cbPosts, $query->get_posts() );
			}

			// get all cbPosts where current user is assigned as admin
			$args = array(
				'post_type'  => static::getPostType(),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => '_' . static::getPostType() . '_admins',
						'value'   => '"' . $userId . '"',
						'compare' => 'like',
					),
				),
			);

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$cbPosts = array_merge( $cbPosts, $query->get_posts() );
			}

			if ( $asModel ) {
				foreach ( $cbPosts as &$cbPost ) {
					$class  = static::getModelClass();
					$cbPost = new $class( $cbPost );
				}
			}

			Plugin::setCacheItem( $cbPosts );

			return $cbPosts;
		}
	}

	/**
	 * @return mixed
	 */
	abstract protected static function getModelClass();

	/**
	 * Returns an array of CB item post objects
	 *
	 *
	 * @param array $args WP Post args
	 * @param bool $bookable
	 *
	 * @return array
	 */
	public static function get( array $args = array(), bool $bookable = false ) {
		$posts             = [];
		$args['post_type'] = static::getPostType();

		if ( Plugin::getCacheItem( static::getPostType() ) ) {
			return Plugin::getCacheItem( static::getPostType() );
		} else {
			$defaults = array(
				'post_status' => array( 'publish', 'inherit' ),
				'nopaging'    => true,
			);

			// Add custom taxonomy filter
			if ( array_key_exists( 'category_slug', $args ) ) {
				$args['taxonomy'] = static::getPostType() . 's_category';
				$args['term']     = $args['category_slug'];
				unset( $args['category_slug'] );
			}

			$queryArgs = wp_parse_args( $args, $defaults );
			$query     = new WP_Query( $queryArgs );

			if ( $query->have_posts() ) {
				$posts = $query->get_posts();
				foreach ( $posts as $key => &$post ) {
					$class = static::getModelClass();
					$post  = new $class( $post );

					// If items shall be bookable, we need to check...
					if ( $bookable && ! $post->isBookable() ) {
						unset( $posts[ $key ] );
					}
				}
			}

			Plugin::setCacheItem( $posts, static::getPostType() );

			return $posts;
		}
	}

}
