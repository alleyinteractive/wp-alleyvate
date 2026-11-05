<?php
/**
 * Post_IDs_Query class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Query;

use Alley\WP\Types\Post_Query;
use WP_Post;
use WP_Query;

/**
 * Query from post IDs.
 */
final class Post_IDs_Query implements Post_Query {
	/**
	 * Set up.
	 *
	 * @param int[] $post_ids Post IDs.
	 */
	public function __construct(
		private readonly array $post_ids,
	) {}

	/**
	 * Query object.
	 *
	 * @return WP_Query
	 */
	public function query_object(): WP_Query {
		$query        = new WP_Query();
		$query->posts = $this->post_ids();

		// Fill in other properties to make it look more like an executed query.
		$count                = \count( $query->posts );
		$query->found_posts   = $count;
		$query->post_count    = $count;
		$query->max_num_pages = 1;
		$query->set( 'fields', 'ids' );

		return $query;
	}

	/**
	 * Found post objects.
	 *
	 * @return WP_Post[]
	 */
	public function post_objects(): array {
		$post_ids = $this->post_ids();

		_prime_post_caches( $post_ids, false, false );

		$posts = array_map( 'get_post', $post_ids );
		$posts = array_filter( $posts, fn ( $p ) => $p instanceof WP_Post );

		return $posts;
	}

	/**
	 * Found post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		return array_map( 'intval', $this->post_ids );
	}
}
