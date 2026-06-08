<?php
/**
 * Post_IDs interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes an object containing post IDs.
 */
interface Post_IDs {
	/**
	 * Post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array;
}
