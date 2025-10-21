<?php
/**
 * Exclude_Queries class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Queries;

use Alley\WP\Post_Query\Post_IDs_Query;
use Alley\WP\Types\Post_IDs;
use Alley\WP\Types\Post_Queries;
use Alley\WP\Types\Post_Query;

/**
 * Queries that exclude some posts.
 */
final class Exclude_Queries implements Post_Queries {
	/**
	 * Set up.
	 *
	 * @param Post_IDs     $exclude          Excluded post IDs.
	 * @param int          $default_per_page Posts per page if not specified in query.
	 * @param Post_Queries $origin           Post_Queries object.
	 */
	public function __construct(
		private readonly Post_IDs $exclude,
		private readonly int $default_per_page,
		private readonly Post_Queries $origin,
	) {}

	/**
	 * Query for posts using literal arguments.
	 *
	 * @param array<string, mixed> $args The arguments to be used in the query.
	 * @return Post_Query
	 */
	public function query( array $args ): Post_Query {
		$excluded_post_ids = $this->exclude->post_ids();
		$expected_per_page = $this->default_per_page;

		if ( isset( $args['posts_per_page'] ) && is_numeric( $args['posts_per_page'] ) ) {
			$expected_per_page = (int) $args['posts_per_page'];
		}

		// Ask for the number of posts we expect to return, plus the number of posts to exclude.
		$args['posts_per_page'] = $expected_per_page + \count( $excluded_post_ids );
		$overfetched_query      = $this->origin->query( $args );

		// Remove the excluded from the overfetched query.
		$diff_post_ids = array_diff( $overfetched_query->post_ids(), $excluded_post_ids );

		// Slice the number of posts we expect to return from the overfetched query.
		$per_page_post_ids = \array_slice( $diff_post_ids, 0, $expected_per_page );

		return new Post_IDs_Query( $per_page_post_ids );
	}
}
