<?php


namespace CommonsBooking\Repository;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use CommonsBooking\Psr\Cache\CacheException;
use CommonsBooking\Psr\Cache\InvalidArgumentException;
use WP_Post;
use WP_Query;

abstract class BookablePost extends PostRepository {

	/**
	 * Types which can be connected to each other via a Timeframe
	 *
	 * @var string[]
	 */
	private static $relationalTypes = [
		'item',
		'location',
	];

	/**
	 * Get all Locations or Items current user is allowed to see/edit
	 *
	 * @param bool $publishedOnly
	 *
	 * @return array
	 * @throws CacheException
	 * @throws InvalidArgumentException
	 */
	public static function getByCurrentUser( bool $publishedOnly = false ): array {
		$current_user = wp_get_current_user();
		$items        = [];

		$customId = md5( $current_user->ID . static::getPostType() );

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			// Get all Locations where current user is author
			$args = array(
				'post_type' => static::getPostType(),
				'author'    => $current_user->ID,
				'nopaging'  => true,
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
					'nopaging'  => true,
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
						),
					),
					'nopaging'   => true,
				);
			}

			if ( $publishedOnly ) {
				$args['post_status'] = 'publish';
			}

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$items = array_merge( $items, $query->get_posts() );
				usort(
					$items,
					function ( $a, $b ) {
						$comparison = strcmp( strtolower( $a->post_title ), strtolower( $b->post_title ) );

						if ( $comparison < 0 ) {
							return - 1;
						}
						if ( $comparison > 0 ) {
							return 1;
						}

						return $comparison;
					}
				);
			}
			$tags   = Wordpress::getPostIdArray( $items );
			$tags[] = 'misc';
			Plugin::setCacheItem( $items, $tags, $customId );

			return $items;
		}
	}

	/**
	 * Gets all the defined terms for locations / items.
	 * Will return an empty array if there are no terms or an error occurred.
	 *
	 * @return int[]|string|string[]|\WP_Error|\WP_Term[]
	 */
	public static function getTerms() {
		$terms = get_terms(
			array(
				'taxonomy'   => static::getTaxonomyName(),
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return $terms;
	}
	/**
	 * @return string
	 */
	abstract protected static function getPostType();

	/**
	 * @return string
	 */
	abstract protected static function getTaxonomyName();

	/**
	 * Returns cb-posts for a user (respects author and assigned admins).
	 *
	 * THIS METHOD DOES NOT SEEM TO BE USED ANYWHERE.
	 *
	 * @param mixed $userId
	 * @param bool  $asModel - Whether the posts should be returned as their respective model class or as WP_Post
	 *
	 * @return array
	 */
	public static function getByUserId( $userId, bool $asModel = false ): array {
		$cbPosts = [];

		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$userId = intval( $userId );
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

			Plugin::setCacheItem( $cbPosts, Wordpress::getPostIdArray( $cbPosts ) );

			return $cbPosts;
		}
	}

	/**
	 * Will get the class name of the model class that belongs to this post type.
	 *
	 * @return mixed
	 */
	abstract protected static function getModelClass();

	/**
	 * Returns an array of CB item post objects
	 *
	 * @param array $args WP Post args
	 * @param bool  $bookable
	 *
	 * @return \CommonsBooking\Model\Location[]|\CommonsBooking\Model\Item[]
	 */
	public static function get( array $args = array(), bool $bookable = false ): array {
		$posts             = [];
		$args['post_type'] = static::getPostType();
		$args['nopaging']  = true;

		// Add custom taxonomy filter
		if ( array_key_exists( 'category_slug', $args ) ) {
			$args['taxonomy'] = static::getTaxonomyName();
			$args['term']     = $args['category_slug'];
			unset( $args['category_slug'] );
		}

		$customCacheKey = md5( serialize( $args ) );

		$cacheItem = Plugin::getCacheItem( $customCacheKey );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$defaults = array(
				'post_status' => array( 'publish', 'inherit' ),
				'nopaging'    => true,
			);

			$queryArgs = wp_parse_args( $args, $defaults );
			$query     = new WP_Query( $queryArgs );

			if ( $query->have_posts() ) {
				$posts = $query->get_posts();

				// If items shall be bookable, we need to check...
				$bookablePostIds = $bookable ? self::getBookablePostIds( $posts ) : [];

				foreach ( $posts as $key => &$post ) {
					$postId = $post->ID;
					$class  = static::getModelClass();
					$post   = new $class( $post );

					if ( $bookable && ! isset( $bookablePostIds[ $postId ] ) ) {
						unset( $posts[ $key ] );
					}
				}
			}

			Plugin::setCacheItem( $posts, Wordpress::getTags( $posts ), $customCacheKey );

			return $posts;
		}
	}

	/**
	 * Returns the IDs of the given bookable posts (locations or items) that have at
	 * least one bookable timeframe.
	 *
	 * This is the batched equivalent of calling `isBookable()` on every post: instead
	 * of running the same bookable-timeframe query once per post (N+1), it runs a
	 * single query for all posts and maps the resulting timeframes back to their
	 * location or item IDs. The resulting set is identical, because
	 * {@see \CommonsBooking\Model\BookablePost::isBookable()} resolves the timeframes
	 * with a null date (`CustomPost::$date` is never set for locations and items) and
	 * the same minimum timestamp.
	 *
	 * @param \WP_Post[] $posts
	 *
	 * @return array<int, true> Bookable post IDs as keys.
	 * @throws Exception
	 */
	private static function getBookablePostIds( array $posts ): array {
		$postIds = array_map(
			static function ( $post ) {
				return $post->ID;
			},
			$posts
		);

		if ( ! count( $postIds ) ) {
			return [];
		}

		$modelClass = static::getModelClass();

		if ( $modelClass === \CommonsBooking\Model\Location::class ) {
			$timeframes   = \CommonsBooking\Repository\Timeframe::getBookableForCurrentUser(
				$postIds,
				[],
				null,
				true,
				Helper::getLastFullHourTimestamp()
			);
			$getEntityIds = static function ( $timeframe ) {
				return $timeframe->getLocationIDs();
			};
		} elseif ( $modelClass === \CommonsBooking\Model\Item::class ) {
			$timeframes   = \CommonsBooking\Repository\Timeframe::getBookableForCurrentUser(
				[],
				$postIds,
				null,
				true,
				Helper::getLastFullHourTimestamp()
			);
			$getEntityIds = static function ( $timeframe ) {
				return $timeframe->getItemIDs();
			};
		} else {
			// Unknown bookable post type: fall back to the per-post check.
			$bookablePostIds = [];
			foreach ( $posts as $post ) {
				$class = $modelClass;
				$model = new $class( $post );
				if ( $model->isBookable() ) {
					$bookablePostIds[ $post->ID ] = true;
				}
			}

			return $bookablePostIds;
		}

		$bookablePostIds = [];
		/** @var \CommonsBooking\Model\Timeframe $timeframe */
		foreach ( $timeframes as $timeframe ) {
			foreach ( $getEntityIds( $timeframe ) as $entityId ) {
				$bookablePostIds[ (int) $entityId ] = true;
			}
		}

		return $bookablePostIds;
	}

	/**
	 * Returns related object based on bookable post.
	 * Example: We'd like to have the items bookable at a specific location. With this function we are able to get them.
	 *
	 * @param $postId
	 * @param $originType
	 * @param $relatedType
	 * @param bool $bookable
	 *
	 * @return \CommonsBooking\Model\Location[] | \CommonsBooking\Model\Item[]
	 * @throws Exception
	 */
	protected static function getByRelatedPost( $postId, $originType, $relatedType, bool $bookable = false ): array {

		if ( ! in_array( $originType, self::$relationalTypes ) || ! in_array( $relatedType, self::$relationalTypes ) ) {
			throw new Exception( 'invalid type submitted' );
		}

		if ( $postId instanceof WP_Post ) {
			$postId = $postId->ID;
		}

		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$posts = self::getRelatedPosts( $postId, $originType, $relatedType );
			foreach ( $posts as $key => &$relatedPost ) {
				if ( $relatedType == 'item' ) {
					$relatedPost = new \CommonsBooking\Model\Item( $relatedPost );
					if ( $bookable && ! $relatedPost->getBookableTimeframesByLocation( $postId ) ) {
						unset( $posts[ $key ] );
					}
				}
				if ( $relatedType == 'location' ) {
					$relatedPost = new \CommonsBooking\Model\Location( $relatedPost );
					if ( $bookable && ! $relatedPost->getBookableTimeframesByItem( $postId ) ) {
						unset( $posts[ $key ] );
					}
				}
			}

			Plugin::setCacheItem( $posts, Wordpress::getTags( $posts, [ $postId ] ) );

			return $posts;
		}
	}

	/**
	 * Returns array with related posts for post with post id and origin type.
	 * Works only for locations and items!
	 *
	 * @param $postId
	 * @param $originType
	 * @param $relatedType
	 *
	 * @return array
	 */
	protected static function getRelatedPosts( $postId, $originType, $relatedType ): array {
		if ( $postId instanceof WP_Post ) {
			$postId = $postId->ID;
		}

		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$relatedPosts   = [];
			$relatedPostIds = [];
			$args           = array(
				'post_type'   => Timeframe::$postType,
				'post_status' => array( 'confirmed', 'unconfirmed', 'publish', 'inherit' ),
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'   => $originType . '-id',
						'value' => $postId,
					),
				),
				'nopaging'    => true,
			);

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$timeframes = $query->get_posts();
				foreach ( $timeframes as $timeframe ) {
					$relatedPostId = get_post_meta( $timeframe->ID, $relatedType . '-id', true );
					if ( $relatedPostId && ! in_array( $relatedPostId, $relatedPostIds ) ) {
						$relatedPostIds[] = $relatedPostId;
						$relatedPost      = get_post( $relatedPostId );

						if ( $relatedPost ) {
							// add only published items
							if ( $relatedPost->post_status == 'publish' ) {
								$relatedPosts[] = $relatedPost;
							}
						}
					}
				}
			}

			Plugin::setCacheItem( $relatedPosts, Wordpress::getTags( $relatedPosts, [ $postId ] ) );

			return $relatedPosts;
		}
	}
}
