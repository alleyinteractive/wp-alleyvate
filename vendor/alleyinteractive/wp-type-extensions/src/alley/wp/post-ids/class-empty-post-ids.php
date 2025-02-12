<?php
/**
 * Empty_Post_IDs class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_IDs;

use Alley\WP\Types\Post_IDs;

/**
 * No post IDs.
 */
final class Empty_Post_IDs implements Post_IDs {
	/**
	 * Post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		return [];
	}
}
