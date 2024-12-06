<?php
/**
 * WP_Query_Envelope class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Query;

use Alley\WP\Post_IDs\WP_Query_Post_IDs;
use Alley\WP\Types\Post_Query;
use WP_Post;
use WP_Query;

/**
 * Post_Query from an existing query.
 */
final class WP_Query_Envelope implements Post_Query {
	/**
	 * Set up.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function __construct(
		private readonly WP_Query $query,
	) {}

	/**
	 * Query object.
	 *
	 * @return WP_Query
	 */
	public function query_object(): WP_Query {
		return $this->query;
	}

	/**
	 * Found post objects.
	 *
	 * @return WP_Post[]
	 */
	public function post_objects(): array {
		$posts = array_map( 'get_post', $this->post_ids() );
		$posts = array_filter( $posts, fn ( $p ) => $p instanceof WP_Post );

		return $posts;
	}

	/**
	 * Found post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		$ids = new WP_Query_Post_IDs( $this->query_object() );

		return $ids->post_ids();
	}
}
