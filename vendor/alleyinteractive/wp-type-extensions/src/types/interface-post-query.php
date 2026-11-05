<?php
/**
 * Post_Query interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

use WP_Post;
use WP_Query;

/**
 * Describes an object that contains a single query for posts.
 */
interface Post_Query extends Post_IDs {
	/**
	 * Query object.
	 *
	 * @return WP_Query
	 */
	public function query_object(): WP_Query;

	/**
	 * Found post objects.
	 *
	 * @return WP_Post[]
	 */
	public function post_objects(): array;
}
